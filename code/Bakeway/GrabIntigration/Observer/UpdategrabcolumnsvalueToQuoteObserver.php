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

class UpdategrabcolumnsvalueToQuoteObserver implements ObserverInterface
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
     * UpdategrabcolumnsvalueToQuoteObserver constructor.
     * @param \Magento\Quote\Model\Quote $quoteObject
     */
    public function __construct(
        \Magento\Quote\Model\QuoteRepository $quoteObject,
        \Magento\Catalog\Model\ProductFactory $productobj,
        \Bakeway\HomeDeliveryshipping\Helper\Data $datahelper,
        Grabhelper $grabhelper
    )
    {
        $this->quoteObject = $quoteObject;
        $this->productobj = $productobj;
        $this->homedeliveryhelper = $datahelper;
        $this->grabhelper = $grabhelper;
    }

    /**
     * Set Grab active Flag to quote
     *
     * @param EventObserver $observer
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $quote = $observer->getQuote();
        $quoteId = $quote->getData('entity_id');
        $storeUniqueName = "";
        if(empty($quoteId))
        {
            return;
        }

        $quoteItem = $quote->getAllItems();
        $sellerId = "";


        $storeUniqueName = $quote->getData('store_unique_name');

        foreach ($quoteItem as $quoteItems)
        {
            if ($quoteItems->getParentItem()) {
                continue;
            }
            $parentSku = $quoteItems->getSku();
            $sellerId = $this->homedeliveryhelper->getSelleridFSku($parentSku);
        }

         if(!empty($sellerId))
            {
            $grabSellerStatus = $this->grabhelper->getSellerUniqueStatus($sellerId,$storeUniqueName,'is_grab_active');
            if(!empty($grabSellerStatus)){
                try{
                    $quote->setData('grab_delivery_flag',self::DELIVERY_ON);
                }catch (Exception $e)
                {
                    echo $e->getMessage();
                }
            }
            }
    }
}
