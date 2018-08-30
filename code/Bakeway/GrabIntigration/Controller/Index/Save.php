<?php
/**
 *
 * Copyright © 2015 Bakewaycommerce. All rights reserved.
 */
namespace Bakeway\GrabIntigration\Controller\Index;

use Braintree\Exception;
use Bakeway\Partnerlocations\Model\Partnerlocations as Partnerlocations;

class Save extends \Magento\Framework\App\Action\Action
{
    CONST DELIVERY_OFF = 0;
    CONST DELIVERY_ON = 1;
    CONST GRAB_DELIVERY = 1;
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

    /*
     * @var  Partnerlocations
     */
    protected $partnerlocations;

    /**
     * Save constructor.
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList
     * @param \Magento\Framework\App\Cache\StateInterface $cacheState
     * @param \Magento\Framework\App\Cache\Frontend\Pool $cacheFrontendPool
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Webkul\Marketplace\Model\SellerFactory $sellerFactory
     * @param \Magento\Framework\Controller\Result\JsonFactory $jsonResultFactory
     * @param Partnerlocations $partnerlocations
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList,
        \Magento\Framework\App\Cache\StateInterface $cacheState,
        \Magento\Framework\App\Cache\Frontend\Pool $cacheFrontendPool,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Framework\Registry $coreRegistry,
        \Webkul\Marketplace\Model\SellerFactory $sellerFactory,
        \Magento\Framework\Controller\Result\JsonFactory $jsonResultFactory,
         Partnerlocations $partnerlocations
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
        $this->partnerlocations = $partnerlocations;

    }

   
    public function execute()
    {
        $response = [];
        $sellerId = $this->getRequest()->getParam('seller_id');
        $status = $this->getRequest()->getParam('status');
        $resultPage = $this->jsonResultFactory->create();
        $obj = $this->partnerlocations;
        $sellerObj = $this->sellerFactory->create();
        $id = $this->getSellerentityId($sellerId);
        $sellerLoad = $obj->load($id);

        $sellerLoad->setIsGrabActive($status);

        $userdataEntityId = $this->getSellerentityIdFromUserdataTable($sellerId);
        $sellerUserdataObj = $sellerObj->load($userdataEntityId);
        if(isset($userdataEntityId)){

            if($status == self::GRAB_DELIVERY) {
                $sellerUserdataObj->setDelivery(self::DELIVERY_OFF);
            }else{
                //$sellerLoad->setDelivery(self::DELIVERY_ON);
            }

        }

        try {
            $sellerLoad->save();
            if(isset($userdataEntityId)){
                $sellerUserdataObj->save();
            }
         $response['success'] = "Data is updated";
        } catch (\Exception $e) {
            $_Response['error'] = $e->getError();
        }

        return $resultPage->setData($response);
    }

    /**
     * @param $id
     * @return mixed
     */
    public function getSellerentityId($id)
    {
        $_Seller = $this->partnerlocations->getCollection()
            ->addFieldToFilter("seller_id", $id);
        return $_Seller->getFirstItem()->getData('id');

    }

    /**
     * @param $id
     * @return mixed
     */
    public function getSellerentityIdFromUserdataTable($id)
    {
        $_Seller = $this->sellerFactory->create()->getCollection()
            ->addFieldToFilter("seller_id", $id);
        return $_Seller->getFirstItem()->getData('entity_id');

    }
}
