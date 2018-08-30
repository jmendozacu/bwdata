<?php

/**
 * Bakeway
 *
 * @category  Bakeway
 * @package   Bakeway_VendorNotification
 * @author    Bakeway
 */

namespace Bakeway\Razorpay\Observer;

require(BP . "/vendor/razorpay-php/Razorpay.php");

use Magento\Framework\Exception\LocalizedException;
use Razorpay\Api\Api;
use Razorpay\Api\Errors\SignatureVerificationError;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Order as SalesOrder;
use Magento\Sales\Model\Order\Payment\Transaction as PaymentTransaction;
use Symfony\Component\Config\Definition\Exception\Exception;

class SalesOrderPlaceAfter implements ObserverInterface {

    const AWATING_CONFIRMATION = 'pending';

    /**
     * @var \Bakeway\Razorpay\Model\Config
     */
    protected $config;

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var PaymentTransaction
     */
    protected $paymentTransaction;


    /**
     * SalesOrderPlaceAfter constructor.
     * @param \Bakeway\Razorpay\Model\Config $config
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param PaymentTransaction $paymentTransaction
     */
    public function __construct(
        \Bakeway\Razorpay\Model\Config $config,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        PaymentTransaction $paymentTransaction
    ) {
        $this->config = $config;
        $this->objectManager = $objectManager;
        $this->paymentTransaction = $paymentTransaction;
    }

    /**
     * sales order place after event handler
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer) {
        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/order_process.log');
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);

        $logger->info("--------------Inside Razorpay sales order place after Log----------------------");

        $order = $observer->getEvent()->getOrder();

        $logger->info("--order entity id---------".$order->getEntityId());
        
        $quoteId = $order->getQuoteId();
        $quoteData = $this->objectManager->create('\Magento\Quote\Model\Quote')
            ->load($quoteId);

        try{
            $orderId = $order->getEntityId();
            $logger->info("-----Inside Razorpay observer order object and order is .".$orderId);
        }catch(Exception $e){
            $logger->info("-----Inside Razorpay observer error through ". $e->getMessage());

        }
        /**
         * check if payment method is razorpay
         */
        $method = $order->getPayment()->getMethod();
        $logger->info("--payment method code is---------".\Bakeway\Razorpay\Model\PaymentMethod::METHOD_CODE);
        if ($method == \Bakeway\Razorpay\Model\PaymentMethod::METHOD_CODE) {


            $logger->info("--------------Razorpay sales order place after log start----------------------");
            $logger->info("quote id => ".$quoteId." for order id ".$order->getEntityId());

            $razororderid = $quoteData['razorpay_order_id'];
            $logger->info("---razorpay order id From Quote table ".$razororderid);

            $razorpaymentid = $quoteData['razorpay_payment_id'];
            $logger->info("---razorpay payment id From Quote table ".$razorpaymentid);

            $razorsignatureid = $quoteData['razorpay_signature'];
            $logger->info("---razorpay signature From Quote table ".$razorsignatureid);

            $razororderid = $quoteData['razorpay_order_id'];
            $razorpaymentid = $quoteData['razorpay_payment_id'];
            $razorsignatureid = $quoteData['razorpay_signature'];
            $orderStatus = $this->processcaptureforOrderStatus($razororderid, $razorpaymentid, $razorsignatureid);
            if ($orderStatus === true):
                try {
                    
                    /*update sales order payment table*/
                    $order->getPayment()->setRazorpayOrderId($quoteData['razorpay_order_id']);
                    $order->getPayment()->setRazorpayPaymentId($quoteData['razorpay_payment_id']);
                    $order->getPayment()->setRazorpaySignature($quoteData['razorpay_signature']);
                    $order->save();

                    /* update sales order payment transcation table */
                    $paymentTranscation = $this->paymentTransaction;
                    $paymentTranscation->setOrderId($orderId);
                    $paymentTranscation->setTxnId($quoteData['razorpay_payment_id']);
                    $paymentTranscation->setTxnType('capture');
                    $paymentTranscation->setRazorpayOrderId($quoteData['razorpay_order_id']);
                    $paymentTranscation->setRazorpayPaymentId($quoteData['razorpay_payment_id']);
                    $paymentTranscation->setRazorpaySignature($quoteData['razorpay_signature']);
                    try{
                        $paymentTranscation->save();
                        $logger->info("---update sales_payment_transaction table with razorpay payment id ".$quoteData['razorpay_payment_id']);
                    }catch(Exception $e){
                        $logger->info("---update sales_payment_transaction table with razorpay payment error is".$e->getError());
                    }
                    $paymentTranscation->save();
                    $logger->info("---signature verified---payment transcation table updated");
                } catch (\Exception $e) {
                    $e->getError();
                }
            else:
                $state = SalesOrder::STATE_PAYMENT_REVIEW;
                $status = SalesOrder::STATUS_FRAUD;
                $order->setState($state, true);
                $order->setStatus($status);
                $order->save();
                $logger->info("---signature failed ------for order id ".$order->getEntityId());
                throw new LocalizedException(__('Order Suspected as a fraud. Entity id is : '.$order->getEntityId()));
            endif;
        }

    }

    /*
     * @param string $razorpay_payment_id
     * @param string $razorpay_order_id
     * @param string $razorpay_signature
     * @return bool
     * @throws \Magento\Framework\Exception\NotFoundException
     */
    public function processcaptureforOrderStatus($razorpay_order_id, $razorpay_payment_id, $razorpay_signature) {
        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/order_process.log');
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);
        $logger->info("--------------Razorpay signature verifation start-------------------");
        $success = true;
        if (empty($razorpay_payment_id) === false) {
            $_apikeyid = $this->config->getKeyId();
            $_apikeysecret = $this->config->getKeySecret();
            $rzpmainobj = new Api($_apikeyid, $_apikeysecret);
            try {
                $attributes = [
                    'razorpay_order_id' => $razorpay_order_id,
                    'razorpay_payment_id' => $razorpay_payment_id,
                    'razorpay_signature' => $razorpay_signature
                ];
                $logger->info("--------Razorpay signature pass for razor pay order id ".$razorpay_order_id);
                $rzpmainobj->utility->verifyPaymentSignature($attributes);
            } catch (SignatureVerificationError $e) {
                $success = false;
                $logger->info("--------Razorpay signature failed for razor pay order id ".$razorpay_order_id);
            }
        } else {
            $success = false;
            $logger->info("--------Razorpay payment id is missing---------");

        }

        return $success;
    }

}
