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
use Symfony\Component\Config\Definition\Exception\Exception;
use Webkul\Marketplace\Helper\Data as MarketplaceHelper;
use Bakeway\Partnerlocations\Model\ResourceModel\Partnerlocations\CollectionFactory as LocationsCollection;
use Bakeway\GrabIntigration\Helper\Data as Grabhelper;
use Magento\Checkout\Block\Cart\Item\Renderer as Renderer;
use Magento\Catalog\Api\ProductRepositoryInterface as ProductRepositoryInterface;
use Magento\Catalog\Api\CategoryRepositoryInterface as CategoryRepositoryInterface;
use Bakeway\Deliveryrangeprice\Helper\Data as DeliveryrangeHelper;
use Magento\Framework\Event\Manager as Eventmanager;
use Magento\Quote\Model\QuoteRepository as QuoteRepository;
use \Bakeway\HomeDeliveryshipping\Observer\Homedelivery;
use \Bakeway\ProductApi\Model\CatalogSeoRepository as CatalogSeoRepository;

class StoreDelivery implements ObserverInterface {

    CONST DELIVERY_STORE_RADIOUS = 10;
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
     * @var QuoteRepository
     */
    protected $quoteRepository;

    /**
     * @var CatalogSeoRepository
     */
    protected $catalogSeoRepository;

    /**
     * StoreDelivery constructor.
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
     * @param Eventmanager $eventmanager
     * @param QuoteRepository $quoteRepository
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
        QuoteRepository $quoteRepository,
        CatalogSeoRepository $catalogSeoRepository
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
        $this->quoteRepository = $quoteRepository;
        $this->catalogSeoRepository =$catalogSeoRepository;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @throws LocalizedException
     */
    public function execute(\Magento\Framework\Event\Observer $observer) {
        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/shipping_log.log');
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);
        $logger->info('------------------shipping store observer process get started---------------');
        $storeShippiingEventData = $observer->getData("store-shipping");

        $grabDelivery = $storeShippiingEventData['grab'];

        if(isset($storeShippiingEventData['sellerid']) && !empty($storeShippiingEventData['sellerid'])
            && isset($storeShippiingEventData['customerlat']) && !empty($storeShippiingEventData['customerlat'])
        && isset($storeShippiingEventData['customerlong']) && !empty($storeShippiingEventData['customerlong'])
        && isset($storeShippiingEventData['savedStorename']) && !empty($storeShippiingEventData['savedStorename'])){
            $latitudeCust = $storeShippiingEventData['customerlat'];
            $longitudeCust = $storeShippiingEventData['customerlong'];
            $sellerId = $storeShippiingEventData['sellerid'];
            $savedStorename = $storeShippiingEventData['savedStorename'];
            $logger->info('----------old saved store name for quote id '.$storeShippiingEventData['quote']." is ".$savedStorename);
            $matrixApikey =   $this->_homedeliveryhelper->getKey('delivery_fee/delivery_fee/google_api_key');
            if(empty($matrixApikey)){
                $this->grabhelper->getBadrequestExpection('Matrix Api key is undefinded.','API_KEY_NOT_AVAILABLE');
            }


            $sellerCityName = $this->_homedeliveryhelper->getSellerCityNameFromCityId($sellerId);
            /**
             * filter condition in case of grab enabled stores
             * todo this may be change
             */
            $storeDistance = $this->catalogSeoRepository->getStoreDetails($sellerId, $sellerCityName, $latitudeCust, $longitudeCust, false, self::DELIVERY_STORE_RADIOUS);
            $sortedStorenameArray= [];
            foreach($storeDistance as $storeDistanceItem){
                $sortedStorenameArray[] = $storeDistanceItem['store_unique_name'];
            }
            if($grabDelivery === true){
                $sellerLocationCollection = $this->locationsCollection->create()
                    ->addFieldToFilter("seller_id",["eq"=>$sellerId])
                    ->addFieldToFilter("is_active",["eq"=>"1"])
                    ->addFieldToFilter("store_unique_name",["neq"=>$savedStorename])
                    ->addFieldToSelect(["store_latitude","store_longitude","store_unique_name"])
                    ->addFieldToFilter("is_grab_active",["eq"=>"1"]);
            }else{
                $sellerLocationCollection = $this->locationsCollection->create()
                    ->addFieldToFilter("seller_id",["eq"=>$sellerId])
                    ->addFieldToFilter("is_active",["eq"=>"1"])
                    ->addFieldToFilter("store_unique_name",["neq"=>$savedStorename])
                    ->addFieldToSelect(["store_latitude","store_longitude","store_unique_name"]);
            }
            /**
             * check if seller has only one store enable but which is not applicable
             */
            if(count($sellerLocationCollection) < 1){
                $this->grabhelper->getBadrequestExpection('Delivery is not available for this area.','DELIVERY_NOT_AVAILABLE');
            }


            $locationDistanceArray = [];
            foreach($sellerLocationCollection as $key=>$sellerLocationData){

            if (in_array($sellerLocationData['store_unique_name'], $sortedStorenameArray)) {
                $latitudeSeller = $sellerLocationData['store_latitude'];
                $longitudeSeller = $sellerLocationData['store_longitude'];
                $nearStoredistance = $this->_homedeliveryhelper->getDistance($latitudeCust, $longitudeCust, $latitudeSeller, $longitudeSeller, $matrixApikey);
                $locationDistanceArray[$sellerLocationData['store_unique_name']] = $nearStoredistance;
                $logger->info($storeShippiingEventData['quote'] ." and distance is ".$nearStoredistance." ".$sellerLocationData['store_unique_name']." lat ".$latitudeSeller ." and longi ".$longitudeSeller);

             }
            }
            $minDistance= min($locationDistanceArray);
            $minDistanceStore = array_search($minDistance,$locationDistanceArray);
            $logger->info($storeShippiingEventData['quote']." --min distance is ".$minDistance." related store name is ".$minDistanceStore);
            if(empty($minDistanceStore)){
                $this->grabhelper->getBadrequestExpection('Store Unique Name is undefinded.','STORE_UNIQUE_NAME_NOT_AVAILABLE');
            }
            $rangeExist = true;
            $this->_registry->unregister('min_location_distance');

            $rangeExist = $this->getSellerRange($sellerId, $minDistance, $storeShippiingEventData['quote']);
            if(isset($rangeExist) && !empty($rangeExist)){
                $rangeExist = true;
            }

            if($grabDelivery === true){
                $rangeExist = false;
            }

            /**
             * get upper delivery limit of grab
             */
            $grabUpperKmLimit  = $this->grabhelper->getGrabUpperLimitinKms();
            $grabIn = false;


            if(isset($minDistanceStore)){
                $quoteId = $storeShippiingEventData['quote'];
                $quoteObj = $this->quoteRepository->getActive($quoteId);
                $quoteObj->setData("store_unique_name",$minDistanceStore);

                try{
                    if($grabDelivery === true){
                      if($minDistance <= $grabUpperKmLimit){
                            $grabIn = true;
                        }
                    }
                    if($grabIn === true){
                        $quoteObj->save();
                        if(!empty($this->_registry->register('min_location_distance',  $minDistance))){
                            $this->_registry->register('min_location_distance', $minDistance);
                            $logger->info($storeShippiingEventData['quote'].' ----------grab : store distance for quote id '.$storeShippiingEventData['quote']." is assigned to registry");
                         }
                        $logger->info($storeShippiingEventData['quote'].' ----------grab : new store has assgined to quote id '.$storeShippiingEventData['quote']." is ".$minDistanceStore);
                    }

                    if($rangeExist === true){
                        $quoteObj->save();
                        if(!empty($this->_registry->register('min_location_distance',  $minDistance))){
                            $this->_registry->register('min_location_distance', $minDistance);
                            $logger->info($storeShippiingEventData['quote']. ' ----------normal deliery: store distance for quote id '.$storeShippiingEventData['quote']." is assigned to registry");
                          }
                        $logger->info($storeShippiingEventData['quote'] .' ----------normal deliery : new store has assgined to quote id '.$storeShippiingEventData['quote']." is ".$minDistanceStore);
                    }




                }catch (Exception $e){
                    echo $e->getMessage();
                }
            }
            return;

        }else{
            $this->grabhelper->getBadrequestExpection('Parameters are not avalaible.','PARAMETERS_NOT_AVAILABLE');

        }


        $logger->info($storeShippiingEventData['quote']. '-------------------shipping store observer process get completed---------------------');
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
            $toDistance = $maxSellerDeliveryKms + Homedelivery::DELIVERYEXTRAKM;
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



}
