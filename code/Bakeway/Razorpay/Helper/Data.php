<?php

namespace Bakeway\Razorpay\Helper;

use Symfony\Component\Config\Definition\Exception\Exception;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\Exception\LocalizedException;
use Razorpay\Api\Api as RazorpayApi;

class Data extends \Magento\Framework\App\Helper\AbstractHelper {


   /**
    *  @var \Magento\Quote\Api\CartRepositoryInterface
    */
    protected $cartRepositoryInterface;

    /**
     * @var \Bakeway\Razorpay\Model\Config
     */
    protected $razorpayConfig;

    /**
     * Data constructor.
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Quote\Api\CartRepositoryInterface $cartRepositoryInterface
     * @param \Bakeway\Razorpay\Model\Config $razorpayConfig
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Quote\Api\CartRepositoryInterface $cartRepositoryInterface,
        \Bakeway\Razorpay\Model\Config $razorpayConfig
    ) {
        parent::__construct($context);
        $this->cartRepositoryInterface = $cartRepositoryInterface;
        $this->razorpayConfig = $razorpayConfig;
    }

    /**
     * check orderid
     * @param int $cartId
     * @param int $razorpay_order_id
     * @throws \Magento\Framework\Exception\NotFoundException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @return boolean
     */
    public function confirmOrderId($cartId,$razorpay_order_id) {

      if(isset($razorpay_order_id))
      {
          $cartDetails  = $this->cartRepositoryInterface->get($cartId);
          $oldRazorOrderId = $cartDetails->getData('razorpay_order_id');
          $newRazorOrderId = $razorpay_order_id;
          if($newRazorOrderId === $oldRazorOrderId){
              return true;
          }else {
              throw new LocalizedException(__('Razorpay orderid is not matching'));
          }

      }else{
          throw new NotFoundException(__('Razorpay payment id is not found'));

      }
    }


    public function initiateRazorpayRefund($order) {
        /**
         * commenting code for refund as per BKWYADMIN-744
         */
//        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/razorpay_refund.log');
//        $logger = new \Zend\Log\Logger();
//        $logger->addWriter($writer);
//
//        $orderId = $order->getIncrementId();
//
//        $logger->info("======Razorpay refund requested for order :: ".$orderId);
//
//        try {
//            $apiKey = $this->razorpayConfig->getKeyId();
//            $apiSecrete = $this->razorpayConfig->getKeySecret();
//
//            $razorpay = new RazorpayApi($apiKey, $apiSecrete);
//
//            $razorpayPaymentId = $order->getPayment()->getData('razorpay_payment_id');
//
//            $refund = $razorpay->refund->create(['payment_id' => $razorpayPaymentId]);
//
//            $logger->info("======Razorpay refund response :: ".json_encode($refund));
//        } catch (\Exception $e) {
//            $logger->info("======Razorpay refund Error for order :: ".$orderId . " :: " . $e->getMessage());
//        }
    }

}
