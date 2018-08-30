<?php
/**
 *
 * Copyright © 2015 Bakewaycommerce. All rights reserved.
 */
namespace Bakeway\Deliveryrangeprice\Controller\Freeshippingvalue;

use Magento\Framework\App\RequestInterface;
use Webkul\Marketplace\Helper\Data as Marletplacehelper;


class Freeshippingstatus extends \Magento\Framework\App\Action\Action
{

    /**
     * @var \Magento\Framework\App\Cache\TypeListInterface
     */
    protected $_cacheTypeList;

    /**
     * @var \Magento\Framework\App\Cache\StateInterface
     */
    protected $_cacheState;

    /**
     * @var \Magento\Framework\App\Cache\Frontend\Pool
     */
    protected $_cacheFrontendPool;

    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $resultPageFactory;
    /*
     * @var \Magento\Customer\Model\Session $customerSession
     */
    public $_customerSession;

    /**
     * @var \Webkul\Marketplace\Model\SellerFactory
     */
    protected $webkulmodelFactory;

    /*
     * Marletplacehelper
     */
    protected $marletplacehelper;

    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * Freeshippingstatus constructor.
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList
     * @param \Magento\Framework\App\Cache\StateInterface $cacheState
     * @param \Magento\Framework\App\Cache\Frontend\Pool $cacheFrontendPool
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \Webkul\Marketplace\Model\Seller $webkulmodelFactory
     * @param Marletplacehelper $marletplacehelper
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList,
        \Magento\Framework\App\Cache\StateInterface $cacheState,
        \Magento\Framework\App\Cache\Frontend\Pool $cacheFrontendPool,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Webkul\Marketplace\Model\Seller $webkulmodelFactory,
        Marletplacehelper $marletplacehelper
    )
    {
        parent::__construct($context);
        $this->_cacheTypeList = $cacheTypeList;
        $this->_cacheState = $cacheState;
        $this->_cacheFrontendPool = $cacheFrontendPool;
        $this->resultPageFactory = $resultPageFactory;
        $this->_customerSession = $customerSession;
        $this->webkulmodelFactory = $webkulmodelFactory;
        $this->marletplacehelper = $marletplacehelper;
        $this->resultJsonFactory = $resultJsonFactory;

    }

    /**
     * Check customer authentication.
     *
     * @param RequestInterface $request
     *
     * @return \Magento\Framework\App\ResponseInterface
     */
    public function dispatch(RequestInterface $request)
    {
        $loginUrl = $this->_objectManager->get(
            'Magento\Customer\Model\Url'
        )->getLoginUrl();

        if (!$this->_customerSession->authenticate($loginUrl)) {
            $this->_actionFlag->set('', self::FLAG_NO_DISPATCH, true);
        }

        return parent::dispatch($request);
    }


    /**
     * Flush cache storage
     *
     */
    public function execute()
    {
        $this->resultPage = $this->resultPageFactory->create();
        $this->resultRedirectPage = $this->resultRedirectFactory->create();

        $result = $this->resultJsonFactory->create();

        $status = $this->getRequest()->getParam('free_delivery');

        $customerId = $this->getCustomerId();

        $sellerPrimaryId = $this->marletplacehelper->getSellerEntityId($customerId);

        $sellerObj =  $this->webkulmodelFactory->load($sellerPrimaryId['entity_id']);

        $sellerObj->setIsFreeDelivery($status);

        try {
            $sellerObj->save();
        } catch (\Exception $e) {
           echo $e->getMessage();
        }
        $this->messageManager->addSuccess(__('Updated successfully'));
        $this->_redirect('deliveryrangeprice/delivery/rangeprice?mode=free-delivery');

    }

    /**
     * @return mixed
     */
    public function getCustomerId() {
        return $this->_customerSession->getCustomerId();
    }

}
