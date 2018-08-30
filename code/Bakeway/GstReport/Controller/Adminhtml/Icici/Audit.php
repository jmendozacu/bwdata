<?php
/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 15-05-2018
 * Time: 12:06
 */

namespace Bakeway\GstReport\Controller\Adminhtml\Icici;


use Magento\Backend\App\Action;

class Audit extends \Magento\Backend\App\Action
{
    /**
     * @var $resultPageFactory
     */
    protected $resultPageFactory;

    /**
     * Audit constructor.
     * @param Action\Context $context
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory
    )
    {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|void
     */
    public function execute()
    {
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Bakeway_GstReport::manage_icici_audit_report');
        $resultPage->addBreadcrumb(__('ICICI Audit Report'), __('ICICI Audit Report'));
        $resultPage->getConfig()->getTitle()->prepend(__('ICICI Audit Report'));

        return $resultPage;
    }

    /**
     * @return bool
     */
    public function _isAllowed()
    {
        return $this->_authorization->isAllowed('Bakeway_GstReport::manage_gstreports');
    }

}