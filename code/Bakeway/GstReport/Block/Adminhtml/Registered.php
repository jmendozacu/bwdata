<?php
/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 02-05-2018
 * Time: 13:07
 */

namespace Bakeway\GstReport\Block\Adminhtml;


class Registered
    extends \Magento\Backend\Block\Widget\Grid\Container
{

    /**
     * Constructor
     */
    public function _construct()
    {
        $this->_controller = 'adminhtml_registered';
        $this->_blockGroup = 'Bakeway_GstReport';
        $this->_headerText = __('Registered GST Report');
        parent::_construct();

        $this->removeButton('add');
    }
}