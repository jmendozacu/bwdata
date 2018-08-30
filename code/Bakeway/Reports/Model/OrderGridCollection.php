<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace Bakeway\Reports\Model;

/**
 * Description of OrderGridCollection
 *
 * @author Admin
 */
class OrderGridCollection extends \Magento\Sales\Model\ResourceModel\Order\Grid\Collection
{

    /**
     * Added alias to field
     */
    protected function _initSelect()
    {
        $this->addFilterToMap('created_at', 'main_table.created_at');
        parent::_initSelect();
    }

    /**
     * Added join for city name
     */
    protected function _renderFiltersBefore()
    {
        $select = $this->getSelect();
        $select->joinLeft(array('mo' => 'marketplace_orders'),
                'mo.order_id = main_table.entity_id', array('mo.seller_id'));
        $select->joinLeft(array('bpl' => 'bakeway_partner_locations'),
                'bpl.seller_id = mo.seller_id', array('bpl.city_id'));
        $select->joinLeft(array('bc' => 'bakeway_cities'),
                'bc.id = bpl.city_id', array('bc.name'));
        $select->distinct();
        $startDate = date('Y-m-d H:i:s', strtotime('-30 days'));
        $endDate = date('Y-m-d H:i:s');
        $cond = "main_table.updated_at BETWEEN '".$startDate."' AND '".$endDate."'";
        $this->getSelect()->where($cond);
        $this->setOrder('main_table.increment_id', 'DESC');
        parent::_renderFiltersBefore();
    }

}
