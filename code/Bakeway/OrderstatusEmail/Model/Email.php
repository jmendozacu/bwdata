<?php

/**
 * Copyright © 2015 Bakeway. All rights reserved.
 */

namespace Bakeway\OrderstatusEmail\Model;

use Bakeway\Vendorapi\Model\OrderStatus as BakewayOrderStatus;
use Magento\Sales\Model\Order\Address\Renderer as AddressRenderer;
use Bakeway\OrderstatusEmail\Block\Order\Email\Items as OrderstatusEmail;
use Magento\Catalog\Api\ProductRepositoryInterface as ProductRepositoryInterface;
use Bakeway\ReviewRating\Helper\Data as ReviewRatingHelper;
use Magento\Framework\App\Config\ScopeConfigInterface as ScopeConfigInterface;
use Bakeway\Partnerlocations\Helper\Data as PartnerlocationHelper;
use Magento\Variable\Model\Variable as VariableClass;
use Bakeway\Cities\Helper\Data as Citieshelper;
use Webkul\Marketplace\Model\SellerFactory as Sellerinstance;

/**
 * Commisontab commison model
 */
class Email extends \Magento\Framework\Model\AbstractModel {
    CONST REACT_URL = "reviewrating/review_setting/react_url";

    /**
     * 
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var \Webkul\Marketplace\Helper\Data
     */
    protected $marketplacehelper;

    /**
     * 
     * @var \Bakeway\Vendorapi\Model\OrderStatus
     */
    protected $bakewayOrderStatus;

    /**
     * 
     * @var \Magento\Framework\App\ObjectManager
     */
    protected $objectManager;

    /**
     * @var OrderEmailSender
     */
    protected $orderEmailSender;

    /**
     * @var AddressRenderer
     */
    protected $addressRenderer;

    /**
     * @var OrderstatusEmail
     */
    protected $orderstatusEmail;

    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepositoryInterface;

    /**
     * @var ReviewRatingHelper
     */
    protected $reviewratinghelper;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeconfig;

    /**
     * @var PartnerlocationHelper
     */
    protected $partnerlocationHelper;

    /**
     * @var VariableClass
     */
    protected $variableClass;

    /**
     * @var Citieshelper
     */
    protected $citieshelper;

    /**
     * @var Sellerinstance
     */
    protected $sellerinstance;


    /**
     * Email constructor.
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Webkul\Marketplace\Helper\Data $marketplacehelper
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
     * @param BakewayOrderStatus $bakewayOrderStatus
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param AddressRenderer $addressRenderer
     * @param OrderstatusEmail $orderstatusEmail
     * @param ProductRepositoryInterface $productRepositoryInterface
     * @param ReviewRatingHelper $reviewratinghelper
     * @param ScopeConfigInterface $scopeconfig
     * @param PartnerlocationHelper $partnerlocationHelper
     * @param VariableClass $variableClass
     * @param Citieshelper $citieshelper
     * @param Sellerinstance $sellerinstance
     */
    public function __construct(
    \Magento\Framework\Model\Context $context,
    \Magento\Framework\Registry $registry,
    \Webkul\Marketplace\Helper\Data $marketplacehelper,
    \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
    BakewayOrderStatus $bakewayOrderStatus,
    \Magento\Framework\ObjectManagerInterface $objectManager,
    AddressRenderer $addressRenderer,
    OrderstatusEmail $orderstatusEmail,
    ProductRepositoryInterface $productRepositoryInterface,
    ReviewRatingHelper $reviewratinghelper,
    ScopeConfigInterface $scopeconfig,
    PartnerlocationHelper $partnerlocationHelper,
    VariableClass $variableClass,
    Citieshelper $citieshelper,
    Sellerinstance $sellerinstance
    ) {
        $this->marketplacehelper = $marketplacehelper;
        $this->orderRepository = $orderRepository;
        $this->bakewayOrderStatus = $bakewayOrderStatus;
        $this->objectManager = $objectManager;
        $this->addressRenderer = $addressRenderer;
        $this->orderstatusEmail = $orderstatusEmail;
        $this->productRepositoryInterface = $productRepositoryInterface;
        $this->reviewratinghelper = $reviewratinghelper;
        $this->scopeconfig = $scopeconfig;
        $this->partnerlocationHelper = $partnerlocationHelper;
        $this->variableClass = $variableClass;
        $this->citieshelper = $citieshelper;
        $this->sellerinstance = $sellerinstance;
    }

    public function getAdminemail() {
        $adminStoremail = $this->marketplacehelper->getAdminEmailId();
        $adminEmail = $adminStoremail ? $adminStoremail : $helper->getDefaultTransEmailId();
        return $adminEmail;
    }

    public function getAdminusername() {
        $adminUsername =  $this->marketplacehelper->getSalesConfigname();
        return $adminUsername;
    }

    /*
    * return react site url
    */
    public function getReactFeedbackUrl()
    {
        return $this->scopeconfig->getValue(
            self::REACT_URL, \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );


    }

    public function accpetUserEmail($orderId) {

        $senderInfo = [];
        $receiverInfo = [];
        $guestcustomerName['firstname']= $guestcustomerName['lastname'] = "";
        $order = $this->orderRepository->get($orderId);

        $receiverInfo = [
            'name' => $order['customer_firstname'] . " " . $order['customer_lastname'],
            'email' => $order['customer_email'],
        ];
        $senderInfo = [
            'name' => $this->getAdminusername(),
            'email' => $this->getAdminemail(),
        ];
        $deliveryAddressObj = $order->getShippingAddress();
        $deliveryAddress = $this->addressRenderer->format($deliveryAddressObj, 'html');

        $guestcustomerName = $order->getBillingAddress();
        $guestcustomerFullName = $guestcustomerName['firstname']." ".$guestcustomerName['lastname'];

        //echo "<pre>";print_r($deliveryAddress);exit;
        $emailTemplateVariables = [];
        $emailTempVariables['myvar2'] = $order['customer_firstname'] . " " . $order['customer_lastname'];
        $emailTempVariables['myvar3'] = $order['increment_id'];
        $emailTempVariables['myvar4'] = $order->getStatusLabel();
        $emailTempVariables["shippingAddress"] = $deliveryAddress;
        $emailTempVariables['order'] = $order;
        $emailTempVariables['subject'] = array('orderid' => $order['increment_id'], "status" => $order->getStatusLabel());
        $emailTempVariables['guest_name'] = $guestcustomerFullName;

        try {
            $this->objectManager->get(
                    'Bakeway\OrderstatusEmail\Helper\Email'
            )->sendOrderStatusEmailToUser(
                    $emailTempVariables, $senderInfo, $receiverInfo
            );
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
    }

    public function rejectUserEmail($orderId) {

        $senderInfo = [];
        $receiverInfo = $sellerInfo = [];
        $userName = "";
        $guestcustomerName['firstname']= $guestcustomerName['lastname'] = "";
        $business_name = $phone_number = $street_address = "";
        $firstItem =  $firstItemSku = [];
        $order = $this->orderRepository->get($orderId);

        $receiverInfo = [
            'name' => $order['customer_firstname'] . " " . $order['customer_lastname'],
            'email' => $order['customer_email'],
        ];
        $senderInfo = [
            'name' => $this->getAdminusername(),
            'email' => $this->getAdminemail(),
        ];

        if(!empty($order['customer_id']))
        {
            $userName = $order['customer_firstname'] . " " . $order['customer_lastname'];
        }else
        {
            $userName = 'Guest';
        }

       $items = $order->getAllItems();
        foreach ($items as $item) {
            if ($item->getParentItem()) {
                echo $item->getName();
                continue;
            }
            $firstItem[] = $item->getName();
            $firstItemSku[] = $item->getSku();
           
        }
         $firstItemSku = current($firstItemSku);
         $productObj = $this->productRepositoryInterface->get($firstItemSku);
         $productId = $productObj->getId();


        /*         * fetching seller info* */
        $sellerInfo = $this->orderstatusEmail->getSellerInfo($productId,$order);
        if (!empty($sellerInfo)):
            $business_name = $sellerInfo['business_name'];
            //$phone_number = $sellerInfo['phone_number'];
            $phone_number = $this->getBakewayPhoneNumber();
            if (isset($sellerInfo['street_address'])) {
                $street_address = $sellerInfo['street_address'];
            } else {
                $street_address = "";
            }

        endif;
        /*         * fetching seller info* */
        /*         * fetching reject reason* */
        $history = $this->getStatusHistory($order->getId());
        /*         * fetching reject reason* */
        $productName = current($firstItem);

        $guestcustomerName = $order->getBillingAddress();
        $guestcustomerFullName = $guestcustomerName['firstname']." ".$guestcustomerName['lastname'];

        $emailTemplateVariables = [];
        $emailTempVariables['myvar2'] = $userName;
        $emailTempVariables['myvar3'] = $order['increment_id'];
        $emailTempVariables['myvar4'] = $order->getStatusLabel();
        $emailTempVariables['product_name'] = $productName;
        $emailTempVariables['business_name'] = $business_name;
        $emailTempVariables['phone_number'] = $phone_number;
        $emailTempVariables['street_address'] = $street_address;
        $emailTempVariables['reject_reason'] = $history;
        $emailTempVariables['subject'] = array('orderid' => $order['increment_id'], "status" => $order->getStatusLabel());
        $emailTempVariables['guest_name'] = $guestcustomerFullName;

        try {
            $this->objectManager->get(
                    'Bakeway\OrderstatusEmail\Helper\Email'
            )->sendOrderRejectStatusEmailToUser(
                    $emailTempVariables, $senderInfo, $receiverInfo
            );
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
    }

    public function sendOrderReadyforpickupStatusEmailToUser($orderId) {

        $senderInfo = [];
        $receiverInfo = $sellerInfo = [];
        $userName = "";
        $guestcustomerName['firstname']= $guestcustomerName['lastname'] = "";
        $business_name = $phone_number = $street_address = "";
        $firstItem =  $firstItemSku = [];
        $order = $this->orderRepository->get($orderId);

        $receiverInfo = [
            'name' => $order['customer_firstname'] . " " . $order['customer_lastname'],
            'email' => $order['customer_email'],
        ];
        $senderInfo = [
            'name' => $this->getAdminusername(),
            'email' => $this->getAdminemail(),
        ];

        if(!empty($order['customer_id']))
        {
            $userName = $order['customer_firstname'] . " " . $order['customer_lastname'];
        }else
        {
            $userName = 'Guest';
        }

        $items = $order->getAllItems();
        foreach ($items as $item) {
            if ($item->getParentItem()) {
                echo $item->getName();
                continue;
            }
            $firstItem[] = $item->getName();
            $firstItemSku[] = $item->getSku();
           
        }
         $firstItemSku = current($firstItemSku);
         $productObj = $this->productRepositoryInterface->get($firstItemSku);
         $productId = $productObj->getId();

        /*         * fetching seller info* */
        $sellerInfo = $this->orderstatusEmail->getSellerInfo($productId,$order);
        if (!empty($sellerInfo)):
            $business_name = $sellerInfo['business_name'];
            //$phone_number = $sellerInfo['phone_number'];
            $phone_number = $this->getBakewayPhoneNumber();
            $street_address = $sellerInfo['street_address'];
        endif;
        /*         * fetching seller info* */

        $productName = current($firstItem);
        $guestcustomerName = $order->getBillingAddress();
        $guestcustomerFullName = $guestcustomerName['firstname']." ".$guestcustomerName['lastname'];

        $emailTemplateVariables = [];
        $emailTempVariables['myvar2'] = $userName;
        $emailTempVariables['myvar3'] = $order['increment_id'];
        $emailTempVariables['myvar4'] = $order->getStatusLabel();
        $emailTempVariables['product_name'] = $productName;
        $emailTempVariables['business_name'] = $business_name;
        $emailTempVariables['phone_number'] = $phone_number;
        $emailTempVariables['street_address'] = $street_address;
        $emailTempVariables['subject'] = array('orderid' => $order['increment_id'], "status" => $order->getStatusLabel());
        $emailTempVariables['guest_name'] = $guestcustomerFullName;

        try {
            $this->objectManager->get(
                    'Bakeway\OrderstatusEmail\Helper\Email'
            )->sendOrderReadyforpickupStatusEmailToUser(
                    $emailTempVariables, $senderInfo, $receiverInfo
            );
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
    }

    public function sendOrderOutForDeliveryStatusEmailToUser($orderId) {

        $senderInfo = [];
        $receiverInfo = $sellerInfo = [];
        $userName = "";
        $business_name = $phone_number = $street_address = "";
        $guestcustomerName['firstname']= $guestcustomerName['lastname'] = "";
        $firstItem =  $firstItemSku = [];
        $order = $this->orderRepository->get($orderId);

        $receiverInfo = [
            'name' => $order['customer_firstname'] . " " . $order['customer_lastname'],
            'email' => $order['customer_email'],
        ];


        $senderInfo = [
            'name' => $this->getAdminusername(),
            'email' => $this->getAdminemail(),
        ];
        if(!empty($order['customer_id']))
        {
            $userName = $order['customer_firstname'] . " " . $order['customer_lastname'];
        }else
        {
            $userName = 'Guest';
        }
        $items = $order->getAllItems();
        foreach ($items as $item) {
            if ($item->getParentItem()) {
                echo $item->getName();
                continue;
            }
            $firstItem[] = $item->getName();
            $firstItemSku[] = $item->getSku();
           
        }
         $firstItemSku = current($firstItemSku);
         $productObj = $this->productRepositoryInterface->get($firstItemSku);
         $productId = $productObj->getId();

        /*         * fetching seller info* */
        $sellerInfo = $this->orderstatusEmail->getSellerInfo($productId,$order);
        if (!empty($sellerInfo)):
            $business_name = $sellerInfo['business_name'];
            //$phone_number = $sellerInfo['phone_number'];
            $phone_number = $this->getBakewayPhoneNumber();
            $street_address = $sellerInfo['street_address'];
        endif;
        /*         * fetching seller info* */

        $productName = current($firstItem);
        $guestcustomerName = $order->getBillingAddress();
        $guestcustomerFullName = $guestcustomerName['firstname']." ".$guestcustomerName['lastname'];
        $emailTemplateVariables = [];
        $emailTempVariables['myvar2'] = $userName;
        $emailTempVariables['myvar3'] = $order['increment_id'];
        $emailTempVariables['myvar4'] = $order->getStatusLabel();
        $emailTempVariables['product_name'] = $productName;
        $emailTempVariables['business_name'] = $business_name;
        $emailTempVariables['phone_number'] = $phone_number;
        $emailTempVariables['street_address'] = $street_address;
        $emailTempVariables['subject'] = array('orderid' => $order['increment_id'], "status" => $order->getStatusLabel());
        $emailTempVariables['guest_name'] = $guestcustomerFullName;

        try {
            $this->objectManager->get(
                    'Bakeway\OrderstatusEmail\Helper\Email'
            )->sendOrderOutForDeliveryStatusEmailToUser(
                    $emailTempVariables, $senderInfo, $receiverInfo
            );
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
    }

    public function sendOrderCompleteStatusEmailToUser($orderId) {

        $senderInfo = [];
        $receiverInfo = $sellerInfo = [];
        $userName = "";
        $business_name = $phone_number = $street_address = "";
        $guestcustomerName['firstname']= $guestcustomerName['lastname'] = "";
         $firstItem =  $firstItemSku = [];
        $order = $this->orderRepository->get($orderId);

        $receiverInfo = [
            'name' => $order['customer_firstname'] . " " . $order['customer_lastname'],
            'email' => $order['customer_email'],
        ];
        $senderInfo = [
            'name' => $this->getAdminusername(),
            'email' => $this->getAdminemail(),
        ];

        if(!empty($order['customer_id']))
        {
            $userName = $order['customer_firstname'] . " " . $order['customer_lastname'];
        }else
        {
            $userName = 'Guest';
        }

        $orderReviewToken = $order->getOrderReviewToken();

        $ratingReviewUrl = $this->getReactFeedbackUrl();


        $items = $order->getAllItems();
        foreach ($items as $item) {
            if ($item->getParentItem()) {
                echo $item->getName();
                continue;
            }
            $firstItem[] = $item->getName();
            $firstItemSku[] = $item->getSku();
           
        }
         $firstItemSku = current($firstItemSku);
         $productObj = $this->productRepositoryInterface->get($firstItemSku);
         $productId = $productObj->getId();

        /*         * fetching seller info* */
        $sellerInfo = $this->orderstatusEmail->getSellerInfo($productId,$order);
        $sellerInfo['street_address'] = "";
        if (!empty($sellerInfo)):
            $business_name = $sellerInfo['business_name'];
            //$phone_number = $sellerInfo['phone_number'];
            $phone_number = $this->getBakewayPhoneNumber();
            $street_address = $sellerInfo['street_address'];
        endif;
        /*         * fetching seller info* */

        $productName = current($firstItem);
        $guestcustomerName = $order->getBillingAddress();
        $guestcustomerFullName = $guestcustomerName['firstname']." ".$guestcustomerName['lastname'];

        $emailTemplateVariables = [];
        $emailTempVariables['myvar2'] = $userName;
        $emailTempVariables['myvar3'] = $order['increment_id'];
        $emailTempVariables['myvar4'] = $order->getStatusLabel();
        $emailTempVariables['product_name'] = $productName;
        $emailTempVariables['business_name'] = $business_name;
        $emailTempVariables['phone_number'] = $phone_number;
        $emailTempVariables['street_address'] = $street_address;
        $emailTempVariables['subject'] = array('orderid' => $order['increment_id'], "status" => $order->getStatusLabel());
        $emailTempVariables['guest_name'] = $guestcustomerFullName;
        $emailTempVariables['rating_url'] = $ratingReviewUrl."?token=".$orderReviewToken;

        try {
            $this->objectManager->get(
                    'Bakeway\OrderstatusEmail\Helper\Email'
            )->sendOrderCompleteStatusEmailToUser(
                    $emailTempVariables, $senderInfo, $receiverInfo
            );
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
    }

    public function getStatusHistory($orderid) {
        $comment = "";
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $order = $objectManager->create('\Magento\Sales\Model\Order\Status\History')
                        ->getCollection()
                        ->addFieldToFilter("parent_id", array('eq' => $orderid))
                        ->addFieldToFilter("status", array('eq' => BakewayOrderStatus::STATUS_PARTNER_REJECTED))->getLastItem();

        if (count($order) > 0) {
            $comment = $order['comment'];
        }

        if (!empty($comment)) {
            return $comment;
        } else {
            return;
        }
    }


     public function sendCommissionLogEmail($rate,$logemail,$sellerName,$date ,$sellerId ,$sellerBN ,$cityName)
    {  
        $senderInfo = [];
        $senderInfo = [
            'name' => $this->getAdminusername(),
            'email' => $this->getAdminemail(),
        ];
        $emailTemplateVariables = [];
        $emailTempVariables['rate'] = $rate;
        $emailTempVariables['logemail'] = $logemail;
        $emailTempVariables['date'] = $date;
        $emailTempVariables['sellerid'] = $sellerId;
        $emailTempVariables['sellername'] = $sellerName;
        $emailTempVariables['businessname'] = $sellerBN;
        $emailTempVariables['city'] = $cityName;

        try {
            $all_EmailAddress = $this->objectManager->get('Bakeway\CommissionLog\Helper\Email')->getCommisionLogEmail();
            $all_EmailAddress = explode(",",$all_EmailAddress);

           foreach($all_EmailAddress as $emailAddress){

                  $receiverInfo = [
                        'name' => 'test',
                        'email' => $emailAddress,
                        ];
                    $this->objectManager->get(
                                    'Bakeway\CommissionLog\Helper\Email'
                            )->sendOrderStatusEmailToUser(
                                    $emailTempVariables, $senderInfo, $receiverInfo
                            );

            }

        
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
    }


    /**
     * @param $email
     * @param $totalOrder
     * @param $orderIds
     * @param $storeOwnerName
     * @param $content
     */
    public function sendNotificationEmail($email,$totalOrder,$orderIds,$storeOwnerName,$content,$subject) {

        $senderInfo = [];
        $receiverInfo = [];

        $guestcustomerName['firstname']= $guestcustomerName['lastname'] = "";
        $receiverInfo = [
            'name' => $storeOwnerName,
            'email' => $email,
        ];

        $senderInfo = [
            'name' => $this->getAdminusername(),
            'email' => $this->getAdminemail(),
        ];

        $emailTemplateVariables = [];
        $emailTempVariables['orders_array'] = $orderIds;
        $emailTempVariables['order_count'] = $totalOrder;
        $emailTempVariables['vendor_name'] = $storeOwnerName;
        $emailTempVariables['content'] = $content;
        $emailTempVariables['subject'] = $subject;

        try {
            $this->objectManager->get(
                'Bakeway\OrderstatusEmail\Helper\Email'
            )->sendOrderNotificationEmailToSeller(
                $emailTempVariables, $senderInfo, $receiverInfo
            );
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
    }


    /**
     * @param $content
     * @param $subject
     */
    public function sendUrlupdateAlertEmail($content,$subject ,$sellerId) {
        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/storealert.log');
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);

        $storeOwnerName = "Sales Team";
        $email = $this->partnerlocationHelper->getUrlUpdateALertTriggerReceivers();
        $senderInfo = [];
        $receiverInfo = [];

        $guestcustomerName['firstname']= $guestcustomerName['lastname'] = "";

        $senderInfo = [
            'name' => $this->getAdminusername(),
            'email' => $this->getAdminemail(),
        ];
        $logger->info("sellerid".$sellerId);
        $sellerModel = $this->sellerinstance->create()->getCollection()
                       ->addFieldToFilter("seller_id",$sellerId)
                       ->getFirstItem();
        $emailTemplateVariables = [];
        $emailTempVariables['content'] = $content;
        $emailTempVariables['subject'] = $subject;
        $emailTempVariables['sellerId'] = $subject;
        $sellerbusinessName = $sellerModel->getData('business_name');
        $sellerCity = $this->citieshelper->getCityNameById($sellerModel->getData('store_city'));
        $emailTempVariables['sellerId'] = $sellerId;
        $emailTempVariables['sellerbusinessName'] = $sellerbusinessName;
        $emailTempVariables['cityName']=$sellerCity;



        $logger->info("business".$sellerbusinessName);
        $logger->info("city".$sellerCity);


        try {

            $allEmailAddress = $this->partnerlocationHelper->getUrlUpdateALertTriggerReceivers();
            $allEmailAddress = explode(",",$allEmailAddress);

            foreach($allEmailAddress as $emailAddress){
                $receiverInfo = [
                    'name' => $storeOwnerName,
                    'email' => $emailAddress,
                ];

                $this->objectManager->get(
                    'Bakeway\OrderstatusEmail\Helper\Email'
                )->sendEailEditAlertEmailtoSalesteam(
                    $emailTempVariables, $senderInfo, $receiverInfo
                );

            }

        } catch (\Exception $e) {
            echo $e->getMessage();
        }
    }


    /**
     * @return mixed
     */
    public function getBakewayPhoneNumber()
    {
        return $this->variableClass->loadByCode('support_bakeway_phone_no')->getPlainValue();
    }

}
