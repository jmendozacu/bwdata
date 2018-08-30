<?php
/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 03-05-2018
 * Time: 15:29
 */

namespace Bakeway\GstReport\Block\Adminhtml\Unregistered\Renderer;

use Magento\Framework\DataObject;

class TotalCommission extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer
{
    /**
     * @var \Webkul\Marketplace\Model\ResourceModel\Saleslist\CollectionFactory
     */
    protected $sellerFactory;

    /**
     * TotalCommission constructor.
     * @param \Webkul\Marketplace\Model\ResourceModel\Saleslist\CollectionFactory $sellerFactory
     */
    public function __construct(\Webkul\Marketplace\Model\ResourceModel\Saleslist\CollectionFactory $sellerFactory)
    {
        $this->sellerFactory = $sellerFactory;
    }

    /**
     * @param DataObject $row
     * @return int|string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function render(DataObject $row)
    {
        $transId = $row->getEntityId();
        $sellerFactory = $this->sellerFactory->create();

        $sellerFactory = $sellerFactory->addFieldToFilter('trans_id', $transId);
        $sellerLists = $sellerFactory->getData();
        $totalCommission = 0;
        foreach ($sellerLists as $list) {
            $totalCommission += $list['total_commission'];
        }

        $row->setTotalCommission($totalCommission);

        return $totalCommission;
    }
}