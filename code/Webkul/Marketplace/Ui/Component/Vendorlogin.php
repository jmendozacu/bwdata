<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace Webkul\Marketplace\Ui\Component;

/**
 * Description of Vendorlogin
 *
 * @author Admin
 */
class Vendorlogin
        extends \Magento\Ui\Component\Listing\Columns\Column
{

    protected $storeManager;

    /**
     * URL Builder
     * @var \Magento\Framework\UrlInterface 
     */
    protected $urlBuilder;

    /**
     * Constructor
     * @param \Magento\Framework\View\Element\UiComponent\ContextInterface $context
     * @param \Magento\Framework\View\Element\UiComponentFactory $uiComponentFactory
     * @param array $components
     * @param array $data
     */
    public function __construct(
    \Magento\Framework\View\Element\UiComponent\ContextInterface $context,
            \Magento\Framework\View\Element\UiComponentFactory $uiComponentFactory,
            \Magento\Framework\UrlInterface $urlBuilder,
            \Magento\Store\Model\StoreManagerInterface $storeManager,
            array $components = [],
            array $data = []
    )
    {
        $this->storeManager = $storeManager;
        $this->urlBuilder = $urlBuilder;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * 
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {

            foreach ($dataSource['data']['items'] as & $item) {
                if ($item['is_live_ready']) {
                    $item['login'] = "<a href='" . $this->storeManager->getStore()->getBaseUrl() . "vendorlogin/index/index/email/" . base64_encode($item['email']) . "' target='blank' title='" . __('Login As Seller') . "'>Login</a>";
                } else {
                    $item['login'] = 'N/A';
                }
            }
        }

        return $dataSource;
    }

}
