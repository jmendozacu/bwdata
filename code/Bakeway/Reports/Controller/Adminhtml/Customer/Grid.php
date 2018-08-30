<?php
namespace Bakeway\Reports\Controller\Adminhtml\Customer;


use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;


class Grid extends Action
{
    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    public function execute()
    {
        
        
        $resultLayout = $this->_resultLayoutFactory->create();

        return $resultLayout;
    }

    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Bakeway_Reports::report_custom_sales_orders');
    }
}
