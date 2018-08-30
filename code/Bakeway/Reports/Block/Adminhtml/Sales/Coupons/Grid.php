<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace Bakeway\Reports\Block\Adminhtml\Sales\Coupons;

/**
 * Description of Grid
 *
 * @author Admin
 */
class Grid
        extends \Magento\Reports\Block\Adminhtml\Sales\Coupons\Grid
{

    /**
     * Function to add city column
     */
    protected function _prepareColumns()
    {
        parent::_prepareColumns();

        $this->addColumn(
                'city',
                [
            'header' => __('City'),
            'sortable' => false,
            'index' => 'city',
            'renderer' => 'Bakeway\Reports\Block\Adminhtml\Sales\Coupons\Renderer\City',
            'header_css_class' => 'col-total',
            'column_css_class' => 'col-total'
                ]
        );
    }

}
