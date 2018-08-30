<?php
/**
 *
 * Copyright © 2015 Bakewaycommerce. All rights reserved.
 */
namespace Bakeway\Import\Controller\Adminhtml\Export;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

class Index extends \Magento\Backend\App\Action
{

    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory
    )
    {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
    }


    /**
     * Index action
     *
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {
        $resultPage = $this->resultPageFactory->create();
        $resultPage->addContent(
            $resultPage->getLayout()->createBlock('Bakeway\Import\Block\Adminhtml\Export\Index')
        );
        $resultPage->getConfig()->getTitle()->prepend(__('Export Product Feeds'));
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */

        return $resultPage;
    }


}
