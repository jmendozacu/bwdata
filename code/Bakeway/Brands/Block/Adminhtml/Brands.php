<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace Bakeway\Brands\Block\Adminhtml;

/**
 * Description of Brands
 *
 * @author Admin
 */
class Brands extends \Magento\Backend\Block\Widget\Grid\Container
{

    /**
     * Construct
     */
    public function _construct()
    {
        $this->_controller = 'adminhtml_brands';
        $this->_blockGroup = 'Bakeway_Brands';
        $this->_headerText = __('Bakeway Brands');
        parent::_construct();
        $this->removeButton('add');

        $this->buttonList->add(
                'add_brand',
                [
            'label' => __('Add Brand'),
            'class' => 'save',
            'onclick' => 'setLocation(\'' . $this->getUrl('*/*/addbrand') . '\')',
            'style' => '    background-color: #ba4000; border-color: #b84002; box-shadow: 0 0 0 1px #007bdb;color: #fff;text-decoration: none;'
                ]
        );
        $this->removeButton('saveandcontinue');
    }

}
