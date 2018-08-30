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
class CustomerName
        extends \Magento\Ui\Component\Listing\Columns\Column
{

    /**
     *
     * @var \Magento\Customer\Model\Customer
     */
    protected $customerModel;

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
            \Magento\Customer\Model\Customer $customerModel,
            array $components = array(),
            array $data = array())
    {
        $this->customerModel = $customerModel;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * Get customer name
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                $customerModel = $this->customerModel->load($item['buyer_id']);
                $item['reviewer_name'] = $customerModel->getFirstname() . ' ' . $customerModel->getLastname();
            }
        }

        return $dataSource;
    }

}
