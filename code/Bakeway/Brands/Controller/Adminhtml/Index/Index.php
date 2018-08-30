<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace Bakeway\Brands\Controller\Adminhtml\Index;

/**
 * Description of Index
 *
 * @author Admin
 */
class Index extends \Magento\Backend\App\Action
{

    /** @var \Magento\Framework\View\Result\PageFactory */
    protected $resultPageFactory;

    /*
     * Construct
     */

    public function __construct(\Magento\Backend\App\Action\Context $context,
            \Magento\Framework\View\Result\PageFactory $resultPageFactory)
    {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
    }

    /**
     * Grid
     */
    public function execute()
    {
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Webkul_Marketplace::brands');
        $resultPage->getConfig()->getTitle()->prepend(__('Manage Brands'));
        return $resultPage;
    }

}
