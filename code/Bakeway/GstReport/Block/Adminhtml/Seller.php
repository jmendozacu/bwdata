<?php
/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 10-05-2018
 * Time: 16:49
 */

namespace Bakeway\GstReport\Block\Adminhtml;


class Seller extends \Magento\Backend\Block\Widget\Grid\Container
{

    /**
     * Constructor
     */
    public function _construct()
    {
        $this->_controller = 'adminhtml_seller';
        $this->_blockGroup = 'Bakeway_GstReport';
        $this->_headerText = __('Seller Report');
        parent::_construct();

        $this->removeButton('add');
    }
}