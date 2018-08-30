<?php

/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Bakeway\Reports\Block\Adminhtml\Sales\Coupons\Renderer;

class City
        extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer
{

    /**
     * @var \magento\Sales\Model\OrderFactory
     */
    protected $collectionFactory;

    public function __construct(\Magento\Backend\Block\Context $context,
            \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $collectionFactory,
            array $data = array())
    {
        $this->collectionFactory = $collectionFactory;
        parent::__construct($context, $data);
    }

    public function render(\Magento\Framework\DataObject $row)
    {
        $ruleName = $row->getRuleName();
        $name = '';
        if (isset($ruleName) && !empty($ruleName)) {
            $collection = $this->collectionFactory->create();
            $collection->getSelect()->join(array('mo' => 'marketplace_orders'),
                    'main_table.entity_id = mo.order_id', array());
            $collection->getSelect()->join(array('bpl' => 'bakeway_partner_locations'),
                    'bpl.seller_id = mo.seller_id', array());
            $collection->getSelect()->join(array('bc' => 'bakeway_cities'),
                    'bc.id = bpl.city_id', array('bc.name'));
            $collection->getSelect()->where("main_table.coupon_rule_name = '" . $ruleName . "'");
            $collection->getSelect()->group('bc.name');
            foreach ($collection as $city) {
                $name = $city->getName();
            }
        }
        return $name;
    }

}
