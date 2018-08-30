<?php
/**
 * Bakeway
 *
 * @category  Bakeway
 * @package   Bakeway_VendorNotification
 * @author    Bakeway
 */

namespace Bakeway\VendorNotification\Helper;

use Aws\Sns\SnsClient;
use Bakeway\VendorNotification\Model\Sellerdevicedata;
use Aws\Exception\AwsException;
use Symfony\Component\Config\Definition\Exception\Exception;
use Webkul\Marketplace\Helper\Data as MarketplaceHelper;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface As TimezoneInterface;
use Bakeway\OrderstatusEmail\Model\Email as OrderStatusEmail;
use Hpatoio\Bitly\Client as BitlyClient;
/**
 * Bakeway VendorNotifcation Helper Data.
 */
class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    const NEW_ORDER_PARTNER_SMS = 'Dear Partner, you have got a new Bakeway order : ';

    const NEW_ORDER_CUSTOMER_SMS = 'Thank you for ordering on Bakeway! Your order id ';

    const ORDER_STATUS_ACCEPTED_SMS = "Dear Customer, your Bakeway order status is changed to : Accepted. Your order Id is : ";

    const ORDER_STATUS_REJECTED_SMS = "Dear Customer, your Bakeway order status is changed to : Rejected. Your payment will be refunded shortly. Your order Id is : ";

    const ORDER_STATUS_READY_SMS = "Dear Customer, your Bakeway order status is changed to : Ready For Pickup. Your order Id is : ";

    const ORDER_STATUS_OUT_FOR_DELIVERY_SMS = "Dear Customer, your Bakeway order status is changed to : Out For Delivery. Your order Id is : ";

    const ORDER_STATUS_COMPLETED_SMS = "Your order has been successfully delivered. We look forward to be part of your celebration soon. Kindly Review us on ";

    const SMS_SUPPORT_TEXT = "For any queries call Bakeway support (8:00 am to 11:00 pm) on 74477 66330";

    const ORDER_TYPE_NOTIFICATION = "1";

    const ORDER_TYPE_REMINDER = "2";

    const ORDER_TYPE_UPCOMING = "3";

    const NEW_ORDER_STATE_NOTIFICATION_MESSAGE = "You have %n orders in awaiting confirmation state";

    CONST REACT_URL = "reviewrating/review_setting/react_url";

    /**
     * @var \Bakeway\VendorNotification\Model\Sellerdevicedata
     */
    protected $deviceData;

    /**
     * @var MarketplaceHelper
     */
    protected $marketplaceHelper;

    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var OrderCollectionFactory
     */
    protected $ordercollectionFactory;

    /**
     * @var OrderStatusEmail
     */
    protected $orderStatusEmail;

    /**
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Bakeway\VendorNotification\Model\Sellerdevicedata $deviceData
     * @param MarketplaceHelper $marketplaceHelper
     * @param OrderRepositoryInterface $orderRepository
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Bakeway\VendorNotification\Model\Sellerdevicedata $deviceData,
        MarketplaceHelper $marketplaceHelper,
        OrderRepositoryInterface $orderRepository,
        OrderCollectionFactory $ordercollectionFactory,
        TimezoneInterface $timezoneInterface,
        OrderStatusEmail $orderStatusEmail
    )
    {
        $this->deviceData = $deviceData;
        parent::__construct($context);
        $this->marketplaceHelper = $marketplaceHelper;
        $this->orderRepository = $orderRepository;
        $this->ordercollectionFactory = $ordercollectionFactory;
        $this->timezoneInterface = $timezoneInterface;
        $this->orderStatusEmail = $orderStatusEmail;
    }

    public function sendPushNotification($endpoints, $messageText, $orderId)
    {
        $adnroidArn = $this->getPlatformApplicationArn('android');
        $iosArn = $this->getPlatformApplicationArn('ios');
        $availableEndpointsAndr = $this->getAvailablePlatformEndpoints($adnroidArn);
        $availableEndpointsIos = $this->getAvailablePlatformEndpoints($iosArn);
        foreach ($endpoints as $endpoint) {
            try {
                $platform = $endpoint->getPlatform();
                $platformEndpoint = $endpoint->getPlatformEndpoint();
                $isEnabled = false;
                if ($platform == strtolower('ios')) {
                    //if (in_array($platformEndpoint, $availableEndpointsIos)) {
                    $isEnabled = $this->checkEndpointEnabled($platformEndpoint);
                    //}
                    $data = json_encode(array("aps" => array("alert" => $messageText, "title" => "Bakeway New Order", "sound"=>"new_order.aiff"), "order_id" => $orderId,"type" => self::ORDER_TYPE_NOTIFICATION));
                    $push_message = json_encode(array($this->getIosKey() => $data));
                } else {
                    //if (in_array($platformEndpoint, $availableEndpointsAndr)) {
                    $isEnabled = $this->checkEndpointEnabled($platformEndpoint);
                    // }
                    $data = json_encode(array("data" => array("message" => $messageText, "title" => "Bakeway New Order", "order_id" => $orderId, "soundName"=>"new_order.mp3","type" => self::ORDER_TYPE_NOTIFICATION)));
                    $push_message = json_encode(array("default" => "test", "GCM" => $data));
                }
                if ($isEnabled === false) {
                    $device = $this->deviceData->load($endpoint->getId());
                    $device->delete();
                } else {
                    $snsClient = $this->getSnsClient();
                    $result = $snsClient->publish(array('Message' => $push_message,
                        'TargetArn' => $endpoint->getPlatformEndpoint(), 'MessageStructure' => 'json'));
                }
            } catch (AwsException $e) {
                $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/pushnoti_order.log');
                $logger = new \Zend\Log\Logger();
                $logger->addWriter($writer);
                $logger->info($e->getMessage());

            } catch (\Exception $e) {
                $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/pushnoti_order.log');
                $logger = new \Zend\Log\Logger();
                $logger->addWriter($writer);
                $logger->info($e->getMessage());
            }
        }
        return;
    }

    /**
     * @return string
     */
    public function getSnsClient()
    {
        $snsApiKey = $this->scopeConfig->getValue('vendor_app_settings/bakeway_general/sns_api_key', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $snsApiSecrete = $this->scopeConfig->getValue('vendor_app_settings/bakeway_general/sns_api_secrete', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);

        $snsClient = new SnsClient(array(
            'credentials' => array('key' => $snsApiKey, 'secret' => $snsApiSecrete),
            'region' => 'ap-south-1',
            'version' => 'latest'
        ));

        return $snsClient;
    }

    /**
     * @param string $platform
     * @return string
     */
    public function getPlatformApplicationArn($platform)
    {
        if (strtolower($platform) == 'ios') {
            return $this->scopeConfig->getValue('vendor_app_settings/bakeway_general/ios_platform_arn', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        } else {
            return $this->scopeConfig->getValue('vendor_app_settings/bakeway_general/android_platform_arn', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        }
    }

    /**
     * @param string $applicationArn
     * @param string $token
     * @return string|bool
     */
    public function getPlatformEndpoint($applicationArn, $token)
    {
        $snsClient = $this->getSnsClient();
        $result = $snsClient->createPlatformEndpoint(array(
            'PlatformApplicationArn' => $applicationArn,
            'Token' => $token
        ));
        if (isset($result['EndpointArn'])) {
            return $result['EndpointArn'];
        }
        return false;
    }

    /**
     * @param string $applicationArn
     * @return array
     */
    public function getAvailablePlatformEndpoints($applicationArn)
    {
        $endpoints = array();
        try{
            $snsClient = $this->getSnsClient();
            $result = $snsClient->listEndpointsByPlatformApplication(['PlatformApplicationArn' => $applicationArn]);

            if (isset($result['Endpoints']) && is_array($result['Endpoints'])) {
                $endpoints = array_column($result['Endpoints'], 'EndpointArn');
            }
        } catch (\Exception $e) {
            $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/pushnoti_order.log');
            $logger = new \Zend\Log\Logger();
            $logger->addWriter($writer);
            $logger->info($e->getMessage());
        }

        return $endpoints;
    }

    /**
     * @param string $endpoint
     * @return bool
     */
    public function checkEndpointEnabled($endpoint)
    {
        try {
            $snsClient = $this->getSnsClient();
            $result = $snsClient->getEndpointAttributes(['EndpointArn' => $endpoint]);

            if (isset($result['Attributes']) && $result['Attributes']['Enabled'] == "true") {
                return true;
            }
        } catch (\Exception $e) {
            $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/pushnoti_order.log');
            $logger = new \Zend\Log\Logger();
            $logger->addWriter($writer);
            $logger->info($e->getMessage());
            return false;
        }
        return false;
    }

    /**
     * Get the SMS Client
     */
    public function getSmsClient() {
        $snsApiKey = $this->scopeConfig->getValue('vendor_app_settings/bakeway_general/sns_api_key',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $snsApiSecrete = $this->scopeConfig->getValue('vendor_app_settings/bakeway_general/sns_api_secrete',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $smsClient = new SnsClient([
            'credentials' => ['key' => $snsApiKey, 'secret' => $snsApiSecrete],
            'region' => 'ap-southeast-1',
            'version' => 'latest'
        ]);

        return $smsClient;
    }

    /**
     * Send the SMS
     * @param int $mobileNumber
     * @param string $message
     */
    public function sendSms($mobileNumber, $message)
    {
        try {
            $smsClient = $this->getSmsClient();
            $msgAttributes = [
                'AWS.SNS.SMS.SenderID' => [
                    'DataType' => 'String',
                    'StringValue' => 'BKW',
                ],
                'AWS.SNS.SMS.SMSType' => [
                    'DataType' => 'String',
                    'StringValue' => 'Transactional',
                ]
            ];

            $mobileNumber = $this->getNumberWithCode($mobileNumber);
            $smsClient->publish([
                'Message' => $message,
                'PhoneNumber' => $mobileNumber,
                'MessageStructure' => 'text',
                'MessageAttributes'=>$msgAttributes
            ]);
        } catch (AwsException $e) {
            $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/ordersms.log');
            $logger = new \Zend\Log\Logger();
            $logger->addWriter($writer);
            $logger->info($e->getMessage());
        } catch (\Exception $e) {
            $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/ordersms.log');
            $logger = new \Zend\Log\Logger();
            $logger->addWriter($writer);
            $logger->info($e->getMessage());
        }
    }

    /**
     * Send sms to customer directly
     * @param int $mobileNumber
     * @param string $lastOrderId
     * @param int|null $sellerId
     */
    public function sendNewOrderSmsToCustomer($mobileNumber, $lastOrderId, $sellerId = null, $trackShortLink = null)
    {
        $message = self::NEW_ORDER_CUSTOMER_SMS.$lastOrderId .', has been placed with the baker. It would be delivered as scheduled '.$trackShortLink;
        if (isset($mobileNumber) && $mobileNumber != '') {
            /*if ($sellerId !== null) {
                $supportText = $this->getSupportText($sellerId);
                $message = $message . $supportText;
            }*/
            $message = $message.' In case of any queries contact us on +91-74477 66330 or write to us at support@bakeway.com (Support Time: 9am to 11pm - IST)';
            $this->sendSms($mobileNumber, $message);
        }
    }

    /**
     * Get partner details and send sms
     * @param int $partnerId
     * @param string $lastOrderId
     */
    public function sendNewOrderSmsToPartner($partnerId, $lastOrderId)
    {
        $message = self::NEW_ORDER_PARTNER_SMS.$lastOrderId;
        $sellerObject = $this->marketplaceHelper->getSellerDataBySellerId($partnerId);
        $sellerData = $sellerObject->getFirstItem();
        if ($sellerData->getId()) {
            $ownerMobileNumber = $sellerData->getData('store_manager_mobile_no');
            if (isset($ownerMobileNumber) && $ownerMobileNumber != '') {
                $this->sendSms($ownerMobileNumber, $message);
            }
        }
    }

    /**
     * @param int $sellerId
     * @param string $lastOrderId
     */
    public function sendNewOrderSms($sellerId, $lastOrderId)
    {
        $order = $this->orderRepository->get($lastOrderId);

        if ($order->getId()) {
            $orderIncrementId = $order->getIncrementId();
            $this->sendNewOrderSmsToPartner($sellerId, $orderIncrementId);
            $customerMobile = $order->getBillingAddress()->getTelephone();
            if (isset($customerMobile)) {
                $trackShortLink = $this->createOrderTrackShortLink($order);
                $this->sendNewOrderSmsToCustomer($customerMobile, $orderIncrementId, $sellerId, $trackShortLink);
            }
        }
    }

    /**
     * @param $mobileNumber
     * @return string
     */
    public function getNumberWithCode($mobileNumber)
    {
        if (strlen($mobileNumber) == 10) {
            return '91'.$mobileNumber;
        }

        if (strlen($mobileNumber) > 10) {
            return '91'.substr($mobileNumber, -10);
        }

        return $mobileNumber;
    }

    /**
     * @param $sellerId
     * @param \Magento\Sales\Model\Order $order
     * @return void
     */
    public function sendOrderStatusChangeSms($sellerId, $order)
    {
        $supportText = $this->getSupportText($sellerId);

        $status = $order->getStatus();
        $orderIncrementId = $order->getIncrementId();
        $customerMobile = $order->getBillingAddress()->getTelephone();
        if (!isset($customerMobile) || $customerMobile == '') {
            return;
        }
        switch ($status) {
            case \Bakeway\Vendorapi\Model\OrderStatus::STATUS_PARTNER_ACCEPTED :
                //$message = self::ORDER_STATUS_ACCEPTED_SMS . $orderIncrementId . $supportText;
                //$this->sendSms($customerMobile, $message);
                break;
            case \Bakeway\Vendorapi\Model\OrderStatus::STATUS_PARTNER_REJECTED :
                $sellerInfo = $this->getSellerInformation($sellerId);
                if ($sellerInfo !== false) {
                    $partnerName = $sellerInfo->getData('business_name');
                } else {
                    $partnerName = 'partner bakery';
                }
                //$message = self::ORDER_STATUS_REJECTED_SMS . $orderIncrementId . $supportText;
                $statusHistories = $order->getStatusHistories();
                $lastHistory = array_pop($statusHistories);
                $reason = $lastHistory->getComment();
                $message = 'Your order with id '.$orderIncrementId.' has not been accepted by the '.$partnerName.', due to '.$reason.' Send your bank details for refund at support@bakeway.com. Meanwhile, we would be happy to assist you with a reorder from a different baker. (For any queries call us: +91-7447766330)';
                $this->sendSms($customerMobile, $message);
                break;
            case \Bakeway\Vendorapi\Model\OrderStatus::STATUS_ORDER_READY :
                //$message = self::ORDER_STATUS_READY_SMS . $orderIncrementId . $supportText;
                //$this->sendSms($customerMobile, $message);
                break;
            case \Bakeway\Vendorapi\Model\OrderStatus::STATUS_ORDER_OUT_FOR_DELIVERY :
                //$message = self::ORDER_STATUS_OUT_FOR_DELIVERY_SMS . $orderIncrementId . $supportText;
                //$this->sendSms($customerMobile, $message);
                break;
            case \Bakeway\Vendorapi\Model\OrderStatus::STATUS_ORDER_COMPLETE :
                $message = self::ORDER_STATUS_COMPLETED_SMS;
                $feedBackShortLink = $this->createFeedbackShortLink($order);
                $message = $message.$feedBackShortLink;
                $this->sendSms($customerMobile, $message);
                break;
        }
        return;
    }

    /**
     * @param int $sellerId
     * @return bool|\Magento\Framework\DataObject
     */
    public function getSellerInformation($sellerId) {
        $sellerColl = $this->marketplaceHelper->getSellerDataBySellerId($sellerId);

        if ($sellerColl->count() > 0) {
            $sellerObj = $sellerColl->getFirstItem();
            return $sellerObj;
        } else {
            return false;
        }
    }

    /**
     * @param $sellerPhoneNo
     * @param string|null $sellerBusinessName
     * @return mixed
     */
    public function getReplacedSupportText($sellerPhoneNo, $sellerBusinessName = null) {
        $supportText = self::SMS_SUPPORT_TEXT;
        $replacedText = str_replace("##phone_no##", $sellerPhoneNo, $supportText);

        if ($sellerBusinessName !== null) {
            $replacedText = str_replace("##business_name##", $sellerBusinessName, $replacedText);
        } else {
            $replacedText = str_replace("##business_name##", '', $replacedText);
        }
        return $replacedText;
    }

    /**
     * @param $sellerId
     * @return string
     */
    public function getSupportText($sellerId) {
        $seller = $this->getSellerInformation($sellerId);
        $supportText = '';

        if ($seller !== false) {
            $sellerMobile = $seller->getData('store_manager_mobile_no');
            $sellerBusinessName = $seller->getData('business_name');
            if (isset($sellerMobile) && $sellerMobile !== '') {
                $supportText = ". ".$this->getReplacedSupportText($sellerMobile);
            } else {
                $sellerMobile = $seller->getData('store_owner_mobile_no');
                $supportText = ". ".$this->getReplacedSupportText($sellerMobile);
            }

            if (isset($sellerBusinessName) && $sellerBusinessName !== '') {
                $supportText = ". ".$this->getReplacedSupportText($sellerMobile, $sellerBusinessName);
            }
        }
        return $supportText;
    }

    /*
     * list of active channel
     * @return mixed
     */
    public function getNotificationChanels()
    {
        return $this->scopeConfig->getValue('vendor_app_settings/bakeway_vendor_notification/noti_channel', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    /*
     * notification blast time
     * @return mixed
     */
    public function getNotificationChanelsBlasttime()
    {
        return $this->scopeConfig->getValue('vendor_app_settings/bakeway_vendor_notification/noti_alert_time', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    /*
     * push notification alert for acepting and rejecting order reminders
     */
    public function sendPushNotificationCron()
    {
        /**
         * Adding time validation here only 7AM to 11PM the notification should go
         */
        $currentDateTime = new \DateTime('now', new \DateTimezone("Asia/Kolkata"));
        $hourOfDay = $currentDateTime->format('H');
        if ($hourOfDay > 22 || $hourOfDay < 7) {
            return;
        }

        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/sendPushNotificationCron.log');
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);
        $logger->info("start");
        $collection =$this->ordercollectionFactory->create()
            ->addFieldToFilter("state",\Magento\Sales\Model\Order::STATE_NEW);
        $collection->getSelect()->join( array('mo'=>'marketplace_orders'), 'main_table.entity_id = mo.order_id', array('mo.seller_id'));
        $collection->getSelect()->join( array('vdd'=>'vendor_device_data'), 'mo.seller_id = vdd.seller_id', array('vdd.seller_id','vdd.platform_endpoint','vdd.platform'));
        $collection->getSelect()->join( array('mu'=>'marketplace_userdata'), 'vdd.seller_id = mu.seller_id', array('store_owner_mobile_no','store_owner_email','store_owner_name'));
        $collection->addFieldToFilter('is_dnd', '0');
        $collection->addFieldToFilter('platform_endpoint', ['neq' => Null]);
        $collection->addFieldToSelect(array("entity_id","increment_id"));
        $collection->getSelect()->columns('count(order_id) as total_order')
            ->group(array('seller_id'));

        $activeChannel = $this->getNotificationChanels();
        $activeChannel = explode(",",$activeChannel);

        $channelNoiification = $channelSmsNoiification = $channelEmailNoiification = $naNoiification = false;
        if(count($activeChannel)>0){

            /*
             *channel :NA
             */
            if(in_array("4",$activeChannel)){
                $naNoiification = true;
            }


            /*
             *channel :Notification
             */
            if(in_array("1",$activeChannel)){
                $channelNoiification = true;
            }

            /*
             *channel :SMS
             */
            if(in_array("2",$activeChannel)){
                $channelSmsNoiification = true;
            }

            /*
             * channel :Email
             */

            if(in_array("3",$activeChannel)){
                $channelEmailNoiification = true;
            }

        }

        if($naNoiification === true){
            return;
        }


        $count = $collection->count();
        if($count > 0) {
            if($channelNoiification === true){
                $adnroidArn = $this->getPlatformApplicationArn('android');
                $iosArn = $this->getPlatformApplicationArn('ios');
                $availableEndpointsAndr = $this->getAvailablePlatformEndpoints($adnroidArn);
                $availableEndpointsIos = $this->getAvailablePlatformEndpoints($iosArn);
                foreach ($collection as $endpoint) {
                    try {
                        $logger->info($endpoint['total_order']);
                        $platform = $endpoint->getPlatform();
                        $platformEndpoint = $endpoint->getPlatformEndpoint();
                        $isEnabled = false;
                        if ($platform == strtolower('ios')) {
                            //if (in_array($platformEndpoint, $availableEndpointsIos)) {
                            $isEnabled = $this->checkEndpointEnabled($platformEndpoint);
                            //}
                            $messageText = "You have ".$endpoint['total_order']." orders in awaiting confirmation state, Your customers are delighted when you Accept orders quickly.";
                            $data = json_encode(array("aps" => array("alert" => $messageText, "title" => $endpoint['total_order']. " orders pending", "sound" => "new_order.aiff"),  "type" => self::ORDER_TYPE_REMINDER));
                            $push_message = json_encode(array($this->getIosKey() => $data));
                        } else {
                            //if (in_array($platformEndpoint, $availableEndpointsAndr)) {
                            $isEnabled = $this->checkEndpointEnabled($platformEndpoint);
                            //}
                            $messageText = "You have ".$endpoint['total_order']." orders in awaiting confirmation state. Your customers are delighted when you Accept orders quickly.";
                            $data = json_encode(array("data" => array("message" => $messageText, "title" => $endpoint['total_order']. " orders pending", "soundName" => "new_order.mp3", "type" => self::ORDER_TYPE_REMINDER)));
                            $push_message = json_encode(array("default" => "test", "GCM" => $data));
                        }
                        if ($isEnabled === false) {
                            $device = $this->deviceData->load($endpoint->getId());
                            $device->delete();
                        } else {
                            $snsClient = $this->getSnsClient();
                            $result = $snsClient->publish(array('Message' => $push_message,
                                'TargetArn' => $endpoint->getPlatformEndpoint(), 'MessageStructure' => 'json'));
                        }
                    } catch (AwsException $e) {
                        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/pushnoti_accept_reject_order.log');
                        $logger = new \Zend\Log\Logger();
                        $logger->addWriter($writer);
                        $logger->info($e->getMessage());

                    } catch (\Exception $e) {
                        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/pushnoti_accept_reject_order.log');
                        $logger = new \Zend\Log\Logger();
                        $logger->addWriter($writer);
                        $logger->info($e->getMessage());
                    }

                }

            }
            if($channelSmsNoiification === true){
                foreach ($collection as $item) {
                    $sellerMobile = $item['store_owner_mobile_no'];
                    //$sellerMobile = '9503678497';
                    $message = "Dear Partner, You have ".$item["total_order"]." customer orders in Awaiting Confirmation State.
                    Please Accept the orders to stop receiving this message.";

                    try{
                        $this->sendSms($sellerMobile, $message);
                    }catch(Exception $e){
                        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/pushnoti_accept_reject_order.log');
                        $logger = new \Zend\Log\Logger();
                        $logger->addWriter($writer);
                        $logger->info($e->getMessage());
                    }

                }

            }


            if($channelEmailNoiification === true){
                foreach ($collection as $item) {
                    $email = $item['store_owner_email'];
                    $supportEmail = 'support@bakeway.com';
                    //$email = "kushagra@relfor.com";
                    $content = "Dear Partner, You have " .$item["total_order"]." customer orders in Awaiting Confirmation State. Please Accept the orders to stop receiving this message.";
                    $subject = "Dear Partner, You have " .$item["total_order"]." customer orders in Awaiting Confirmation State";
                    $supportSubject = "Order for " .$item["store_owner_name"]. " Order Id " .$item["order_ids"];
                    try{
                        $this->orderStatusEmail->sendNotificationEmail($email,$item["total_order"],$item["order_ids"],$item["store_owner_name"],$content, $subject);
                        /**
                         * comma seperated email id's
                         */
                        $emailsIds = $this->notificationReciverEmails();
                        if(!empty($emailsIds)){
                            foreach($emailsIds as $emailsId){
                                $this->orderStatusEmail->sendNotificationEmail($emailsId,$item["total_order"],$item["order_ids"],$item["store_owner_name"],$content, $supportSubject);
                          }
                        }
                    }catch(Exception $e){
                        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/pushnoti_accept_reject_order.log');
                        $logger = new \Zend\Log\Logger();
                        $logger->addWriter($writer);
                        $logger->info($e->getMessage());
                    }
                }

            }

        }else{

            echo __("Sorry, no Records Found");
        }

        return;
    }

    /*
     * send order notification for accpet prder state
     */
    public function sendPushNotificationForUpcomingOrderCron()
    {
        /*
        * from date and to date calculation
        */

        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/sendPushNotificationForUpcomingOrderCron.log');
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);
        $logger->info('----------------------------------------------');
        $logger->info("start log for upcoming order");

        $fromDate =  $this->timezoneInterface->date()->format('Y-m-d');
        $fromDate = date('Y-m-d H:i:s', strtotime($fromDate));
        $toDate = date("Y-m-d H:i:s",strtotime("+2 day",strtotime($fromDate)));
        $collection = $this->ordercollectionFactory->create()->addFieldToFilter("state",\Magento\Sales\Model\Order::STATE_PROCESSING);
        $collection =$this->ordercollectionFactory->create()->addFieldToFilter("status",\Bakeway\Vendorapi\Model\OrderStatus::STATUS_PARTNER_ACCEPTED);
        $collection->getSelect()->join( array('mo'=>'marketplace_orders'), 'main_table.entity_id = mo.order_id', array('mo.seller_id'));
        $collection->getSelect()->join( array('vdd'=>'vendor_device_data'), 'mo.seller_id = vdd.seller_id', array('vdd.seller_id','vdd.platform_endpoint','vdd.platform'));
        $collection->addFilterToMap('delivery_time', 'main_table.delivery_time');
        $collection->getSelect()->join( array('mu'=>'marketplace_userdata'), 'vdd.seller_id = mu.seller_id', array('store_owner_mobile_no','store_owner_email','store_owner_name'));
        $collection->addFieldToFilter('is_dnd', '0');
        $collection->addFieldToFilter('platform_endpoint', ['neq' => Null]);
        $collection->addFieldToSelect(array("increment_id","delivery_time"));
        $collection->getSelect()->columns('group_concat(increment_id) as order_ids');
        $collection->getSelect()->columns('count(order_id) as total_order')
            ->group(array('seller_id'));
        $collection->addAttributeToFilter('delivery_time', array('from'=>$fromDate, 'to'=>$toDate));

        $activeChannel = $this->getNotificationChanels();
        $activeChannel = explode(",",$activeChannel);

        $channelNoiification = $channelSmsNoiification = $channelEmailNoiification = $naNoiification = false;

        if(count($activeChannel)>0){

            /*
             *channel :NA
             */
            if(in_array("4",$activeChannel)){
                $naNoiification = true;
            }


            /*
             *channel :Notification
             */
            if(in_array("1",$activeChannel)){
                $channelNoiification = true;
            }

            /*
             *channel :SMS
             */
            if(in_array("2",$activeChannel)){
                $channelSmsNoiification = true;
            }

            /*
             * channel :Email
             */

            if(in_array("3",$activeChannel)){
                $channelEmailNoiification = true;
            }

        }
        if($naNoiification === true){
            return;
        }

        $count = $collection->count();
        if($count > 0) {

            if($channelNoiification === true){

                $logger->info('mobile pish noti channel is active');
                $adnroidArn = $this->getPlatformApplicationArn('android');
                $iosArn = $this->getPlatformApplicationArn('ios');
                $availableEndpointsAndr = $this->getAvailablePlatformEndpoints($adnroidArn);
                $availableEndpointsIos = $this->getAvailablePlatformEndpoints($iosArn);
                foreach ($collection as $endpoint) {
                    try {
                        $logger->info('inside collection');
                        $logger->info('total order'.$endpoint['total_order']);
                        $platform = $endpoint->getPlatform();
                        $platformEndpoint = $endpoint->getPlatformEndpoint();
                        $isEnabled = false;
                        if ($platform == strtolower('ios')) {
                            //if (in_array($platformEndpoint, $availableEndpointsIos)) {
                            $isEnabled = $this->checkEndpointEnabled($platformEndpoint);
                            //}
                            $messageText = "You have ".$endpoint['total_order']." upcoming orders to fulfill tomorrow. Build customer trust on you by serving them On-Time";
                            $logger->info('ios-orders'.$endpoint->getData('order_ids'));
                            $orderArray = explode(",",$endpoint->getData('order_ids'));
                            $data = json_encode(array("aps" => array("alert" => $messageText, "orders"=> $orderArray , "title" => $endpoint['total_order']. " upcoming orders", "sound" => "new_order.aiff") ,  "type" => self::ORDER_TYPE_UPCOMING));
                            $push_message = json_encode(array($this->getIosKey() => $data));

                        } else {
                            // if (in_array($platformEndpoint, $availableEndpointsAndr)) {
                            $isEnabled = $this->checkEndpointEnabled($platformEndpoint);
                            //}
                            $logger->info("and-orders".$endpoint->getData('order_ids'));
                            $messageText = "You have ".$endpoint['total_order']." upcoming orders to fulfill tomorrow. Build customer trust on you by serving them On-Time";
                            $orderArray = explode(",",$endpoint->getData('order_ids'));
                            $data = json_encode(array("data" => array("message" => $messageText, "orders"=> $orderArray ,"title" => $endpoint['total_order']. " upcoming orders", "soundName" => "new_order.mp3", "type" => self::ORDER_TYPE_UPCOMING)));
                            $push_message = json_encode(array("default" => "test", "GCM" => $data));
                        }
                        if ($isEnabled === false) {
                            $device = $this->deviceData->load($endpoint->getId());
                            $device->delete();
                        } else {
                            $snsClient = $this->getSnsClient();
                            $result = $snsClient->publish(array('Message' => $push_message,
                                'TargetArn' => $endpoint->getPlatformEndpoint(), 'MessageStructure' => 'json'));
                        }
                    } catch (AwsException $e) {
                        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/pushnoti_outfordelivery_order.log');
                        $logger = new \Zend\Log\Logger();
                        $logger->addWriter($writer);
                        $logger->info($e->getMessage());

                    } catch (\Exception $e) {
                        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/pushnoti_outfordelivery_order.log');
                        $logger = new \Zend\Log\Logger();
                        $logger->addWriter($writer);
                        $logger->info($e->getMessage());
                    }

                }
            }


            if($channelSmsNoiification === true){
                $logger->info("sms is active");
                foreach ($collection as $item) {
                    $sellerMobile = $item['store_owner_mobile_no'];
                    //$sellerMobile = '9503678497';
                    $message = "Dear Partner, You have ".$item["total_order"]." upcoming orders  to fulfill tomorrow. Build customer trust on you by serving them On-Time";
                    $logger->info($sellerMobile);
                    try{
                        $this->sendSms($sellerMobile, $message);
                        $logger->info('sms sent');
                    }catch(Exception $e){
                        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/pushnoti_outfordelivery_order.log');
                        $logger = new \Zend\Log\Logger();
                        $logger->addWriter($writer);
                        $logger->info($e->getMessage());
                    }

                }

            }

            if($channelEmailNoiification === true){
                $logger->info("email is active");
                foreach ($collection as $item) {
                    $email = $item['store_owner_email'];
                    $supportEmail = 'support@bakeway.com';
                    //$email = "kushagra@relfor.com";
                    $content = "Dear Partner, You have " .$item["total_order"]." orders upcoming orders to fulfill tomorrow. Build customer trust on you by serving them On-Time";
                    $subject =  "Dear Partner, You have " .$item["total_order"]." orders upcoming orders to fulfill tomorrow.";
                    $supportSubject = "Order for " .$item["store_owner_name"]. " Order Id " .$item["order_ids"];
                    try{
                        $logger->info("emailsent");
                        $this->orderStatusEmail->sendNotificationEmail($email,$item["total_order"],$item["order_ids"],$item["store_owner_name"],$content ,$subject);
                        /**
                         * comma seperated email id's
                         */
                        $emailsIds = $this->notificationReciverEmails();
                        if(!empty($emailsIds)){
                            foreach($emailsIds as $emailsId){
                                $this->orderStatusEmail->sendNotificationEmail($emailsId,$item["total_order"],$item["order_ids"],$item["store_owner_name"],$content, $supportSubject);
                            }
                        }
                    }catch(Exception $e){
                        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/pushnoti_outfordelivery_order.log');
                        $logger = new \Zend\Log\Logger();
                        $logger->addWriter($writer);
                        $logger->info($e->getMessage());
                    }
                }

            }
        }else{

            echo __("Sorry, no Records Found");
        }

        return;
    }

    /**
     * @return mixed
     */
    public function getIosKey()
    {
        $notiIosKey = $this->scopeConfig->getValue('vendor_app_settings/bakeway_vendor_notification/noti_ios_key', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        return $notiIosKey;
    }

    public function createOrderTrackShortLink($order = null) {
        $trackingBaseUrl = $this->getOrderTrackingUrl();
        $token = $order->getData('order_tracking_token');
        $longUrl = $trackingBaseUrl.'?token='.$token;
        $shortUrl = $this->makeBitlyShortUrl($longUrl, 'json');
        return $shortUrl;
    }

    public function createFeedbackShortLink($order = null) {
        $feedbackBaseUrl = $this->getReactFeedbackUrl();
        $token = $order->getData('order_review_token');
        $longUrl = $feedbackBaseUrl.'?token='.$token;
        $shortUrl = $this->makeBitlyShortUrl($longUrl, 'json');
        return $shortUrl;
    }

    public function makeBitlyShortUrl($url, $format = 'xml',$version = '2.0.1')
    {
        $login = "o_5r9g9msngh";
        $appKey = "R_9de2f89a06eb4906864291d2c45bff05";

        //create the URL
        $bitly = 'http://api.bit.ly/shorten?version='.$version.'&longUrl='.urlencode($url).'&login='.$login.'&apiKey='.$appKey.'&format='.$format;

        //get the url
        $response = file_get_contents($bitly);

        //parse depending on desired format
        if (strtolower($format) == 'json') {
            $json = @json_decode($response,true);
            return $json['results'][$url]['shortUrl'];
        } else {
            $xml = simplexml_load_string($response);
            return 'http://bit.ly/'.$xml->results->nodeKeyVal->hash;
        }
    }

    public function getReactFeedbackUrl()
    {
        return $this->scopeConfig->getValue(
            self::REACT_URL, \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getOrderTrackingUrl()
    {
        return $this->scopeConfig->getValue('react_site_settings/react_settings_general/guest_track_url',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    /**
     * @vendor notification emails alerts
     * @return string
     */
    public function notificationReciverEmails(){
        $notificationEmails = $this->scopeConfig->getValue('vendor_app_settings/bakeway_vendor_notification/notification_email', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $notificationEmails = explode(",",$notificationEmails);
        return $notificationEmails;

    }

    
}