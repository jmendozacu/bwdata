<?php
namespace Bakeway\Reports\Block\Adminhtml;
class Orders extends \Magento\Backend\Block\Widget\Grid\Container
{
    /**
     * Constructor
     *
     * @return void
     */
    protected function _construct()
    {

        $this->_controller = 'adminhtml_orders';/*block grid.php directory*/
        $this->_blockGroup = 'Bakeway_Reports';
        $this->_headerText = __('Order details report');
        $this->_addButtonLabel = __('Add New Entry');
        parent::_construct();
        $this->removeButton('add');

       $this->buttonList->add(
            'saveandcontinue',
            [
                'label' => __('Export'),
                'class' => 'save',
                'onclick' => 'setLocation(\'' .$this->getUrl('*/*/export'). '\')',
                'style' =>'    background-color: #ba4000; border-color: #b84002; box-shadow: 0 0 0 1px #007bdb;color: #fff;text-decoration: none;'
            ]

        );
        $this->removeButton('saveandcontinue');
    }
}
