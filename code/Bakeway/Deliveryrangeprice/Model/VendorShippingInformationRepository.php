<?php

namespace Bakeway\Deliveryrangeprice\Model;

use Bakeway\Deliveryrangeprice\Api\VendorShippingInformationRepositoryInterface;
use Symfony\Component\Config\Definition\Exception\Exception;
use Webkul\Marketplace\Helper\Data as SellerHelper;
use Bakeway\HomeDeliveryshipping\Helper\Data as HomedeliveryHelper;
use Magento\Framework\Exception\NotFoundException;
use Webkul\Marketplace\Helper\Data as MarketplaceHelper;
use Bakeway\GrabIntigration\Helper\Data as grabHelper;
use Bakeway\Partnerlocations\Model\ResourceModel\Partnerlocations\CollectionFactory as LocationsCollection;
use Bakeway\Deliveryrangeprice\Model\RangepriceFactory as RangepriceFactory;
use Bakeway\ProductApi\Helper\Data as  ProductapiHelper;
use Bakeway\Deliveryrangeprice\Block\Delivery\Rangeprice as RangepriceBlock;
use Magento\Framework\Exception\LocalizedException;
use Bakeway\Deliveryrangeprice\Helper\Data as  DeliveryrangepriceHelper;

class VendorShippingInformationRepository implements VendorShippingInformationRepositoryInterface {

    CONST DELIVERYEXTRAKM = "0.5";
    CONST GRAB_START_DELIVERY_DISTANCE = "4"; //in km
    CONST WEIGHT_ATTRIBUTE_NAME = 'cake_weight';
    /**
     * @param SellerHelper $sellerHelper
     */
    protected $sellerHelper;

    /**
     * @param \Webkul\Marketplace\Model\SellerFactory $sellerFactory
     */
    protected $sellerFactory;

    /**
     * @param \Bakeway\Deliveryrangeprice\Helper\Data $_deliveryrang eHelper
     */
    protected $deliveryrangeHelper;

    /**
     * @var HomedeliveryHelper
     */
    protected $homedeliveryHelper;

    /**
     * @var MarketplaceHelper
     */
    protected $marketplaceHelper;
    /**
     * @var grabHelper
     */
    protected  $grabHelper;
    /**
     * @var LocationsCollection
     */
    protected $locationsCollection;
    /**
     * @var RangepriceFactory
     */
    protected $rangepriceFactory;

    /**
     * @var \Magento\Catalog\Model\ProductFactory
     */
    protected $productobj;

    /**
     * @var ProductapiHelper
     */
    protected $productapiHelper;

    /**
     * @var DeliveryrangepriceHelper
     */
    protected $deliveryrangepriceHelper;

    /**
     * VendorShippingInformationRepository constructor.
     * @param MarketplaceHelper $sellerHelper
     * @param \Webkul\Marketplace\Model\SellerFactory $sellerFactory
     * @param DeliveryrangepriceHelper $deliveryrangeHelper
     * @param HomedeliveryHelper $homedeliveryHelper
     * @param MarketplaceHelper $marketplaceHelper
     * @param grabHelper $grabHelper
     * @param LocationsCollection $locationsCollection
     * @param \Bakeway\Deliveryrangeprice\Model\RangepriceFactory $rangepriceFactory
     * @param \Magento\Catalog\Model\ProductFactory $productobj
     * @param ProductapiHelper $productapiHelper
     * @param RangepriceBlock $rangepriceBlock
     * @param DeliveryrangepriceHelper $deliveryrangepriceHelper
     */
    public function __construct(
    SellerHelper $sellerHelper,
    \Webkul\Marketplace\Model\SellerFactory $sellerFactory,
    \Bakeway\Deliveryrangeprice\Helper\Data $deliveryrangeHelper,
    HomedeliveryHelper $homedeliveryHelper,
    MarketplaceHelper $marketplaceHelper,
    grabHelper $grabHelper,
    LocationsCollection $locationsCollection,
    RangepriceFactory $rangepriceFactory,
    \Magento\Catalog\Model\ProductFactory $productobj,
    ProductapiHelper $productapiHelper,
    RangepriceBlock $rangepriceBlock,
    DeliveryrangepriceHelper $deliveryrangepriceHelper
    ) {
        $this->sellerHelper = $sellerHelper;
        $this->sellerFactory = $sellerFactory;
        $this->deliveryrangeHelper = $deliveryrangeHelper;
        $this->homedeliveryHelper = $homedeliveryHelper;
        $this->marketplaceHelper = $marketplaceHelper;
        $this->grabHelper = $grabHelper;
        $this->locationsCollection = $locationsCollection;
        $this->rangepriceFactory = $rangepriceFactory;
        $this->productobj = $productobj;
        $this->productapihelper = $productapiHelper;
        $this->rangepriceBlock= $rangepriceBlock;
        $this->deliveryrangepriceHelper = $deliveryrangepriceHelper;

    }

    /**
     * Get vendor Shipping Value
     * @api
     * @param int $vendorId
     * @param string $sku
     * @param string $latitude
     * @param string $longitude
     * @param string $storename
     * @return array
     * @throws \Magento\Framework\Exception\NotFoundException
     */
    public function getDelivery($vendorId ,$sku ,$latitude ,$longitude ,$storename = null){

        if(empty($vendorId))
        {
            throw new LocalizedException(__("Vendor id is missing."));
        }

        if(empty($sku) && empty($latitude) && empty($vendorId))
        {
            throw new NotFoundException(__("Latitude and logitude are missing."));
        }

        $sellerid = $this->homedeliveryHelper->getSelleridFSku($sku);

        if(isset($sellerid)){
          $deliveryStatus = $this->homedeliveryHelper->getSelleredelivery($sellerid);

            /** seller delivery status**/
            $sellerFreeShippingFlag = $this->deliveryrangepriceHelper->getSellerFreeShippingFlag($sellerid);
            /**grab intigration start**/

          $grabGlobalStatus = $weightAttributeValue = $grabSellerStatus = $taxAmount = "";
          $freeshippingFlag = false;
          $isConglomerate = $this->marketplaceHelper->isConglomerate($sellerid);
          $grabGlobalStatus = $this->grabHelper->getGrabGlobalStatus();

          $storeUniqueName = $storename;

          if(!empty($isConglomerate)){
              $grabSellerStatus =  $this->grabHelper->getSellerStoreGrabStatus($sellerid,$storeUniqueName);
          }else{
              $grabSellerStatus =  $this->grabHelper->getNonxonglomerateSellerGrabStatus($sellerid);
          }

          /**
          * check bakery type and global check
          */

          $homebakerGlobalStatus =  $this->homedeliveryHelper->getHomeBakersFreeShippingStatus();

          $bakeryType = $this->homedeliveryHelper->getBakeryType($sellerid);

          if(!empty($bakeryType))
          {
                $bakeryType = true;
          }

          $matrixKey = $this->getMapKey();

          if(empty($matrixKey)){
              throw new NotFoundException(__("Google matrix key is not defined."));
          }

          try{
              $prodData =  $this->productobj->create()->loadByAttribute('sku',$sku);
          }catch(Exception $e){
              $prodData =  false;
          }

          $isConglomerate = $this->marketplaceHelper->isConglomerate($sellerid);
          $locationColl = $this->locationsCollection->create()
              ->addFieldToFilter('seller_id', $sellerid);
          if ($isConglomerate === true) {
              $storeUniqueName = $storename;
              if (trim($storeUniqueName) != '' && $storeUniqueName != Null) {
                  $locationColl->addFieldToFilter('store_unique_name', trim($storeUniqueName));
                  if ($locationColl->count() > 0) {
                      $location = $locationColl->getFirstItem();
                      $_selleraddress['lat'] = $location->getData('store_latitude');
                      $_selleraddress['long'] = $location->getData('store_longitude');
                  } else {
                      throw new NotFoundException(__("No location found for this store unique name."));
                  }
              } else {
                  throw new NotFoundException(__("Store unique name not present in quote."));
              }
          } else {
              if ($locationColl->count() > 0) {
                  $location = $locationColl->getFirstItem();
                  $_selleraddress['lat'] = $location->getData('store_latitude');
                  $_selleraddress['long'] = $location->getData('store_longitude');
              } else {
                  throw new NotFoundException(__("Location is not present for this seller."));
              }
          }

          if(!empty($grabGlobalStatus) && !empty($grabSellerStatus))
          {

              $grabBaseAmount =  $this->grabHelper->getGrabbaseDiscount();
              $grabSurplusAmountDistance = $this->grabHelper->getGrabSurplusAmoutForDistance();
              $grabFinalweightForMultiplication = $this->grabHelper->getWeightForapplyMultiplication();
              $grabSurplusAmountWeight =  $this->grabHelper->getGrabSurplusAmoutForWeight();
              $grabUpperKmLimit  = $this->grabHelper->getGrabUpperLimitinKms();
              $grabshippingTax = $this->grabHelper->getGrabTax();

              $weightAttributeValue  = $prodData->getData(self::WEIGHT_ATTRIBUTE_NAME);

              $weightAttributeValue =  $prodData->getAttributeText(self::WEIGHT_ATTRIBUTE_NAME,$weightAttributeValue);

              //$weightAttributeValue = 1.7;

             if(!empty($weightAttributeValue) &&  !empty($latitude) &&  !empty($longitude))
             {

                 $grabMaxDeliveryLimit = $this->grabHelper->getGrabUpperLimitinKms();
                 $bakeryDistance = $this->homedeliveryHelper->getDistance($latitude, $longitude, $_selleraddress['lat'], $_selleraddress['long'], $matrixKey);
                 //$bakeryDistance = 5;
                    if($bakeryDistance <= $grabMaxDeliveryLimit)
                    {
                        $grabShippingAmount = $grabshippingTax;
                        $fee['shipping_type'] =  'grab';
                        if($weightAttributeValue > $grabFinalweightForMultiplication)
                        {
                            $grabShippingAmount  = $grabShippingAmount * $grabSurplusAmountWeight;
                        }
                        if($bakeryType === true && !empty($homebakerGlobalStatus)){
                            $grabShippingAmount = 0;
                            $fee['shipping_type'] =  'grab-homebakers';
                        }
                        $fee['shipping_rate'] =  $grabShippingAmount;

                        return json_decode(json_encode($fee),false);

                    }else{

                        throw new NotFoundException(__("Delivery is not available for this area."));
                    }
                } else{

                throw new NotFoundException(__("Delivery is not available for this area."));
                }

          }
          else if(!empty($deliveryStatus) &&  !empty($latitude) &&  !empty($longitude)){

              $deliveryRangeCount = $this->deliveryrangeHelper->getSellerDeliveryRangeCount($sellerid);

              if($deliveryRangeCount === false){
                throw new LocalizedException(__("Delivery Range Is Not Found For Seller"));
              }

              $bakeryDistance = $this->homedeliveryHelper->getDistance($latitude, $longitude, $_selleraddress['lat'], $_selleraddress['long'], $matrixKey);

              //$bakeryDistance = 6;
              /**
               * new feature @17-03-2018
               * apply free shipping for seller if seller set max price value for product
               */
              $sellerFreeShippingGlobalStatus = $this->homedeliveryHelper->getSellersFreeShippingStatus();

              $priceInclTax = $this->productapihelper->getProductTaxPrice($prodData);

              $sellerMaxShipPrice = $this->deliveryrangeHelper->getSellerMaxInPrice($sellerid);

              //$sellerMaxShipPrice = "100";

              if(!empty($sellerMaxShipPrice) && $sellerFreeShippingFlag == 1){
                  if($priceInclTax > $sellerMaxShipPrice){
                      $freeshippingFlag = true;
                      $fee['type'] =  'free-shipping';
                  }
              }


              $_checkMaxFees = $this->checkMaxSellerRange($sellerid, $bakeryDistance);


              if ($_checkMaxFees === true) {
                  if($freeshippingFlag === true && !empty($sellerFreeShippingGlobalStatus)){
                      $shippingFee = 0;
                  }else {
                      $shippingFee = $this->getSellerRange($sellerid, $bakeryDistance);

                      if($shippingFee == "nan"){
                          $shippingFee = 0;
                      }
                      $fee['shipping_type'] =  'generic-shipping';
                  }

                  if(isset($shippingFee)){
                        $fee['shipping_rate'] =  $shippingFee;
                        return json_decode(json_encode($fee),false);
                    }else{
                        throw new NotFoundException(__("Delivery is not available for this area."));
                  }

              }else{
                  throw new NotFoundException(__("Delivery is not available for this area."));

              }
          }

      }

      return;
    }


    /*
     * get matrix key
     */
    public function getMapKey() {
        return $this->homedeliveryHelper->getKey('delivery_fee/delivery_fee/google_api_key');
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

        if (!empty($_Maxdistance)):
            if (max($_Maxdistance) >= $_distance) {
                return true;
            } else {
                return false;
            }
        else:
            return false;
        endif;
    }


    /**
     * return final shipping fee
     * @param $id
     * @param $_distance
     * @return string
     */
    public function getSellerRange($id, $_distance) {


        $_finalFess = "";
        $_collection = $this->rangepriceFactory->create()->getCollection()
            ->addFieldToFilter('is_active', 1)
            ->addFieldToFilter('delivery_deleted', 0)
            ->addFieldToFilter('seller_id', $id);
        foreach ($_collection as $_rangedata) {
            if ($_rangedata['from_kms'] == 0) {
                $_rangedata['from_kms'] = -1;
            }
            if ($_distance > $_rangedata['from_kms'] && $_distance <= ($_rangedata['to_kms'] + self::DELIVERYEXTRAKM)) {
                if(empty($_rangedata['delivery_price'])){
                    return  $_finalFess = "nan";
                }
                return  $_finalFess = $_rangedata['delivery_price'];

                break;
            }
            continue;
        }
    }

}
