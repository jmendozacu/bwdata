<?php

/**
 * Webkul Software.
 *
 * @category  Webkul
 * @package   Webkul_Marketplace
 * @author    Webkul
 * @copyright Copyright (c) 2010-2017 Webkul Software Private Limited (https://webkul.com)
 * @license   https://store.webkul.com/license.html
 */

namespace Webkul\Marketplace\Controller\Order;

/**
 * Webkul Marketplace Order Invoice Controller.
 */
use Magento\Framework\View\Result\PageFactory;
use Bakeway\Vendorapi\Model\OrderStatus as BakewayOrderStatus;

class Statusupdate extends \Magento\Framework\App\Action\Action {
    /*
     * \Magento\Customer\Model\Session
     */

    protected $customerSesssion;/**
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

    /**
     * @var \Bakeway\Vendorapi\Model\OrderStatus
     */
    protected $bakewayorderstatus;

    /**
     * Statusupdate constructor.
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList
     * @param \Magento\Framework\App\Cache\StateInterface $cacheState
     * @param \Magento\Framework\App\Cache\Frontend\Pool $cacheFrontendPool
     * @param PageFactory $resultPageFactory
     * @param \Bakeway\CommissionLog\Model\CommissionLogFactory $commissionLogFactory
     * @param \Magento\Customer\Model\Session $customerSesssion
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param BakewayOrderStatus $bakewayorderstatus
     */
    public function __construct(
    \Magento\Framework\App\Action\Context $context,
    \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList,
    \Magento\Framework\App\Cache\StateInterface $cacheState,
    \Magento\Framework\App\Cache\Frontend\Pool $cacheFrontendPool,
    \Magento\Framework\View\Result\PageFactory $resultPageFactory,
    \Bakeway\CommissionLog\Model\CommissionLogFactory $commissionLogFactory,
    \Magento\Customer\Model\Session $customerSesssion,
    \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
    BakewayOrderStatus $bakewayorderstatus) {
        parent::__construct($context);
        $this->_cacheTypeList = $cacheTypeList;
        $this->_cacheState = $cacheState;
        $this->_cacheFrontendPool = $cacheFrontendPool;
        $this->resultPageFactory = $resultPageFactory;
        $this->commissionLogCollection = $commissionLogFactory;
        $this->customerSesssion = $customerSesssion;
        $this->productRepository = $productRepository;
        $this->bakewayorderstatus = $bakewayorderstatus;
    }

    /**
     * Marketplace order invoice controller.
     * @return \Magento\Framework\View\Result\Page
     */
    public function execute() {
        $sellerId = $this->customerSesssion->getCustomerId();
        $orderId = $this->getRequest()->getParam("id");
        $orderStatus = $this->getRequest()->getParam("case");
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();

        if (isset($orderId)):

            $vendorapiobj = $objectManager->create('\Bakeway\Vendorapi\Model\Resource\VendorOrderRepository');
            $casestatus = $orderStatus;
            switch ($casestatus):

                case "accepted":
                    $vendorapiobj->vendoracceptOrder($sellerId, $orderId);
                    break;
                case "rejected":
                    $vendorapiobj->vendorrejectOrder($sellerId, $orderId);
                    break;

                case "out_for_delivery":
                    $vendorapiobj->vendorsetOrderStatus($sellerId, $orderId, BakewayOrderStatus::STATUS_ORDER_OUT_FOR_DELIVERY);
                    break;

                case "bakeway_order_ready":
                    $vendorapiobj->vendorsetOrderStatus($sellerId, $orderId, BakewayOrderStatus::STATUS_ORDER_READY);
                    break;

                case "complete":
                    $vendorapiobj->vendorsetOrderStatus($sellerId, $orderId, BakewayOrderStatus::STATUS_ORDER_COMPLETE);
                    break;

                case "default":
                    break;


            endswitch;

            return $this->resultRedirectFactory->create()->setPath(
                            '*/*/view', [
                        'id' => $orderId,
                        '_secure' => $this->getRequest()->isSecure(),
                            ]
            );

        endif;
    }

}
