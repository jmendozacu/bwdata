<?php

/**
 * Copyright © 2015 Bakeway. All rights reserved.
 */

namespace Bakeway\HomeDeliveryshipping\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Webkul\Marketplace\Helper\Data as MarketplaceHelper;
use Bakeway\Partnerlocations\Model\ResourceModel\Partnerlocations\CollectionFactory as LocationsCollection;
use Bakeway\GrabIntigration\Helper\Data as Grabhelper;
use Magento\Checkout\Block\Cart\Item\Renderer as Renderer;
use Magento\Catalog\Api\ProductRepositoryInterface as ProductRepositoryInterface;
use Magento\Catalog\Api\CategoryRepositoryInterface as CategoryRepositoryInterface;
use Bakeway\Deliveryrangeprice\Helper\Data as DeliveryrangeHelper;
use Magento\Framework\Event\Manager as Eventmanager;

class Homedelivery implements ObserverInterface {

    CONST DELIVERYMETHOD = "bakewayhomedelivery_bakewayhomedelivery";
    CONST DELIVERYEXTRAKM = "1";
    CONST WEIGHT_ATTRIBUTE_NAME = 'cake_weight';
    CONST GRAB_START_DELIVERY_DISTANCE = "4"; //in km
    CONST ADD_ON_SCAT = "Add ons";

    CONST FREE_SHIPPING_METHOD = "freeshipping";

    /**
     * @var ObjectManagerInterface
     */
    protected $_objectManager;

    /**
     * @var registry
     */
    protected $_registry;

    /**
     * @var \Magento\Quote\Model\Quote\Address\TotalFactory
     */
    protected $totalFactory;

    /**
     * @param   \Bakeway\HomeDeliveryshipping\Model\Carrier
     */
    protected $carrier;

    /**
     * @param   \Magento\Quote\Model\Quote\Address\RateResult
     */
    protected $MethodFactory;

    /**
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     */

    /**
     * @var PriceCurrencyInterface
     */
    protected $priceCurrency;

    /**
     * @var \Bakeway\HomeDeliveryshipping\Helper\Data
     */
    protected $_homedeliveryhelper;

    /**
     * @var \Bakeway\Deliveryrangeprice\Model\RangepriceFactory
     */
    protected $rangepriceFactory;

    /**
     * @var MarketplaceHelper
     */
    protected $marketplaceHelper;

    /**
     * @var LocationsCollection
     */
    protected $locationsCollection;
    /**
     * @var Grabhelper
     */
    protected $grabhelper;
    /**
     * @var Renderer
     */
    protected $rendererhelper;
    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepositoryInterface;
    /**
     * @var \Magento\Catalog\Model\ProductFactory
     */
    protected $productobj;

    /**
     * @var CategoryRepositoryInterface
     */
    protected $categoryRepositoryInterface;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Magento\Framework\Webapi\Rest\Request
     */
    protected $webApiRequest;

    /**
     * Homedelivery constructor.
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Quote\Model\Quote\Address\TotalFactory $totalFactory
     * @param \Bakeway\HomeDeliveryshipping\Model\Carrier $carrier
     * @param \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory $method
     * @param PriceCurrencyInterface $priceCurrency
     * @param \Bakeway\HomeDeliveryshipping\Helper\Data $datahelper
     * @param \Bakeway\Deliveryrangeprice\Model\RangepriceFactory $rangepriceFactory
     * @param MarketplaceHelper $marketplaceHelper
     * @param LocationsCollection $locationsCollection
     * @param Grabhelper $grabhelper
     * @param Renderer $rendererhelper
     * @param ProductRepositoryInterface $productRepositoryInterface
     * @param \Magento\Catalog\Model\ProductFactory $productobj
     * @param CategoryRepositoryInterface $categoryRepositoryInterface
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param DeliveryrangeHelper $deliveryrangeHelper
     * @param \Magento\Framework\Webapi\Rest\Request $webApiRequest
     */
    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Framework\Registry $registry,
        \Magento\Quote\Model\Quote\Address\TotalFactory $totalFactory,
        \Bakeway\HomeDeliveryshipping\Model\Carrier $carrier,
        \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory $method,
        PriceCurrencyInterface $priceCurrency,
        \Bakeway\HomeDeliveryshipping\Helper\Data $datahelper,
        \Bakeway\Deliveryrangeprice\Model\RangepriceFactory $rangepriceFactory,
        MarketplaceHelper $marketplaceHelper,
        LocationsCollection $locationsCollection,
        Grabhelper $grabhelper,
        Renderer $rendererhelper,
        ProductRepositoryInterface $productRepositoryInterface,
        \Magento\Catalog\Model\ProductFactory $productobj,
        CategoryRepositoryInterface $categoryRepositoryInterface,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        DeliveryrangeHelper $deliveryrangeHelper,
        Eventmanager $eventmanager,
        \Magento\Framework\Webapi\Rest\Request $webApiRequest
    ) {
        $this->_objectManager = $objectManager;
        $this->_registry = $registry;
        $this->totalFactory = $totalFactory;
        $this->carrier = $carrier;
        $this->MethodFactory = $method;
        $this->priceCurrency = $priceCurrency;
        $this->_homedeliveryhelper = $datahelper;
        $this->rangepriceFactory = $rangepriceFactory;
        $this->marketplaceHelper = $marketplaceHelper;
        $this->locationsCollection = $locationsCollection;
        $this->grabhelper = $grabhelper;
        $this->rendererhelper = $rendererhelper;
        $this->productRepositoryInterface = $productRepositoryInterface;
        $this->productobj = $productobj;
        $this->categoryRepositoryInterface = $categoryRepositoryInterface;
        $this->scopeConfig = $scopeConfig;
        $this->deliveryrangeHelper = $deliveryrangeHelper;
        $this->eventmanager = $eventmanager;
        $this->webApiRequest = $webApiRequest;
    }

    public function execute(\Magento\Framework\Event\Observer $observer) {

        $requestPath = $this->webApiRequest->getPathInfo();

       /* if (strpos($requestPath, '/create-order') !== false) {
            return;
        }
        if (strpos($requestPath, '/order-create') !== false) {
            return;
        }*/

        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/shipping_log.log');
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);
        $logger->info('---------------------------------------------------------------------------------');
        $logger->info('-------------------inside in shipping observer process get started---------------');
        $logger->info('---------------------------------------------------------------------------------');

        $bakeryType = false;
        $freeshippingFlag = false;
        $minDistance = "";

        $corsOriginUrl = $this->scopeConfig->getValue('web/corsRequests/origin_url',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE);

        $shippingAssignment = $observer->getShippingAssignment();
        $address = $shippingAssignment->getShipping()->getAddress();
        $method = $shippingAssignment->getShipping()->getMethod();
        $quote = $observer->getQuote();


        $logger->info('---------quote '.$quote->getId().' assigned--------');
        $address = $shippingAssignment->getShipping()->getAddress();
        $errorMessage = [];
        $_QuoteDeliveryType = $quote->getDeliveryType();
        if ($_QuoteDeliveryType == 'home') {
            /* calling extension attributes start */
            $logger->info($quote->getId().' --inserted in home deleivery type--');
            $extensionAttributes = $address->getExtensionAttributes();
            $_setLatitude = $_setLongtitude = $_setProductsku = "";
            if (!empty($address->getExtensionAttributes())) {
                $extensionAttributes = $address->getExtensionAttributes();
                $_setLatitude = $extensionAttributes->getLatitude();
                $_setLongtitude = $extensionAttributes->getLongtitude();
                $_setProductsku = $extensionAttributes->getSku();
                $logger->info($quote->getId().' latitude val '.$_setLatitude.' longitude val '.$_setLongtitude.'and product-sku '.$_setProductsku);
            }
            /* calling extension attributes end */

            /** @varw \Magento\Quote\Model\Quote\Address $address */
            foreach ($observer->getTotal() as $totel) {
                $addressTotal = $this->collectAddressTotals($quote, $address);
            }

            $address->collectShippingRates();

            $key = $this->getMapKey();
            $_sellerid = $this->_homedeliveryhelper->getSelleridFSku($_setProductsku);
            $logger->info($quote->getId(). '---------------seller-id '.$_sellerid);
            $_selleraddress = ['lat'=>'00.000000', 'long'=>'00.000000'];
            /******************* Single Login Delivery Fee Calculation START *********************/
            if ($quote->getItems() && $_sellerid) {
                $isConglomerate = $this->marketplaceHelper->isConglomerate($_sellerid);
                $locationColl = $this->locationsCollection->create()
                    ->addFieldToFilter('seller_id', $_sellerid);
                if ($isConglomerate === true) {
                    $logger->info($quote->getId().' seller '.$_sellerid.' is conglomerate');
                    $storeUniqueName = $quote->getData('store_unique_name');
                    $logger->info($quote->getId().' store unique name  '.$storeUniqueName.' has fechted');
                    if (trim($storeUniqueName) != '' && $storeUniqueName != Null) {
                        $locationColl->addFieldToFilter('store_unique_name', trim($storeUniqueName));
                        if ($locationColl->count() > 0) {
                            $location = $locationColl->getFirstItem();
                            $_selleraddress['lat'] = $location->getData('store_latitude');
                            $_selleraddress['long'] = $location->getData('store_longitude');
                            $logger->info($quote->getId().' latitude '.$_selleraddress['lat'] .' and longitude is '.$_selleraddress['long']);
                        } else {
                            $this->grabhelper->getBadrequestExpection('No location found for this store unique name.');
                        }
                    } else {
                        $logger->info($quote->getId().' Store unique name not present in quote store name is '.$storeUniqueName);
                        $this->grabhelper->getBadrequestExpection('Store unique name not present in quote.');
                    }
                } else {
                    if ($locationColl->count() > 0) {
                        $location = $locationColl->getFirstItem();
                        $_selleraddress['lat'] = $location->getData('store_latitude');
                        $_selleraddress['long'] = $location->getData('store_longitude');
                        $logger->info($quote->getId().' single store seller is '. $_sellerid. "and store lat". $_selleraddress['lat']  .'and long'.$_selleraddress['long']);
                    } else {
                        $logger->info($quote->getId().' location is not found for single seller .'.$_sellerid);
                        $this->grabhelper->getBadrequestExpection('Location is not present for this seller.');
                    }
                    //$_selleraddress = $this->getSelleraddressData($_sellerid);
                }
            }

            /******************* Single Login Delivery Fee Calculation END *********************/

            $_DeliveryStatus = $this->getSelleredeliveryStatus($_sellerid);

            /** seller delivery status**/
            $sellerFreeShippingFlag = $this->deliveryrangeHelper->getSellerFreeShippingFlag($_sellerid);

            /**grab intigration start**/

            $grabGlobalStatus = $weightAttributeValue = $grabSellerStatus = $taxAmount = "";

            $isConglomerate = $this->marketplaceHelper->isConglomerate($_sellerid);


            $grabGlobalStatus = $this->grabhelper->getGrabGlobalStatus();

            $storeUniqueName = $quote->getData('store_unique_name');

            if(!empty($isConglomerate)){
                $grabSellerStatus =  $this->grabhelper->getSellerStoreGrabStatus($_sellerid,$storeUniqueName);
            }else{
                $grabSellerStatus =  $this->grabhelper->getNonxonglomerateSellerGrabStatus($_sellerid);
            }

            $logger->info('---------quote '.$quote->getId().' assigned--------');
            $logger->info($quote->getId().' seller grab global status '.$grabGlobalStatus);
            $logger->info($quote->getId().' seller grab status '.$grabSellerStatus);
            $logger->info($quote->getId().' Store unique name '.$storeUniqueName);
            /**
             * check bakery type and global check
             */

            $homebakerGlobalStatus =  $this->_homedeliveryhelper->getHomeBakersFreeShippingStatus();

            $bakeryType = $this->_homedeliveryhelper->getBakeryType($_sellerid);

            if(!empty($bakeryType))
            {
                $bakeryType = true;
                $logger->info($quote->getId() .' Bakery type '.$bakeryType.' for seller(homebakers) '.$_sellerid);
            }

            if(!empty($grabGlobalStatus) && !empty($grabSellerStatus))
            {
                $logger->info('--inside grab calculation----');
                $grabBaseAmount =  $this->grabhelper->getGrabbaseDiscount();
                $grabSurplusAmountDistance = $this->grabhelper->getGrabSurplusAmoutForDistance();
                $grabFinalweightForMultiplication = $this->grabhelper->getWeightForapplyMultiplication();
                $grabSurplusAmountWeight =  $this->grabhelper->getGrabSurplusAmoutForWeight();
                $grabUpperKmLimit  = $this->grabhelper->getGrabUpperLimitinKms();
                $grabshippingTax = $this->grabhelper->getGrabTax();
                $parentProductSku = "";
                $quoteItem = $quote->getAllItems();


                foreach ($quoteItem as $quoteItems)
                {
                    if ($quoteItems->getParentItem()) {
                        $parentProductSku = $quoteItems->getSku();
                        continue;
                    }else{
                        $simpleProdsku =  $quoteItems->getSku();
                        $prodData =  $this->productobj->create()->loadByAttribute('sku',$simpleProdsku);
                        if($prodData->getTypeId() === 'simple'){
                            $prodCatId =  $prodData->getCategoryIds();
                            foreach($prodCatId as $catid)
                            {
                                $catData = $this->categoryRepositoryInterface->get($catid);
                                $catName = $catData->getName();
                                if($catName != self::ADD_ON_SCAT)
                                {
                                    $parentProductSku = $prodData->getSku();
                                }

                            }
                        }
                    }

                }

                $logger->info('sku assigned '.$parentProductSku.' to quote id is '.$quote->getId());

                $logger->info('---------quote '.$quote->getId().' assigned--------');
                /**
                 * throw error when product sku dosent found
                 */
                if(empty($parentProductSku))
                {
                    $this->grabhelper->getBadrequestExpection('No Product Found.');
                }

                $productData =  $this->productobj->create()->loadByAttribute('sku',$parentProductSku);

                $weightAttributeValue  = $productData->getAttributeText(self::WEIGHT_ATTRIBUTE_NAME);

                $logger->info($quote->getId().' weight attribute value is '.$weightAttributeValue. ' for product '.$parentProductSku);

                //$weightAttributeValue = 1.6;

                /**
                 * commenting code according discussion with shrikant @14-02-2018
                 */
                /*if(!empty($_setLatitude) && !empty($_setLongtitude) && !empty($_selleraddress['lat']) && !empty($_selleraddress['long']))
                {
                    $areaDistance = $this->_homedeliveryhelper->getDistance($_setLatitude, $_setLongtitude, $_selleraddress['lat'],  $_selleraddress['long'], $key);

                    $areaDistance = 4.1;
                    if($areaDistance <= self::GRAB_START_DELIVERY_DISTANCE)
                    {
                        $grabShippingAmount = $grabBaseAmount;

                        if($weightAttributeValue >= $grabFinalweightForMultiplication)
                        {
                            $grabShippingAmount  = $grabShippingAmount * $grabSurplusAmountWeight;
                        }

                        if(!empty($grabshippingTax)){

                            $taxAmount = $this->grabhelper->grabTaxCalculation($grabShippingAmount);
                            $grabShippingAmount  =  $grabShippingAmount + $taxAmount;
                        }
                        if (!empty($areaDistance)) {
                               foreach ($address->getAllShippingRates() as $rate) {
                                   if ($rate->getCode() == self::DELIVERYMETHOD) {
                                       $store = $quote->getStore();
                                       /* function to return delivery fees calculation */
                /*$_checkProduct = $this->_homedeliveryhelper->getSelleridFSku($_setProductsku);
                if (!empty($_checkProduct)) {
                    $rate->setPrice($grabShippingAmount);
                }
                $observer->getTotal()->setTotalAmount($rate->getCode(), $grabShippingAmount);
                $observer->getTotal()->setBaseTotalAmount($rate->getCode(), $grabShippingAmount);
                $shippingDescription = $rate->getCarrierTitle() . ' - ' . $rate->getMethodTitle();
                $address->setShippingDescription(trim($shippingDescription, ' -'));
                $observer->getTotal()->setBaseShippingAmount($grabShippingAmount);
                $observer->getTotal()->setShippingAmount($grabShippingAmount);
                $observer->getTotal()->setShippingDescription($address->getShippingDescription());
                /*
                 * set grand total and base grand total
                 */
                /*$_getSubTotal = $observer->getTotal()->getGrandTotal();
                $observer->getTotal()->setGrandTotal($_getSubTotal + $grabShippingAmount);
                $observer->getTotal()->setBaseGrandTotal($_getSubTotal + $grabShippingAmount);
                /* function to return delivery fees calculation */
                /* break;
                         }
                     }
                 }
                }elseif($areaDistance > self::GRAB_START_DELIVERY_DISTANCE && $areaDistance <= $grabUpperKmLimit )
                {
                   $grabShippingAmount = $grabBaseAmount + $grabSurplusAmountDistance;

                  if($weightAttributeValue > $grabFinalweightForMultiplication)
                  {
                      $grabShippingAmount  = $grabShippingAmount * $grabSurplusAmountWeight;
                  }

                  if(!empty($grabshippingTax)){

                      $taxAmount = $this->grabhelper->grabTaxCalculation($grabShippingAmount);
                      $grabShippingAmount  =  $grabShippingAmount + $taxAmount;
                  }

                  if (!empty($areaDistance)) {
                      foreach ($address->getAllShippingRates() as $rate) {
                          if ($rate->getCode() == self::DELIVERYMETHOD) {
                              $store = $quote->getStore();
                              /* function to return delivery fees calculation */
                /* $_checkProduct = $this->_homedeliveryhelper->getSelleridFSku($_setProductsku);
                 if (!empty($_checkProduct)) {
                     $rate->setPrice($grabShippingAmount);
                 }
                 $observer->getTotal()->setTotalAmount($rate->getCode(), $grabShippingAmount);
                 $observer->getTotal()->setBaseTotalAmount($rate->getCode(), $grabShippingAmount);
                 $shippingDescription = $rate->getCarrierTitle() . ' - ' . $rate->getMethodTitle();
                 $address->setShippingDescription(trim($shippingDescription, ' -'));
                 $observer->getTotal()->setBaseShippingAmount($grabShippingAmount);
                 $observer->getTotal()->setShippingAmount($grabShippingAmount);
                 $observer->getTotal()->setShippingDescription($address->getShippingDescription());
                 /*
                  * set grand total and base grand total
                  */
                /*  $_getSubTotal = $observer->getTotal()->getGrandTotal();
                  $observer->getTotal()->setGrandTotal($_getSubTotal + $grabShippingAmount);
                  $observer->getTotal()->setBaseGrandTotal($_getSubTotal + $grabShippingAmount);
                  /* function to return delivery fees calculation */
                /*break;
            }
        }
    }

}
else{
    $this->grabhelper->getBadrequestExpection('Delivery is not available for this area.');
}

}
*/
                $logger->info($quote->getId(). ' start cal based on lat and long');
                $logger->info($quote->getId().' lat '.$_setLatitude.' and long' .$_setLongtitude.' seller lat '.$_selleraddress['lat'].' and long val is '.$_selleraddress['long']);

                if(!empty($_setLatitude) && !empty($_setLongtitude) && !empty($_selleraddress['lat']) && !empty($_selleraddress['long']))
                {
                    $logger->info($quote->getId() .' insided cal based on lat and long');

                    $areaDistance = $this->_homedeliveryhelper->getDistance($_setLatitude, $_setLongtitude, $_selleraddress['lat'],  $_selleraddress['long'], $key);

                    //$areaDistance = 4.1;

                    $logger->info($quote->getId() .' area distance is '.$areaDistance);

                    $grabMaxDeliveryLimit = $this->grabhelper->getGrabUpperLimitinKms();

                    if($areaDistance <= $grabMaxDeliveryLimit)
                    {
                        $grabShippingAmount = $grabshippingTax;

                        if($weightAttributeValue > $grabFinalweightForMultiplication)
                        {
                            $grabShippingAmount  = $grabShippingAmount * $grabSurplusAmountWeight;
                        }


                        $logger->info($quote->getId() .' grab shiiping amount for '.$grabShippingAmount. ' for quote id '.$quote->getId() );

                        if($bakeryType === true && !empty($homebakerGlobalStatus)){
                            $grabShippingAmount = 0;
                            $logger->info($quote->getId() .' grab shipping amount zero when is home bakers '.$grabShippingAmount);
                        }

                        $logger->info('---------quote '.$quote->getId().' assigned--------');
                        if (!empty($areaDistance)) {


                            foreach ($address->getAllShippingRates() as $rate) {
                                if ($rate->getCode() == self::DELIVERYMETHOD) {
                                    $store = $quote->getStore();
                                    /* function to return delivery fees calculation */
                                    $_checkProduct = $this->_homedeliveryhelper->getSelleridFSku($_setProductsku);
                                    if (!empty($_checkProduct)) {
                                        $rate->setPrice($grabShippingAmount);
                                    }
                                    $observer->getTotal()->setTotalAmount($rate->getCode(), $grabShippingAmount);
                                    $observer->getTotal()->setBaseTotalAmount($rate->getCode(), $grabShippingAmount);
                                    $shippingDescription = $rate->getCarrierTitle() . ' - ' . $rate->getMethodTitle();
                                    $address->setShippingDescription(trim($shippingDescription, ' -'));
                                    $observer->getTotal()->setBaseShippingAmount($grabShippingAmount);
                                    $observer->getTotal()->setShippingAmount($grabShippingAmount);
                                    $observer->getTotal()->setShippingDescription($address->getShippingDescription());
                                    /*
                                     * set grand total and base grand total
                                     */
                                    $_getSubTotal = $observer->getTotal()->getGrandTotal();
                                    $observer->getTotal()->setGrandTotal($_getSubTotal + $grabShippingAmount);
                                    $observer->getTotal()->setBaseGrandTotal($_getSubTotal + $grabShippingAmount);
                                    /* function to return delivery fees calculation */

                                    $logger->info($quote->getId() .' actual shipping cost '.$grabShippingAmount);
                                    break;
                                }
                            }
                        }
                    }

                    else{
                        /*
                         * check sub store in case of grab
                         */
                        if($isConglomerate === true){
                            $logger->info($quote->getId(). "-------------check sub store in case ob grab start------------------");
                            $storeShippingArray = ["sellerid"=>$_sellerid,"customerlat"=>$_setLatitude,"customerlong"=>$_setLongtitude,"savedStorename"=>$storeUniqueName,"quote"=>$quote->getEntityId(),"grab"=>true];
                            $this->eventmanager->dispatch("store_check_for_shipping",["store-shipping"=> $storeShippingArray]);
                            if(($this->_registry->registry('min_location_distance') >= 0 && $quote->getData('store_unique_name') != '')) {

                                $areaDistance = $this->_registry->registry("min_location_distance");
                                $logger->info($quote->getId(). "-------------check sub store in case ob grab  area distnace is ".$areaDistance);


                                if($areaDistance <= $grabMaxDeliveryLimit) {
                                    $grabShippingAmount = $grabshippingTax;

                                    if ($weightAttributeValue > $grabFinalweightForMultiplication) {
                                        $grabShippingAmount = $grabShippingAmount * $grabSurplusAmountWeight;
                                    }


                                    $logger->info($quote->getId().' case : grab event in case of other store - grab shiiping amount for ' . $grabShippingAmount . ' for quote id ' . $quote->getId());

                                    if ($bakeryType === true && !empty($homebakerGlobalStatus)) {
                                        $grabShippingAmount = 0;
                                        $logger->info($quote->getId().' case : grab event in case of other store - grab shipping amount zero when is home bakers ' . $grabShippingAmount);
                                    }
                                }
                                else {
                                    $this->grabhelper->getBadrequestExpection('Delivery is not available for this area.','DELIVERY_NOT_AVAILABLE');
                                }
                                $logger->info($quote->getId(). " -------------check sub store in case ob grab  area distnace is ".$areaDistance." and shiiping amout is ".$grabShippingAmount);

                                if (!empty($areaDistance)) {
                                    $logger->info($quote->getId(). " -------------check sub store in case ob grab  area distnace is ".$areaDistance." and come inside condition of delivery calculation");
                                    foreach ($address->getAllShippingRates() as $rate) {
                                        if ($rate->getCode() == self::DELIVERYMETHOD) {
                                            $store = $quote->getStore();
                                            /* function to return delivery fees calculation */
                                            $_checkProduct = $this->_homedeliveryhelper->getSelleridFSku($_setProductsku);
                                            if (!empty($_checkProduct)) {
                                                $rate->setPrice($grabShippingAmount);
                                            }
                                            $observer->getTotal()->setTotalAmount($rate->getCode(), $grabShippingAmount);
                                            $observer->getTotal()->setBaseTotalAmount($rate->getCode(), $grabShippingAmount);
                                            $shippingDescription = $rate->getCarrierTitle() . ' - ' . $rate->getMethodTitle();
                                            $address->setShippingDescription(trim($shippingDescription, ' -'));
                                            $observer->getTotal()->setBaseShippingAmount($grabShippingAmount);
                                            $observer->getTotal()->setShippingAmount($grabShippingAmount);
                                            $observer->getTotal()->setShippingDescription($address->getShippingDescription());
                                            /*
                                             * set grand total and base grand total
                                             */
                                            $_getSubTotal = $observer->getTotal()->getGrandTotal();
                                            $observer->getTotal()->setGrandTotal($_getSubTotal + $grabShippingAmount);
                                            $observer->getTotal()->setBaseGrandTotal($_getSubTotal + $grabShippingAmount);
                                            /* function to return delivery fees calculation */

                                            $logger->info($quote->getId().' actual shipping cost ' . $grabShippingAmount);
                                            break;
                                        }
                                    }
                                    return;
                                }else{

                                    $this->grabhelper->getBadrequestExpection('Delivery is not available for this area.','DELIVERY_NOT_AVAILABLE');
                                }

                            }else{
                                $this->grabhelper->getBadrequestExpection('Delivery is not available for this area.','DELIVERY_NOT_AVAILABLE');
                            }
                        }
                        $this->grabhelper->getBadrequestExpection('Delivery is not available for this area.','DELIVERY_NOT_AVAILABLE');
                    }

                }
            }elseif(!empty($_DeliveryStatus)){
                $logger->info($quote->getId(). ' --inside delivery  calculation when grab disabled ----');
                $logger->info($quote->getId().' --lat and long value fetched----');
                $logger->info($quote->getId().' lat '.$_selleraddress['lat'].'and long' .$_selleraddress['long']);

                if (!empty($_selleraddress['lat'] && !empty($_selleraddress['long']) && !empty($_DeliveryStatus))) {
                    //tmp code
                    $latitudeCust = $_setLatitude; //visitor lat
                    $longitudeCust = $_setLongtitude; //visitor long
                    $latitudeSeller = $_selleraddress['lat']; // seller lat
                    $longitudeSeller = $_selleraddress['long']; //seller long
                    //tmp code end

                    $logger->info($quote->getId().' lat '.$latitudeCust.'and long' .$longitudeCust.' and for seller lat '.$latitudeSeller.' and long val is .'.$longitudeSeller);
                    $logger->info('---------quote '.$quote->getId().' assigned--------');

                    /**
                     * new feature @17-03-2018
                     * apply free shipping for seller if seller set max price value for product
                     */
                    $sellerFreeShippingGlobalStatus = $this->_homedeliveryhelper->getSellersFreeShippingStatus();
                    $subTotal = round($quote->getSubtotal());
                    $quotetaxAmout = round($subTotal * 18/ 100);
                    $subtotalInclTax = $subTotal + $quotetaxAmout;
                    $sellerMaxShipPrice = $this->deliveryrangeHelper->getSellerMaxInPrice($_sellerid);
                    if(!empty($sellerMaxShipPrice) && $sellerFreeShippingFlag == 1) {
                        if ($subtotalInclTax > $sellerMaxShipPrice) {
                            $freeshippingFlag = true;
                            $logger->info($quote->getId(). ' free shipping check if inside seller max price ' . $freeshippingFlag);
                        }
                    }
                    $_AddressDistance = $this->_homedeliveryhelper->getDistance($latitudeCust, $longitudeCust, $latitudeSeller, $longitudeSeller, $key);
                    $logger->info($quote->getId().' address distance is '.$_AddressDistance);
                    $_checkMaxFees = $this->checkMaxSellerRange($_sellerid, $_AddressDistance);
                    if ($_checkMaxFees === true) {

                        if($freeshippingFlag === true && !empty($sellerFreeShippingGlobalStatus)){
                            $_Custshipping = 0;
                        }else {
                            $_Custshipping = $this->getSellerRange($_sellerid, $_AddressDistance, $quote->getData('entity_id'));
                        }
                        if (isset($_Custshipping)):
                            foreach ($address->getAllShippingRates() as $rate) {
                                if ($rate->getCode() == self::DELIVERYMETHOD) {
                                    $store = $quote->getStore();
                                    /* function to return delivery fees calculation */
                                    $_checkProduct = $this->_homedeliveryhelper->getSelleridFSku($_setProductsku);
                                    if (!empty($_checkProduct)) {
                                        $rate->setPrice($_Custshipping);
                                    }
                                    $address->setPrice(10);
                                    $observer->getTotal()->setTotalAmount($rate->getCode(), $_Custshipping);
                                    $observer->getTotal()->setBaseTotalAmount($rate->getCode(), $_Custshipping);
                                    $shippingDescription = $rate->getCarrierTitle() . ' - ' . $rate->getMethodTitle();
                                    $address->setShippingDescription(trim($shippingDescription, ' -'));
                                    $observer->getTotal()->setBaseShippingAmount($_Custshipping);
                                    $observer->getTotal()->setShippingAmount($_Custshipping);
                                    $observer->getTotal()->setShippingDescription($address->getShippingDescription());
                                    /*
                                     * set grand total and base grand total
                                     */
                                    $_getSubTotal = $observer->getTotal()->getGrandTotal();
                                    $observer->getTotal()->setGrandTotal($_getSubTotal + $_Custshipping);
                                    $observer->getTotal()->setBaseGrandTotal($_getSubTotal + $_Custshipping);
                                    /* function to return delivery fees calculation */

                                    $logger->info($quote->getId() .' actual shipping val '.$_Custshipping.' for quote id .'.$quote->getId());
                                    break;
                                }
                            }
                        else:
                            /*
                             * applying zero shipping when user select pick up option
                             */
                            $this->grabhelper->getBadrequestExpection('Delivery is not available for this area.','DELIVERY_NOT_AVAILABLE');

                        endif;
                    } else {

                        /**
                         * created new observer as per new requirement
                         * @17-07-2018
                         */

                        if($isConglomerate === true){
                            $logger->info($quote->getId()." inside store delivery cal for conglomerate seller ".$_sellerid);
                            $logger->info($quote->getId()." ---------------------------------------------------------------");
                            $storeShippingArray = ["sellerid"=>$_sellerid,"customerlat"=>$latitudeCust,"customerlong"=>$longitudeCust,"savedStorename"=>$storeUniqueName,"quote"=>$quote->getEntityId(),"grab"=>false];
                            $this->eventmanager->dispatch("store_check_for_shipping",["store-shipping"=> $storeShippingArray]);
                            /**
                             * set mindistance to shipping rate
                             */
                            if($this->_registry->registry('min_location_distance') >= 0 && $quote->getData('store_unique_name') != '') {
                                $logger->info($quote->getId(). " min location distance has set in registry for seller id ".$_sellerid." quote id is ".$quote->getData('entity_id'));

                                $minDistance = $this->_registry->registry('min_location_distance');
                                $logger->info($quote->getId() ." min distnace return from event for quote id ".$quote->getData('entity_id')." is ".$minDistance);
                                $custshippingValue = $this->getSellerRange($_sellerid, $minDistance, $quote->getData('entity_id'));
                                $logger->info($quote->getId() ." delivery rate for quote id ".$quote->getData('entity_id')."is ".$custshippingValue);

                                if (isset($custshippingValue) && !empty($custshippingValue)) {
                                    $logger->info($quote->getId() ." inside delivery rate for quote id ".$quote->getData('entity_id')." is INR".$custshippingValue);

                                    $logger->info($custshippingValue);

                                    foreach ($address->getAllShippingRates() as $rate) {
                                        if ($rate->getCode() == self::DELIVERYMETHOD) {
                                            $logger->info($quote->getId() .' store inside condtion ' . $rate->getCode() . ' for quote id .' . $quote->getId());

                                            $store = $quote->getStore();
                                            /* function to return delivery fees calculation */
                                            $_price = $custshippingValue;
                                            $amountPrice = $this->priceCurrency->convert(
                                                $_price, $store
                                            );
                                            $observer->getTotal()->setTotalAmount($rate->getCode(), $amountPrice);
                                            $observer->getTotal()->setBaseTotalAmount($rate->getCode(), $amountPrice);
                                            $shippingDescription = $rate->getCarrierTitle() . ' - ' . $rate->getMethodTitle();
                                            $address->setShippingDescription(trim($shippingDescription, ' -'));
                                            $observer->getTotal()->setBaseShippingAmount($amountPrice);
                                            $observer->getTotal()->setShippingAmount($amountPrice);
                                            $observer->getTotal()->setShippingDescription($address->getShippingDescription());
                                            /*
                                            * set grand total and base grand total
                                            */
                                            $_getSubTotal = $observer->getTotal()->getGrandTotal();
                                            $observer->getTotal()->setGrandTotal($_getSubTotal + $_price);
                                            $observer->getTotal()->setBaseGrandTotal($_getSubTotal + $_price);
                                            $logger->info($quote->getId() .' shipping amout has set for  quote id '.$quote->getData('entity_id'));
                                            /* function to return delivery fees calculation */
                                            break;
                                        }
                                    }
                                } else {
                                    $this->grabhelper->getBadrequestExpection('Delivery is not available for this area.', 'DELIVERY_NOT_AVAILABLE');

                                }
                            }else{
                                $this->grabhelper->getBadrequestExpection('Delivery is not available for this area.','DELIVERY_NOT_AVAILABLE');
                            }

                        }else{
                            $this->grabhelper->getBadrequestExpection('Delivery is not available for this area.','DELIVERY_NOT_AVAILABLE');

                        }
                    }
                } else {
                    //throw new LocalizedException(__('Shiiping is not available for this area'));
                }

            }
            else{
                //$this->grabhelper->getBadrequestExpection('Grab Delivery is disabled completely.');
            }


        } else {
            /*
             * applying zero shipping when user select pick up option
             */
            foreach ($address->getAllShippingRates() as $rate) {
                if ($rate->getCode() == $method) {
                    $store = $quote->getStore();
                    /* function to return delivery fees calculation */
                    $_price = 0;
                    $amountPrice = $this->priceCurrency->convert(
                        $_price, $store
                    );
                    $observer->getTotal()->setTotalAmount($rate->getCode(), $amountPrice);
                    $observer->getTotal()->setBaseTotalAmount($rate->getCode(), $amountPrice);
                    $shippingDescription = $rate->getCarrierTitle() . ' - ' . $rate->getMethodTitle();
                    $address->setShippingDescription(trim($shippingDescription, ' -'));
                    $observer->getTotal()->setBaseShippingAmount($amountPrice);
                    $observer->getTotal()->setShippingAmount($amountPrice);
                    $observer->getTotal()->setShippingDescription($address->getShippingDescription());
                    /* function to return delivery fees calculation */
                    break;
                }
            }
        }
        $logger->info('---------------------------------------------------------------------------------');
        $logger->info($quote->getId() . '-------------------inside in shipping observer process ended---------------------');
        $logger->info('---------------------------------------------------------------------------------');
        return;
    }

    /*
     * get matrix key
     */

    public function getMapKey() {
        return $this->_homedeliveryhelper->getKey('delivery_fee/delivery_fee/google_api_key');
    }

    /*
     * return seller lat and long value
     */

    public function getSelleraddressData($id) {
        $customerAddress = array();

        $customerObj = $this->_homedeliveryhelper->getSelleraddress($id);
        if (!empty($customerObj->getStoreLatitude())) {
            return array("lat" => $customerObj->getStoreLatitude(), "long" => $customerObj->getStoreLongitude());
        }
    }

    /**
     * @return $_finalFess
     *
     */
    public function getSellerRange($id, $_distance, $quoteId = null) {
        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/shipping_seller_range.log');
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);

        $_finalFess = "";
        $_collection = $this->rangepriceFactory->create()->getCollection()
            ->addFieldToFilter('is_active', 1)
            ->addFieldToFilter('delivery_deleted', 0)
            ->addFieldToFilter('seller_id', $id);
        foreach ($_collection as $_rangedata) {
            if ($_rangedata['from_kms'] == 0) {
                $_rangedata['from_kms'] = -1;
            }
            $maxSellerDeliveryKms = $_rangedata['to_kms'];
            $toDistance = $maxSellerDeliveryKms + self::DELIVERYEXTRAKM;
            if ($_distance > $_rangedata['from_kms'] && $_distance <= $toDistance) {
                if(empty($_rangedata['delivery_price'])){
                    return  $_finalFess = "nan";
                }
                $logger->info('seller given range shipping val is '.$_finalFess);
                return  $_finalFess = $_rangedata['delivery_price'];

                break;
            }
            continue;
        }
    }

    /**
     * @check distance
     * return true when seller max delivery range is below than customer address range
     */
    public function checkMaxSellerRange($id, $_distance) {
        $_Maxdistance = array();
        $_collection = $this->rangepriceFactory->create()->getCollection()
            ->addFieldToFilter('is_active', 1)
            ->addFieldToFilter('delivery_deleted', 0)
            ->addFieldToFilter('seller_id', $id);

        foreach ($_collection as $_rangedata) {
            $_Maxdistance[] = $_rangedata['to_kms'];
        }
        if (!empty($_Maxdistance)) :
            $maxFromArray = max($_Maxdistance);
            $maxDeliveryKms = $maxFromArray + self::DELIVERYEXTRAKM;
            if ($maxDeliveryKms >= $_distance) {
                return true;
            } else {
                return false;
            }
        else:
            return false;
        endif;
    }

    /*
     * check delivery is on or off for seller
     */

    public function getSelleredeliveryStatus($id) {
        return $this->_homedeliveryhelper->getSelleredelivery($id);
    }




}
