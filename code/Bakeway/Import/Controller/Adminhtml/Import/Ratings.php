<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace Bakeway\Import\Controller\Adminhtml\Import;

/**
 * Description of Ratings
 *
 * @author Admin
 */
class Ratings extends \Magento\Backend\App\Action
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
    \Magento\Backend\App\Action\Context $context,
            \Magento\Framework\View\Result\PageFactory $resultPageFactory
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
                $resultPage->getLayout()->createBlock('Bakeway\Import\Block\Adminhtml\Import\Ratings')
        );
        $resultPage->getConfig()->getTitle()->prepend(__('Import Ratings'));
        return $resultPage;
    }

}
