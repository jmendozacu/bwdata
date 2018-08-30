<?php

/**
 * Bakeway
 *
 * @category  Bakeway
 * @package   Bakeway_ProductApi
 * @author    Bakeway
 */

namespace Bakeway\ProductApi\Model;

use Bakeway\ProductApi\Api\VendorDeliveryChragesRepositoryInterface;
use \Magento\Framework\Exception\NotFoundException;
use Bakeway\HomeDeliveryshipping\Helper\Data as HomedeliveryHelper;
use Bakeway\GrabIntigration\Helper\Data as grabHelper;
use Webkul\Marketplace\Helper\Data as MarketplaceHelper;
use Bakeway\Partnerlocations\Model\ResourceModel\Partnerlocations\CollectionFactory as LocationsCollection;
use Bakeway\Deliveryrangeprice\Helper\Data as  DeliveryrangepriceHelper;
use Bakeway\ProductApi\Helper\Data as  ProductapiHelper;

class VendorDeliveryChragesRepository implements VendorDeliveryChragesRepositoryInterface {
    CONST DELIVERYEXTRAKM = "0.5";
    CONST GRAB_START_DELIVERY_DISTANCE = "4"; //in km
    CONST WEIGHT_ATTRIBUTE_NAME = 'cake_weight';

    /*
     * @param \Bakeway\Deliveryrangeprice\Helper\Data 
     */

    protected $deliveryrangepricehelper;

    /**
     * @var HomedeliveryHelper
     */
    protected $homedeliveryHelper;

    /**
     * @var grabHelper
     */
    protected $grabHelper;

    /**
     * @var MarketplaceHelper
     */
    protected $marketplaceHelper;
    /**
     * @var \Magento\Catalog\Model\ProductFactory
     */
    protected $productobj;
    /**
     * @var DeliveryrangepriceHelper
     */
    protected $deliveryrangepriceHelper;
    /**
     * @var ProductapiHelper
     */
    protected $productapiHelper;


    /**
     * VendorDeliveryChragesRepository constructor.
     * @param \Bakeway\Deliveryrangeprice\Helper\Data $deliveryrangepricehelper
     * @param HomedeliveryHelper $homedeliveryHelper
     * @param grabHelper $grabHelper
     * @param MarketplaceHelper $marketplaceHelper
     */
    public function __construct(
    \Bakeway\Deliveryrangeprice\Helper\Data $deliveryrangepricehelper,
    HomedeliveryHelper $homedeliveryHelper,
    grabHelper $grabHelper,
    MarketplaceHelper $marketplaceHelper,
    LocationsCollection $locationsCollection,
    \Magento\Catalog\Model\ProductFactory $productobj,
    ProductapiHelper $productapiHelper

    ) {
        $this->deliveryrangepricehelper = $deliveryrangepricehelper;
        $this->homedeliveryHelper = $homedeliveryHelper;
        $this->grabHelper = $grabHelper;
        $this->marketplaceHelper = $marketplaceHelper;
        $this->locationsCollection = $locationsCollection;
        $this->productobj = $productobj;
        $this->productapihelper = $productapiHelper;
    }

    /**
     * Get Vendor Delivery Charges Details
     * @param int $vendorId
     * @param string $storeName
     * @return array
     * @return empty []
     */
    public function getDeliverycharges($vendorId ,$storeName = null) {
        $deliveryCharges = [];

        if(empty($vendorId))
        {
            throw new NotFoundException(__("Vendor id is missing."));
        }

        /**
         * check bakery type and global check
         */
        $sellerFreeShippingFlag = $this->deliveryrangepricehelper->getSellerFreeShippingFlag($vendorId);
        $homebakerGlobalStatus =  $this->homedeliveryHelper->getHomeBakersFreeShippingStatus();
        $bakeryType = $this->homedeliveryHelper->getBakeryType($vendorId);
        $grabGlobalStatus = $this->grabHelper->getGrabGlobalStatus();

        $grabSellerStatus =  $this->grabHelper->getNonxonglomerateSellerGrabStatus($vendorId);
        $sellerMaxShipPrice = $this->deliveryrangepricehelper->getSellerMaxInPrice($vendorId);

        /**
         * grab calculation for congloramate seller
         */

        $sellerStoreGrabStatus = $this->grabHelper->getSellerStoreGrabStatus($vendorId ,$storeName);

        if(!empty($grabGlobalStatus) && !empty($grabSellerStatus) || !empty($sellerStoreGrabStatus)) {

            $grabMaxDeliveryLimit = $this->grabHelper->getGrabUpperLimitinKms();
            $grabFinalweightForMultiplication = $this->grabHelper->getWeightForapplyMultiplication();
            $grabSurplusAmountWeight =  $this->grabHelper->getGrabSurplusAmoutForWeight();
            $grabshippingTax = $this->grabHelper->getGrabTax();
            $grabShippingAmount = $grabshippingTax;

            /**
             * normal grab with homebakers
             */


           if(!empty($homebakerGlobalStatus) && !empty($bakeryType)){
               $deliveryCharges["delivery"][] = [
                   "from_kms" => 0,
                   "to_kms" => $grabMaxDeliveryLimit,
                   "charges" => 0,
               ];

                  $deliveryCharges[ "notes" ] =  "Free Delivery";

           }else{

               /**
                * normal grab
                */
               if(!empty($grabSellerStatus) || !empty($sellerStoreGrabStatus)){
                   $deliveryCharges["delivery"][] =  [
                       "from_kms" => 0,
                       "to_kms" => $grabMaxDeliveryLimit,
                       "charges" => $grabShippingAmount,
                   ];

                   $deliveryCharges["notes"] =  "Above ".$grabFinalweightForMultiplication." kg, ₹". $grabshippingTax * $grabSurplusAmountWeight;

               }

           }

        }else{

            /**
             * seller has max price than apply threshhold delivery
             */

            $sellerFreeShippingGlobalStatus = $this->homedeliveryHelper->getSellersFreeShippingStatus();


            $sellerMaxShipPrice = $this->deliveryrangepricehelper->getSellerMaxInPrice($vendorId);

            $deliveryChargescheck = "";
            if(!empty($sellerMaxShipPrice) && $sellerFreeShippingFlag == 1 && !empty($sellerFreeShippingGlobalStatus)){
                    $deliveryChargescheck =  "Above ₹".$sellerMaxShipPrice.",free delivery";

            }
            $collection = $this->deliveryrangepricehelper->getSellerDeliverychargesdetails($vendorId);
            if (!empty($collection)) {
                foreach ($collection as $key => $value) {
                    $deliveryCharges["delivery"][] = [
                        "from_kms" => $value['from_kms'],
                        "to_kms" => $value['to_kms'],
                        "charges" => $value['delivery_price']
                    ];
                }
                if (!empty($deliveryChargescheck)) {
                    $deliveryCharges["notes"] = $deliveryChargescheck;
                }
            }else {
                $deliveryCharges["error"] = "delivery range is empty";
            }

        }

        //$deliveryCharges[111] = 2;
        return $deliveryCharges;
    }

}
