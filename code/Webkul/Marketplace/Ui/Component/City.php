<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace Webkul\Marketplace\Ui\Component;

/**
 * Description of City
 *
 * @author Admin
 */
class City extends \Magento\Ui\Component\Listing\Columns\Column
{

    /** @var \Bakeway\Cities\Model\Cities */
    protected $cities;

    /**
     * Construct
     * @param \Magento\Framework\View\Element\UiComponent\ContextInterface $context
     * @param \Magento\Framework\View\Element\UiComponentFactory $uiComponentFactory
     * @param \Bakeway\Cities\Model\Cities $citiesCollection
     * @param array $components
     * @param array $data
     */
    public function __construct(\Magento\Framework\View\Element\UiComponent\ContextInterface $context,
            \Magento\Framework\View\Element\UiComponentFactory $uiComponentFactory,
            \Bakeway\Cities\Model\Cities $citiesCollection,
            array $components = array(),
            array $data = array())
    {
        parent::__construct($context, $uiComponentFactory, $components, $data);
        $this->cities = $citiesCollection;
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
                if (isset($item['store_city']) && !empty($item['store_city'])) {
                    $cityModel = $this->cities->load($item['store_city']);
                    $item['store_city'] = $cityModel->getName();
                }
            }
        }
        return $dataSource;
    }

}
