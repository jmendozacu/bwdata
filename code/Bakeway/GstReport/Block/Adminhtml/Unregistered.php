<?php
/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 02-05-2018
 * Time: 13:07
 */

namespace Bakeway\GstReport\Block\Adminhtml;


class Unregistered
    extends \Magento\Backend\Block\Widget\Grid\Container
{

    /**
     * Constructor
     */
    public function _construct()
    {
        $this->_controller = 'adminhtml_unregistered';
        $this->_blockGroup = 'Bakeway_GstReport';
        $this->_headerText = __('Unregistered GST Report');
        parent::_construct();

        $this->removeButton('add');
    }
}