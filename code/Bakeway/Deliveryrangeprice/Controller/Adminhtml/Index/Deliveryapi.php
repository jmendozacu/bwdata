<?php
/**
 *
 * Copyright © 2015 Bakewaycommerce. All rights reserved.
 */
namespace Bakeway\Deliveryrangeprice\Controller\Adminhtml\Index;


class Deliveryapi extends \Magento\Framework\App\Action\Action
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

    protected $sellerFactory;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $coreRegistry;
    protected $jsonResultFactory;

    /**
     * Deliveryapi constructor.
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList
     * @param \Magento\Framework\App\Cache\StateInterface $cacheState
     * @param \Magento\Framework\App\Cache\Frontend\Pool $cacheFrontendPool
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Webkul\Marketplace\Model\SellerFactory $sellerFactory
     * @param \Magento\Framework\Controller\Result\JsonFactory $jsonResultFactory
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList,
        \Magento\Framework\App\Cache\StateInterface $cacheState,
        \Magento\Framework\App\Cache\Frontend\Pool $cacheFrontendPool,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Framework\Registry $coreRegistry,
        \Webkul\Marketplace\Model\SellerFactory $sellerFactory,
        \Magento\Framework\Controller\Result\JsonFactory $jsonResultFactory
    )
    {
        parent::__construct($context);
        $this->_cacheTypeList = $cacheTypeList;
        $this->_cacheState = $cacheState;
        $this->_cacheFrontendPool = $cacheFrontendPool;
        $this->resultPageFactory = $resultPageFactory;
        $this->coreRegistry = $coreRegistry;
        $this->sellerFactory = $sellerFactory;
        $this->jsonResultFactory = $jsonResultFactory;

    }

    /**
     * Flush cache storage
     *
     */
    public function execute()
    {
        $_Response = [];
        $_SellerId = $this->getRequest()->getParam('seller_id');
        $_Status = $this->getRequest()->getParam('status');
        $resultPage = $this->jsonResultFactory->create();
        $_obj = $this->sellerFactory->create();
        $_Id = $this->getSellerentityId($_SellerId);
        $_sellerLoad = $_obj->load($_Id);
        $_sellerLoad->setDelivery($_Status);
        try {
            $_sellerLoad->save();
            $_Response['success'] = "Delivery is updated";
        } catch (\Exception $e) {
            $_Response['error'] = $e->getError();
        }

        return $resultPage->setData($_Response);
    }

    public function getSellerentityId($id)
    {
        $_Seller = $this->sellerFactory->create()->getCollection()
            ->addFieldToFilter("seller_id", $id);
        return $_Seller->getFirstItem()->getData('entity_id');

    }
}
