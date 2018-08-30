<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace Bakeway\Vendorlogin\Controller\Index;

/**
 * Description of Index
 *
 * @author Admin
 */
class Index
        extends \Magento\Framework\App\Action\Action
{

    /**
     *
     * @var \Magento\Customer\Model\Customer 
     */
    protected $_customer;

    /**
     *
     * @var \Magento\Customer\Model\Session 
     */
    protected $_customerSession;

    /**
     * @var PageFactory
     */
    protected $_resultPageFactory;

    /**
     * @param Context     $context
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
    \Magento\Framework\App\Action\Context $context,
            \Magento\Framework\View\Result\PageFactory $resultPageFactory,
            \Magento\Customer\Model\Customer $customer,
            \Magento\Customer\Model\Session $customerSession
    )
    {
        $this->_customer = $customer;
        $this->_customerSession = $customerSession;
        $this->_resultPageFactory = $resultPageFactory;
        parent::__construct($context);
    }

    /**
     * Page
     */
    public function execute()
    {
        $email = $this->getRequest()->getParam('email');
        if (isset($email) && !empty($email)) {
            $this->_customer->setWebsiteId(1);
            $customer = $this->_customer->loadByEmail(base64_decode($email));
            $this->_customerSession->setCustomerAsLoggedIn($customer);            
            $this->_redirect('customer/account/');
        }        
    }

}
