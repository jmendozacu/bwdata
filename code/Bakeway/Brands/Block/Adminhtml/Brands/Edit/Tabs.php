<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace Bakeway\Brands\Block\Adminhtml\Brands\Edit;

/**
 * Description of Tabs
 *
 * @author Admin
 */
class Tabs extends \Magento\Backend\Block\Widget\Tabs
{

    public function _construct()
    {
        parent::_construct();
        $this->setId('brands_edit_tabs');
        $this->setDestElementId('edit_form');
        $this->setTitle(__('Brand Information'));
    }

    protected function _beforeToHtml()
    {
        $this->addTab(
                'brand_info',
                [
            'label' => __('General'),
            'title' => __('General'),
            'content' => $this->getLayout()->createBlock(
                    'Bakeway\Brands\Block\Adminhtml\Brands\Edit\Tab\Info'
            )->toHtml(),
            'active' => true
                ]
        );

        return parent::_beforeToHtml();
    }

}
