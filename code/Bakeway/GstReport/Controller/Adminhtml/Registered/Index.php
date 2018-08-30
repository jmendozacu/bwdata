<?php
/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 30-04-2018
 * Time: 16:06
 */

namespace Bakeway\GstReport\Controller\Adminhtml\Registered;

class Index extends \Magento\Backend\App\Action
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
        $resultPage->setActiveMenu('Bakeway_GstReport::manage_gstreports_registered');
        $resultPage->addBreadcrumb(__('Registered Report'), __('Registered Report'));
        $resultPage->getConfig()->getTitle()->prepend(__('Registered Report'));

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