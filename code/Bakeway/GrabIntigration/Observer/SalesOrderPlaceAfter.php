<?php
/**
 * Bakeway
 *
 * @category  Bakeway
 * @package   Bakeway_CustomFee
 * @author    Bakeway
 */
namespace Bakeway\GrabIntigration\Observer;

use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;
use Symfony\Component\Config\Definition\Exception\Exception;
use Bakeway\GrabIntigration\Helper\Data as Grabhelper;
use Webkul\Marketplace\Model\Orders as Orders;

class SalesOrderPlaceAfter implements ObserverInterface
{
    CONST DELIVERY_OFF = 0;
    CONST DELIVERY_ON = 1;
    /**
     * @var \Magento\Quote\Model\Quote
     */
    protected $quoteObject;

    /**
     * @var \Magento\Catalog\Model\ProductFactory
     */
    protected $productobj;
    /*
     * \Bakeway\HomeDeliveryshipping\Helper\Data
     */
    protected $homedeliveryhelper;

    /**
     * @var Grabhelper
     */
    protected $grabhelper;

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected  $objectManager;

    /**
     * Orders
     */
    protected $marketplaceorder;
    /**
     * UpdategrabcolumnsvalueToQuoteObserver constructor.
     * @param \Magento\Quote\Model\Quote $quoteObject
     */
    public function __construct(
        \Magento\Quote\Model\QuoteRepository $quoteObject,
        \Magento\Catalog\Model\ProductFactory $productobj,
        \Bakeway\HomeDeliveryshipping\Helper\Data $datahelper,
        Grabhelper $grabhelper,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        Orders $marketplaceorder
    )
    {
        $this->quoteObject = $quoteObject;
        $this->productobj = $productobj;
        $this->homedeliveryhelper = $datahelper;
        $this->grabhelper = $grabhelper;
        $this->objectManager = $objectManager;
        $this->marketplaceorder = $marketplaceorder;
    }

    /**
     *
     * @param EventObserver $observer
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {

        $storeUniqueName = "";
        $order =  $observer->getEvent()->getOrder();
        $quoteId = $order->getQuoteId();
        $quote = $this->objectManager->create('\Magento\Quote\Model\Quote')
            ->load($quoteId);
        $quoteItem = $quote->getAllItems();
        $sellerId = "";
        foreach ($quoteItem as $quoteItems)
        {
            if ($quoteItems->getParentItem()) {
                continue;
            }
            $parentSku = $quoteItems->getSku();
            $sellerId = $this->homedeliveryhelper->getSelleridFSku($parentSku);
        }

        $storeUniqueName = $quote->getData('store_unique_name');


        if(!empty($sellerId))
        {
            $grabSellerStatus = $this->grabhelper->getSellerUniqueStatus($sellerId,$storeUniqueName,'is_grab_active');


            if(!empty($grabSellerStatus)){
                try{
                    $order->setData('grab_delivery_flag',self::DELIVERY_ON);
                }catch (Exception $e)
                {
                    echo $e->getMessage();
                }
            }
        }

    }
}
