<?php
/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 15-05-2018
 * Time: 12:13
 */

namespace Bakeway\GstReport\Block\Adminhtml;


class Icici extends \Magento\Backend\Block\Widget\Grid\Container
{

    /**
     * Constructor
     */
    public function _construct()
    {
        $this->_controller = 'adminhtml_icici';
        $this->_blockGroup = 'Bakeway_GstReport';
        $this->_headerText = __('ICICI Audit Report');
        parent::_construct();

        $this->removeButton('add');
    }
}