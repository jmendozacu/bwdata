<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace Webkul\Marketplace\Model\Config\Source;

/**
 * Description of Cities
 *
 * @author Admin
 */
class Cities implements \Magento\Framework\Option\ArrayInterface
{

    /** @var \Bakeway\Cities\Model\Cities */
    protected $cities;

    /**
     * Construct
     * @param \Bakeway\Cities\Model\Cities $citiesCollection
     */
    public function __construct(\Bakeway\Cities\Model\Cities $citiesCollection)
    {
        $this->cities = $citiesCollection;
    }

    /**
     * City Data
     * @return array
     */
    public function toOptionArray()
    {
        $cityCollection = $this->cities->getCollection();
        $cities = $cityCollection->getData();
        $data = array();
        foreach ($cities as $city) {
            array_push($data,
                    ['value' => $city['id'], 'label' => __($city['name'])]);
        }
        return $data;
    }

}
