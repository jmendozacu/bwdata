<?php

namespace Bakeway\HomeDeliveryshipping\Helper;

use Braintree\Exception;

use Bakeway\GrabIntigration\Helper\Data as Grabhelper;

class Data extends \Magento\Framework\App\Helper\AbstractHelper {

    CONST HOME_BAKERS = 3;

    CONST HOME_BAKERS_GLOBAL_FREE_SHIPPING = "free_shipping/free_shipping/home_bakers";

    CONST SELLERS_GLOBAL_FREE_SHIPPING = "free_shipping/free_shipping_of_seller/status";

    /**
     * Store manager.
     *
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var \Bakeway\Deliveryrangeprice\Model\RangepriceFactory
     */
    protected $rangepriceFactory;

    /**
     * @var  \Magento\Customer\Model\CustomerFactory
     */
    protected $_sellerFactory;

    /**
     * @var \Webkul\Marketplace\Model\ProductFactory
     */
    protected $productFactory;
    protected $vendorFactory;

    /**
     * @var  \Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $_productRepository;

    /**
     * @var  \Magento\Catalog\Model\Product
     */
    protected $_productModel;

    /**
     * @var Grabhelper
     */
    protected $grabhelper;
    /**
     * Data constructor.
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Bakeway\Deliveryrangeprice\Model\RangepriceFactory $rangepriceFactory
     * @param \Magento\Customer\Model\CustomerFactory $sellerFactory
     * @param \Webkul\Marketplace\Model\SellerFactory $vendorFactory
     * @param \Webkul\Marketplace\Model\ProductFactory $productFactory
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param \Magento\Catalog\Model\Product $productModel
     */
    public function __construct(
    \Magento\Framework\App\Helper\Context $context, \Bakeway\Deliveryrangeprice\Model\RangepriceFactory $rangepriceFactory, \Magento\Customer\Model\CustomerFactory $sellerFactory, \Webkul\Marketplace\Model\SellerFactory $vendorFactory, \Webkul\Marketplace\Model\ProductFactory $productFactory
    , \Magento\Catalog\Api\ProductRepositoryInterface $productRepository, \Magento\Catalog\Model\Product $productModel,
    Grabhelper $grabhelper) {
        parent::__construct($context);
        $this->rangepriceFactory = $rangepriceFactory;
        $this->_sellerFactory = $sellerFactory;
        $this->vendorFactory = $vendorFactory;
        $this->productFactory = $productFactory;
        $this->_productRepository = $productRepository;
        $this->_productModel = $productModel;
        $this->grabhelper = $grabhelper;
    }

    public function checkCollectionforid($id) {

        $_Collection = $this->rangepriceFactory->create()->getCollection()
                ->addFieldToFilter('delivery_deleted', 0)
                ->addFieldToFilter('seller_id', $id);
        return count($_Collection);
    }

    /*
     * get seller id from product id
     */

    public function getSellerid($prodid) {
        $_collection = $this->productFactory->create()->getCollection()
                ->addFieldToFilter('mageproduct_id', $prodid);

        if (!empty($_collection->getSize())) {
            return $_collection->getFirstItem()->getData('seller_id');
        } else {
            return "";
        }
    }

    /**
     * Matrix google map api to know the actual distance between two address
     */
    public function getDistance($latitude, $longitude, $latitudeb, $longitudeb, $key) {

        $_matrix = "https://maps.googleapis.com/maps/api/distancematrix/json?units=imperial&origins=" . $latitude . "," . $longitude . "&destinations=" . $latitudeb . "," . $longitudeb . "&key=" . $key;
        /*
         * this may be change in future
         */

        if(isset($latitudeb) && isset($longitudeb)){
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $_matrix);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_PROXYPORT, 3128);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            $response = curl_exec($ch);
            curl_close($ch);
            $response = json_decode($response, true);
  
            if(isset($response['rows'][0]['elements'][0]['distance']['value'])){
                $_resobj = $response['rows'][0]['elements'][0]['distance']['value'];
            }else {
                $_resobj = "";
                $this->grabhelper->getBadrequestExpection('Distance is not available for this area.');
            }
                $_res = $_resobj / 1000;
            return round($_res, 1);
        }else{
            return "";
        }
    }

    public function getLatandLang($address, $key) {
        $_data = array();
        $address = str_replace(' ', '+', $address);
        $url = 'https://maps.google.com/maps/api/geocode/json?address=' . $address . '&sensor=false&key=' . $key;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_PROXYPORT, 3128);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        $response = curl_exec($ch);
        curl_close($ch);
        $response = json_decode($response, true);
        $_data['late'] = $response['results'][0]['geometry']['location']['lat'];
        $_data['long'] = $response['results'][0]['geometry']['location']['lng'];
        return $_data;
    }

    /*
     * get matrix key
     */

    public function getKey($config_path) {
        return $this->scopeConfig->getValue(
                        $config_path, \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /*
     * get seller address
     */

    public function getSelleraddress($id) {

        $_sellerData = $this->vendorFactory->create()->getCollection()
                ->addFieldToFilter("seller_id", $id)
                ->addFieldToSelect(array("store_latitude", "store_longitude"))
                ->getFirstItem();
        return $_sellerData;
    }

    /*
     * get seller region and postcode
     */

    public function getSellerDetails($id) {

        $_sellerData = $this->vendorFactory->create()->getCollection()
                ->addFieldToFilter("seller_id", $id)
                ->addFieldToSelect(array("store_zipcode", "store_city", "country_pic"))
                ->getFirstItem();
        return $_sellerData;
    }

    /*
     * get seller delivery status
     */

    public function getSelleredelivery($id) {
        $_Seller = $this->vendorFactory->create()->getCollection()
                ->addFieldToFilter("seller_id", $id);
        return $_Seller->getFirstItem()->getData('delivery');
    }

    /*
     * get seller shop title
     */

    public function getSellerShoptitle($id) {
        $_Seller = $this->vendorFactory->create()->getCollection()
                ->addFieldToFilter("seller_id", $id);
        return $_Seller->getFirstItem()->getData('shop_title');
    }

    /*
     * @param $sku
     * get seller id from product $sku
     */

    public function getSelleridFSku($sku) {
        /* get product id from sku */
        //$_collection = $this->_productModel->create()->getCollection();
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $productCollection = $objectManager->create('Magento\Catalog\Model\ResourceModel\Product\Collection')
                ->addAttributeToFilter("sku", $sku);
        if (count($productCollection)) { 
            $promodel = $this->_productRepository->get($sku);
            $proid = $promodel->getEntityId();
            $_collection = $this->productFactory->create()->getCollection()
                    ->addFieldToFilter('mageproduct_id', $proid);

            if (!empty($_collection->getSize())) {
                return $_collection->getFirstItem()->getData('seller_id');
            } else {
                return "";
            }
        } else {
            return "";
        }
    }

    /**
     * @param $sellerId
     * @return mixed
     */
    public function getBakeryType($sellerId)
    {
     $collection = $this->vendorFactory->create()->getCollection()
             ->addFieldToFilter("seller_id",array("eq"=>$sellerId))
             ->addFieldToFilter("bakery_type",array("eq"=>self::HOME_BAKERS))
             ->addFieldToSelect('bakery_type')
             ->getFirstItem();
     return count($collection->getData('bakery_type'));
    }

    /**
     * return home bakers shipping flag
     * @return mixed
     */
    public function getHomeBakersFreeShippingStatus() {
        return $this->scopeConfig->getValue(
            self::HOME_BAKERS_GLOBAL_FREE_SHIPPING, \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }


    /**
     * return free shipping seller global status
     * @return mixed
     */
    public function getSellersFreeShippingStatus() {
        return $this->scopeConfig->getValue(
            self::SELLERS_GLOBAL_FREE_SHIPPING, \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @param $sellerId
     * @return array|void
     */
    public function getSellerCityNameFromCityId($sellerId){
        $sellerData = $this->vendorFactory->create()->getCollection()
            ->addFieldToFilter("seller_id", $sellerId)
            ->addFieldToSelect(array("store_city"));
        $sellerData->getSelect()->join( array('bc'=>'bakeway_cities'), 'main_table.store_city = bc.id', array('bc.name'));
        $sellerCount = count($sellerData);
        if(!empty($sellerCount)){
            $sellercityName = [];
            foreach($sellerData as $sellerdata){
                $sellercityName = $sellerdata['name'];

            }
         return $sellercityName;
        }
        return;

    }
}
