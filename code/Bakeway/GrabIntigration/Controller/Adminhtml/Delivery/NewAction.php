<?php
namespace Bakeway\Deliveryrangeprice\Controller\Adminhtml\Delivery;


class NewAction extends \Magento\Backend\App\Action
{
    public function execute()
    {
        $this->_forward('edit');
    }
}
