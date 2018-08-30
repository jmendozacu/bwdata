<?php
namespace Bakeway\Partnerlocations\Block\Adminhtml\Customer;
class Locations extends \Magento\Backend\Block\Widget\Grid\Container
{
    /**
     * Constructor
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_controller = 'adminhtml_rangeprice';/*block grid.php directory*/
        $this->_blockGroup = 'Bakeway_Partnerlocations';
        $this->_headerText = __('Store Addresses');
        $this->_addButtonLabel = __('Add New Entry');
        parent::_construct();

    }
}
