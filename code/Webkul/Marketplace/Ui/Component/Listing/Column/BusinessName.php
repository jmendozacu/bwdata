<?php

namespace Webkul\Marketplace\Ui\Component\Listing\Column;

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of BusinessName
 *
 * @author Admin
 */
class BusinessName
        extends \Magento\Ui\Component\Listing\Columns\Column
{

    /**
     *
     * @var \Webkul\Marketplace\Model\Seller 
     */
    protected $seller;

    /**
     * Constructor
     * @param \Magento\Framework\View\Element\UiComponent\ContextInterface $context
     * @param \Magento\Framework\View\Element\UiComponentFactory $uiComponentFactory
     * @param \Webkul\Marketplace\Model\Seller $seller
     * @param array $components
     * @param array $data
     */
    public function __construct(\Magento\Framework\View\Element\UiComponent\ContextInterface $context,
            \Magento\Framework\View\Element\UiComponentFactory $uiComponentFactory,
            \Webkul\Marketplace\Model\Seller $seller,
            array $components = array(),
            array $data = array())
    {
        $this->seller = $seller;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * Get seller business name
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                $seller = $this->seller->getCollection()->addFieldToFilter('seller_id',
                                $item['seller_id'])->getFirstItem();
                $item['business_name'] = $seller->getBusinessName();
            }
        }

        return $dataSource;
    }

}
