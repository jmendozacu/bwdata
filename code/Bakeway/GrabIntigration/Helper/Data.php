<?php

namespace Bakeway\GrabIntigration\Helper;

use Bakeway\Partnerlocations\Model\Partnerlocations as Partnerlocations;
use Symfony\Component\Config\Definition\Exception\Exception;
use Magento\Framework\ObjectManagerInterface as ObjectManager;
use Magento\Customer\Api\CustomerRepositoryInterface as CustomerRepositoryInterface;
use Magento\Sales\Model\Order\Address\Renderer as AddressRenderer;
use \Magento\Framework\Message\ManagerInterface as ManagerInterface;
use Webkul\Marketplace\Helper\Data as MarketplaceHelper;
use Bakeway\Partnerlocations\Model\ResourceModel\Partnerlocations\Collection as LocationCollection;

class Data extends \Magento\Framework\App\Helper\AbstractHelper {

    CONST GRAB_DELIVERY_STATUS = 'grab/grab_setting/status';

    CONST GRAB_BASE_DISCOUNT = 'grab/grab_setting/base_discount';

    CONST GRAB_SURPLUS_AMOUNT_FOR_DISTANCE = 'grab/grab_setting/distance_amout';

    CONST GRAB_FINAL_WEIGHT_TO_APPLY_MULTIPLICATION = 'grab/grab_setting/final_weight';

    CONST GRAB_SURPLUS_AMOUNT_FOR_WEIGHT  = 'grab/grab_setting/weight_amount';

    CONST GRAB_UUPPER_LIMIT_FOR_KMS = 'grab/grab_setting/upper_limit';

    CONST GRAB_CLIENT_ID = 'grab/grab_api_setting/client_id';

    CONST GRAB_PUBLIC_KEY = 'grab/grab_api_setting/public_key';

    CONST GRAB_PRIVATE_KEY = 'grab/grab_api_setting/private_key';

    CONST BAKEWAY_ORDER_TYPE = 'ONLINE_ASYNC';

    CONST GRAB_API_PRODUCTION_END_POINT ='grab/grab_api_setting/apiendpoint';

    CONST GRAB_TAX_PERCENT = 'grab/grab_tax_setting/tax';


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
     * @var \Webkul\Marketplace\Model\SellerFactory
     */
     protected $vendorFactory;

    /**
     * @var  \Magento\Customer\Block\Account\Dashboard\Info
     */
     protected $customerdash;

    /*
    * @var  Partnerlocations
    */
     protected $partnerlocations;

    /**
     * @var ObjectManager
     */
     protected $objectManager;

    /**
     * @var CustomerRepositoryInterface
     */
     protected  $customerRepositoryInterface;

    /**
     * @var AddressRenderer
     */
     protected $addressRenderer;

    /**
     * @var ManagerInterface
     */
     protected $managerInterface;

    /**
     * @var MarketplaceHelper
     */
    protected  $marketplacehelper;

    /**
     * @var LocationCollection
     */
    protected $partnerlocationscollection;
    /**
     * Data constructor.
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Bakeway\Deliveryrangeprice\Model\RangepriceFactory $rangepriceFactory
     * @param \Magento\Customer\Model\CustomerFactory $sellerFactory
     * @param \Webkul\Marketplace\Model\SellerFactory $vendorFactory
     * @param \Magento\Customer\Block\Account\Dashboard\Info $customerdash
     * @param Partnerlocations $partnerlocations
     * @param ObjectManager $objectManager
     * @param CustomerRepositoryInterface $customerRepositoryInterface
     * @param AddressRenderer $addressRenderer
     * @param ManagerInterface $managerInterface
     */
    public function __construct(
    \Magento\Framework\App\Helper\Context $context, 
    \Bakeway\Deliveryrangeprice\Model\RangepriceFactory $rangepriceFactory, 
    \Magento\Customer\Model\CustomerFactory $sellerFactory, 
    \Webkul\Marketplace\Model\SellerFactory $vendorFactory, 
    \Magento\Customer\Block\Account\Dashboard\Info $customerdash,
    Partnerlocations $partnerlocations,
    ObjectManager $objectManager,
    CustomerRepositoryInterface $customerRepositoryInterface,
    AddressRenderer $addressRenderer,
    ManagerInterface $managerInterface,
    MarketplaceHelper $marketplacehelper,
    LocationCollection $locationCollection

    ) {
        parent::__construct($context);
        $this->rangepriceFactory = $rangepriceFactory;
        $this->_sellerFactory = $sellerFactory;
        $this->vendorFactory = $vendorFactory;
        $this->customerdash = $customerdash;
        $this->partnerlocations = $partnerlocations;
        $this->objectManager = $objectManager;
        $this->customerRepositoryInterface = $customerRepositoryInterface;
        $this->addressRenderer = $addressRenderer;
        $this->managerInterface = $managerInterface;
        $this->marketplacehelper = $marketplacehelper;
        $this->partnerlocationscollection = $locationCollection;

    }


    /**
     * @param $sellerId
     * @return mixed
     */
    public function getGrabdelivery($sellerId) {
        $_Seller = $this->vendorFactory->create()->getCollection()
            ->addFieldToFilter("seller_id", $sellerId);
        return $_Seller->getFirstItem()->getData('is_grab_active');
    }


    /**
    * @return global Enable or disable Status of GGRAB"S
    */
    public function getGrabGlobalStatus()
    {
        return $this->scopeConfig->getValue(
            self::GRAB_DELIVERY_STATUS, \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }


    /**
     * @return Grab base discount
     */
    public function getGrabbaseDiscount()
    {
        return $this->scopeConfig->getValue(
            self::GRAB_BASE_DISCOUNT, \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @return Grab surplus amount for distance
     */
    public function getGrabSurplusAmoutForDistance()
    {
        return $this->scopeConfig->getValue(
            self::GRAB_SURPLUS_AMOUNT_FOR_DISTANCE, \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @return Grab Final weight to apply multiplication
     */
    public function getWeightForapplyMultiplication()
    {
        return $this->scopeConfig->getValue(
            self::GRAB_FINAL_WEIGHT_TO_APPLY_MULTIPLICATION, \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }


    /**
     * @return Grab surplus amount for weight
     */
    public function getGrabSurplusAmoutForWeight()
    {
        return $this->scopeConfig->getValue(
            self::GRAB_SURPLUS_AMOUNT_FOR_WEIGHT, \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @return Grab Upper limit for grab in Kms
     */
    public function getGrabUpperLimitinKms()
    {
        return $this->scopeConfig->getValue(
            self::GRAB_UUPPER_LIMIT_FOR_KMS, \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * grab client id
     * @return mixed
     */
    public function getGrabClientKey()
    {
        return $this->scopeConfig->getValue(
            self::GRAB_CLIENT_ID, \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * grab publick key
     * @return mixed
     */
    public function getGrabPrivateKey()
    {
        return $this->scopeConfig->getValue(
            self::GRAB_PUBLIC_KEY, \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * grab private key
     * @return mixed
     */
    public function getGrabPublicKey()
    {
        return $this->scopeConfig->getValue(
            self::GRAB_PRIVATE_KEY, \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }


    /**
     * @param $message
     * @param $code
     */
    public function getBadrequestExpection($message, $code = null)
    {
        $corsOriginUrl = $this->scopeConfig->getValue('web/corsRequests/origin_url',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        if (!headers_sent()) {
            header('HTTP/1.1 ' . '400' . ' Bad Request');
            header('Access-Control-Allow-Credentials:true');
            header('Content-type: application/json; charset=utf-8');
            header('Access-Control-Allow-Origin: ' . $corsOriginUrl);
        }
        $response = array(
            'message' => $message,
            'code' => $code
        );
        echo json_encode($response);
        exit;
    }

    /**
     * @param $id
     * @return mixed
     */

    public function getSellerGrabStatus($id) {
        $_Seller = $this->partnerlocations->getCollection()
            ->addFieldToFilter("seller_id", $id);
        return $_Seller->getFirstItem()->getData('is_grab_active');
    }

    /**
     * @param $id
     * @param $storeName
     * @return mixed
     */
    public function getSellerStoreGrabStatus($id,$storeName) {
        $seller = $this->partnerlocations->getCollection()
            ->addFieldToFilter("seller_id", $id)
            ->addFieldToFilter("store_unique_name", $storeName);
        return $seller->getFirstItem()->getData('is_grab_active');
    }

    /**
     * grab api end point
     * @return mixed
     */
    public function getGrabApiEndPoints() {
        return $this->scopeConfig->getValue(
            self::GRAB_API_PRODUCTION_END_POINT, \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }


    /**
     * Return the seller entity id from userdata table.
     *
     * @return \Webkul\Marketplace\Model\ResourceModel\Seller\Collection
     */
    public function getSellerEntityId($sellerid) {
        $model = $this->objectManager->create(
            'Bakeway\Partnerlocations\Model\Partnerlocations'
        )
            ->getCollection()
            ->addFieldToFilter(
                'seller_id', $sellerid)
            ->getFirstItem();

        $id = $model->getData('id');
        return $id;
    }

    
    /**
     * Return the seller grab status  from userdata table.
     *
     * @return \Webkul\Marketplace\Model\ResourceModel\Seller\Collection
     */
    public function getSellerUniqueStatus($sellerid,$storeName,$returnfield) {


        $isConglomerate = $this->marketplacehelper->isConglomerate($sellerid);

        $model = $this->partnerlocationscollection
            ->addFieldToFilter('seller_id', $sellerid)
            ->addFieldToFilter('is_active', 1);


        if ($isConglomerate === true) {
            $location = $model->addFieldToFilter('store_unique_name', $storeName)
                    ->getFirstItem();

            $status = $location->getData($returnfield);
        } else {
            $location = $model->getFirstItem();
            $status = $location->getData($returnfield);
        }

        return $status;
    }



    /**
     * @param $order
     * @return mixed
     */
   public function outForDeliveryStatusGrabApiCall($order,$sellerId)
   {

       /**
        * varriable define
        */

       $customerPhone = $customerFirstName = $customerLastName = $customerAddressLine1 = $billAmount = "";

       $grabClientid = $this->getGrabClientKey();
       $grabPublicKey = $this->getGrabPrivateKey();
       $grabPraivateKey = $this->getGrabPublicKey();

       $storeUniqueName = $locationPrimaryKey =  "";
       $customerShiipingData = $order->getShippingAddress();

       $customerPhone = $customerShiipingData['telephone'];

       $customerFirstName = $customerShiipingData['firstname'];

       $customerLastName = $customerShiipingData['lastname'];

       $storeUniqueName = $order->getStoreUniqueName();

       $customerAddressLine1 = $this->addressRenderer->format($customerShiipingData, 'text');

       $billAmount = number_format($order['base_grand_total'], 0);

       /*remove string from order amount*/
       $billAmount = str_replace(",", "", $billAmount);


       $locationPrimaryKey = $this->getSellerUniqueStatus($sellerId, $storeUniqueName,'id');


       if (empty($locationPrimaryKey)){
           $this->managerInterface->addSuccess(__("Store Unique Name is Not Defined"));
       }


       $grabApiEndPoints = $this->getGrabApiEndPoints();

       //$locationPrimaryKey = 29; //tmp

       if(!empty($locationPrimaryKey)){

       if(!empty($order)) {

           try {

               $publicKey = $grabPublicKey;

               $privateHash = $grabPraivateKey;

               $content    = json_encode(
                   array(
                       "clientId" => $grabClientid,
                       "clientOrderId" => $order->getEntityId(),
                       "prepTime"=> "0",
                       "merchantId"=> $locationPrimaryKey,
                       "dttm" => $order['created_at'],
                       "customerPhone" => $customerPhone,
                       "customerName" => $customerFirstName." ".$customerLastName,
                       "customerAddressLine1" =>  $customerAddressLine1,
                       "customerAddressLine2" =>  "NA",
                       "customerAddressLandMark"=>"NA",
                       "billAmount"=> $billAmount,
                       "billNo"=> $order->getIncrementId(),
                       "orderType"=> self::BAKEWAY_ORDER_TYPE,
                       "amountPaidByClient"=>$billAmount,
                       "amountCollectedInCash"=>"0",
                       "comments"=> "LIVE ORDER"
                   ));

               $hashAlgoritham = hash_hmac('sha256', $content, $privateHash);


               $headers = array(
                   'X-Public: '.$publicKey,
                   'X-Hash: '.$hashAlgoritham
               );


               $ch = curl_init($grabApiEndPoints);
               curl_setopt($ch,CURLOPT_HTTPHEADER,$headers);
               curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
               curl_setopt($ch,CURLOPT_POSTFIELDS,$content);

               $result = curl_exec($ch);
               curl_close($ch);


               $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/grabOrderStatus.log');
               $logger = new \Zend\Log\Logger();
               $logger->addWriter($writer);



               $parseArray = json_decode($result,true);
               if($parseArray['success'] == 'false')
               {
                   $errorCode =  $parseArray['errorCode'];

                   $logger->info('clientOrderId=>'.$order->getIncrementId()."   error_code=>".$errorCode);

                   $grabErrorCodeArray = $this->getGrabErrorCodes();

                   $errorMessage = $grabErrorCodeArray[$errorCode];

                   $this->managerInterface->addError(__("Grab API Error: ". $errorMessage));
               }elseif($parseArray['success'] == 'true'){
                      $this->managerInterface->addSuccess(__("The Order id ".$order->getEntityId()." is successfully assigned to Grab"));
               }


           } catch (Exception $e) {
               echo $e->getMessage();
           }
       }
       else{

           return;
       }
      }

   }

    /**
     * grab error codes array
     * @return array
     */
    public function getGrabErrorCodes()
    {
      $errorCodeArray = [
          '1'=>"Invalid Client Key",
          "2"=>"Invalid Secret Key",
          "3"=>"Parse Error: Bad JSON Format",
          "4"=>"Missing Mandatory Field",
          "5"=>"Parse Error: Bad Date Format",
          "6"=>"Parse Error: Bad Timestamp format",
          "7"=>"Parse Error: Data Mismatch",
          "8"=>"Invalid Action",
          "9"=>"Pending Action",
          "10" => "Duplicate Record"
      ];

     return $errorCodeArray;
          
    }


    /**
     * grab push order status list
     * @return array
     */
    public function getGrabOrderStatus()
    {
        $statusArray = [
            '1' => "ORDER_CREATED",
            "2" => "ORDER_IN_PROCESS",
            "3" => "ASSIGNED_DELIVERY_BOY",
            "4" => "READY_FOR_DELIVERY",
            "5" => "OUT_FOR_DELIVERY",
            "6" => "ORDER_DELIVERED",
            "99" => "ORDER_CANCELLED",
            "101" => "ORDER_ACCEPTED",
            "102" => "ORDER_REJECTED"
        ];

        return $statusArray;

    }


    /**
     * @param $id
     * @return mixed
     */
    public function getNonxonglomerateSellerGrabStatus($id){
        $seller = $this->partnerlocations->getCollection()
            ->addFieldToFilter("seller_id", $id);
        return $seller->getFirstItem()->getData('is_grab_active');
    }


    /**
     * @return tax percent of GGRAB"S
     */
    public function getGrabTax()
    {
        return $this->scopeConfig->getValue(
            self::GRAB_TAX_PERCENT, \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }


    /**
     * @param void $shippingamout
     * @return mixed
     */
    public function grabTaxCalculation($shippingamount)
    { 
       if(isset($shippingamount))
       {
           $tax = ($shippingamount * $this->getGrabTax())/100;
           return round($tax);

       }else{
           return;
       }


    }

    /**
     * @param $sellerid
     * @return mixed
     */
    public function getGrabForSellerforAnySingleStore($sellerid) {
        $colletion = $this->objectManager->create(
            'Bakeway\Partnerlocations\Model\Partnerlocations'
        )
            ->getCollection()
            ->addFieldToFilter('seller_id', $sellerid)
            ->addFieldToFilter('is_grab_active', 1);

      return count($colletion);

    }

}
