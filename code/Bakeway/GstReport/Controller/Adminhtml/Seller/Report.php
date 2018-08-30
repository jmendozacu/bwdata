<?php
/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 10-05-2018
 * Time: 16:31
 */

namespace Bakeway\GstReport\Controller\Adminhtml\Seller;


class Report extends \Magento\Backend\App\Action
{

    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $resultPageFactory;

    /**
     * Index constructor.
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
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|\Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Bakeway_GstReport::manage_seller_reports');
        $resultPage->addBreadcrumb(__('Seller Report'), __('Seller Report'));
        $resultPage->getConfig()->getTitle()->prepend(__('Seller Report'));

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