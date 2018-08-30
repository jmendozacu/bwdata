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
class City
        extends \Magento\Ui\Component\Listing\Columns\Column
{

    /**
     *
     * @var \Bakeway\Partnerlocations\Model\Partnerlocations
     */
    protected $partnerLocation;

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
            \Bakeway\Partnerlocations\Model\Partnerlocations $partnerLocation,
            array $components = array(),
            array $data = array())
    {
        $this->partnerLocation = $partnerLocation;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * Get seller city name
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                $collection = $this->partnerLocation
                        ->getCollection()
                        ->addFieldToFilter('seller_id', $item['seller_id']);
                $collection->getSelect()->joinInner(array('bc' => 'bakeway_cities'),
                        'main_table.city_id = bc.id', array('bc.name'));
                $data = $collection->getFirstItem();
                $item['seller_city'] = $data->getData('name');
            }
        }

        return $dataSource;
    }

}
