<?php
/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 24-04-2018
 * Time: 15:30
 */

namespace Bakeway\ImportReviews\Controller\Adminhtml\Index;


use Magento\Backend\App\Action;

class Index extends \Magento\Backend\App\Action
{

    /**
     * Page Factory Object
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $resultPageFactory;

    /**
     * Index constructor.
     * @param Action\Context $context
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     */
    public function __construct(\Magento\Backend\App\Action\Context $context, \Magento\Framework\View\Result\PageFactory $resultPageFactory)
    {
        $this->resultPageFactory = $resultPageFactory;
        parent::__construct($context);
    }

    /**
     * Execute Method
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|\Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Bakeway_ImportReviews::manage_importratings');
        $resultPage->addBreadcrumb(__('Import Reviews Ratings'), __('Import Reviews Ratings'));
        $resultPage->getConfig()->getTitle()->prepend(__('Import Reviews Ratings'));

        return $resultPage;
    }

    /**
     * @return bool
     */
    public function _isAllowed()
    {
        return $this->_authorization->isAllowed('Bakeway_ImportReviews::manage_importratings');
    }
}