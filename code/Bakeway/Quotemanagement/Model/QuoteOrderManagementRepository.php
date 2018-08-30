<?php

namespace Bakeway\Quotemanagement\Model;

use Bakeway\Quotemanagement\Api\QuoteOrderManagementRepositoryInterface;
use Bakeway\Razorpay\Api\PaymentgatewayRepositoryInterface as RazorpayInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\GuestCartRepositoryInterface;
use Magento\Quote\Api\GuestCartManagementInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Api\Data\PaymentInterface;
use Bakeway\Paytm\Api\PaytmRepositoryInterface;
use Symfony\Component\Config\Definition\Exception\Exception;
use Magento\Sales\Api\OrderRepositoryInterface as OrderRepositoryInterface;
use Magento\Sales\Model\Order as SalesOrder;
use Magento\Framework\Event\Manager as Eventmanager;
use Magento\Sales\Model\Order\Email\Sender\OrderSender as OrderSender;
use Magento\Sales\Model\OrderFactory as OrderFactory;

class QuoteOrderManagementRepository implements QuoteOrderManagementRepositoryInterface
{
    /**
     * @var RazorpayInterface
     */
    protected $razorpayInterface;

    /**
     * @var CartManagementInterface
     */
    protected $cartManagementInterface;

    /**
     * @var GuestCartManagementInterface
     */
    protected $guestCartManagementInterface;

    /**
     * @var CartRepositoryInterface
     */
    protected $quoteRepository;

    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @var PaymentInterface
     */
    protected $paymentInterface;

    /**
     * @var GuestCartRepositoryInterface
     */
    protected $guestCartRepository;

    /**
     * @var PaytmRepositoryInterface
     */
    protected $paytmRepository;

    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepositoryInterface;

    /**
     * @var Eventmanager
     */
    protected $eventmanager;

    /**
    protected $orderSender;

    /**
     * QuoteOrderManagementRepository constructor.
     * @param RazorpayInterface $razorpayInterface
     * @param GuestCartManagementInterface $guestCartManagementInterface
     * @param CartManagementInterface $cartManagementInterface
     * @param CartRepositoryInterface $quoteRepository
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     * @param PaymentInterface $paymentInterface
     * @param GuestCartRepositoryInterface $guestCartRepository
     * @param PaytmRepositoryInterface $paytmRepository
     * @param OrderRepositoryInterface $orderRepositoryInterface
     */
    public function __construct(
        RazorpayInterface $razorpayInterface,
        GuestCartManagementInterface $guestCartManagementInterface,
        CartManagementInterface $cartManagementInterface,
        CartRepositoryInterface $quoteRepository,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        PaymentInterface $paymentInterface,
        GuestCartRepositoryInterface $guestCartRepository,
        PaytmRepositoryInterface $paytmRepository,
        OrderRepositoryInterface $orderRepositoryInterface,
        Eventmanager $eventmanager,
        OrderSender $orderSender,
        OrderFactory $orderFactory
    )
    {
        $this->razorpayInterface = $razorpayInterface;
        $this->guestCartManagementInterface = $guestCartManagementInterface;
        $this->cartManagementInterface = $cartManagementInterface;
        $this->quoteRepository = $quoteRepository;
        $this->customerRepository = $customerRepository;
        $this->paymentInterface = $paymentInterface;
        $this->guestCartRepository = $guestCartRepository;
        $this->paytmRepository = $paytmRepository;
        $this->orderRepositoryInterface = $orderRepositoryInterface;
        $this->eventmanager = $eventmanager;
        $this->orderSender = $orderSender;
        $this->orderFactory = $orderFactory;
    }

    /**
     * @api
     * @param int $customerId
     * @param int $cartId
     * @param mixed $payload
     * @return int
     * @throws \Magento\Framework\Exception\NotFoundException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function createOrder($customerId, $cartId, $payload)
    {
        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/order_process.log');
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);
        $quote = $this->getCustomerCart($customerId);
        $signatureVerified = false;
        $logger->info($quote->getData('entity_id') . " : ===============>>>>>> Order Placing Start Logged in Cust <<<<<<===============");
        if (($quote->getId()) && $cartId == $quote->getId()) {
            $logger->info($quote->getData('entity_id') . " : Quote Ids get matched for the request.");
            if (
                isset($payload['paymentDetails']) &&
                is_array($payload['paymentDetails']) &&
                isset($payload['paymentMethod']) &&
                is_array($payload['paymentMethod'])
            ) {
                $logger->info($quote->getData('entity_id') . " : Required Parameters found.");
                $razorpayDetails = $payload['paymentDetails'];
                $orderPaymentMethod = $payload['paymentMethod'];

                /**
                 * Check payment Method
                 */
                if (
                    isset($orderPaymentMethod ['method']) &&
                    $orderPaymentMethod ['method'] == \Bakeway\Paytm\Model\Paytm::CODE
                ) {
                    /**
                     * Paytm Payment Method
                     */
                    try {
                        $orderId = $this->paytmRepository->createPaytmOrder($quote->getId(), $payload);
                        return $orderId;
                    } catch (\Exception $e) {
                        throw new LocalizedException(__($e->getMessage()));
                    }
                } else {
                    /**
                     * Razorpay Signature verification
                     */
                    if (
                        isset($razorpayDetails['razorpay_payment_id']) &&
                        isset($razorpayDetails['razorpay_order_id']) &&
                        isset($razorpayDetails['razorpay_signature'])
                    ) {
                        try {
                            $logger->info($quote->getData('entity_id') . " : Payment Details found.");
                            $verification = $this->razorpayInterface->processcapture(
                                $quote->getId(),
                                $razorpayDetails['razorpay_payment_id'],
                                $razorpayDetails['razorpay_order_id'],
                                $razorpayDetails['razorpay_signature']);
                            if (isset($verification->status) && $verification->status === true) {
                                $logger->info($quote->getData('entity_id') . " : Signature Verified");
                                $signatureVerified = true;
                            }
                        } catch (\Exception $e) {
                            $logger->info($quote->getData('entity_id') . " : ERROR in Signature Verification");
                            throw new LocalizedException(__($e->getMessage()));
                        }

                        /**
                         * Order creation after signature is verified
                         */
                        if ($signatureVerified === true) {
                            $logger->info($quote->getData('entity_id') . " : Order creation starts");
                            if (
                                isset($orderPaymentMethod['method']) &&
                                $orderPaymentMethod['method'] == \Bakeway\Razorpay\Model\PaymentMethod::METHOD_CODE
                            ) {
                                $logger->info($quote->getData('entity_id') . " : Payment Method Code Verified");
                                $this->paymentInterface->setMethod($orderPaymentMethod['method']);
                                try {
                                    $orderId = $this->cartManagementInterface->placeOrder($quote->getId(), $this->paymentInterface);
                                    $logger->info($quote->getData('entity_id') . " : ===============>>>>>> Order Placing ENDS Logged in Cust <<<<<<===============" . $orderId);
                                    return $orderId;
                                } catch (\Exception $e) {
                                    $logger->info($quote->getData('entity_id') . " : ERROR :" . $e->getMessage());
                                    throw new LocalizedException(__($e->getMessage()));
                                }
                            } else {
                                $logger->info($quote->getData('entity_id') . " : ERROR : Wrong Payment method requested");
                                throw new LocalizedException(__("Wrong Payment method requested"));
                            }
                        }
                    }
                }
            } else {
                $logger->info($quote->getData('entity_id') . " : ERROR : Wrong Request Parameters");
                throw new LocalizedException(__("Required params paymentDetails and paymentMethod not received as expected"));
            }
        } else {
            $logger->info($quote->getData('entity_id') . " : ERROR : Requested cartId not matching");
            throw new LocalizedException(__("Active cartId not matching with requested cartId"));
        }
    }

    /*
     * @api
     * @param string $cartId
     * @param mixed $payload
     * @return int
     * @throws \Magento\Framework\Exception\NotFoundException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function createGuestOrder($cartId, $payload)
    {
        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/order_process.log');
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);
        $logger->info($cartId . " : ===============>>>>>> Order Placing Start Guest Cust <<<<<<===============");
        try {
            $quote = $this->guestCartRepository->get($cartId);
        } catch (\Exception $e) {
            $logger->info($cartId . " : Error getting the requested cart");
            throw new LocalizedException(__($e->getMessage()));
        }

        $signatureVerified = false;
        if ($quote->getId()) {
            $logger->info($quote->getData('entity_id') . " : Quote Ids get matched for the request.");
            if (
                isset($payload['paymentDetails']) &&
                is_array($payload['paymentDetails']) &&
                isset($payload['paymentMethod']) &&
                is_array($payload['paymentMethod'])
            ) {
                $logger->info($quote->getData('entity_id') . " : Required Parameters found.");
                $razorpayDetails = $payload['paymentDetails'];
                $orderPaymentMethod = $payload['paymentMethod'];

                /**
                 * Check payment Method
                 */
                if (
                    isset($orderPaymentMethod ['method']) &&
                    $orderPaymentMethod ['method'] == \Bakeway\Paytm\Model\Paytm::CODE
                ) {
                    /**
                     * Paytm Payment Method
                     */
                    $orderId = $this->paytmRepository->createPaytmGuestOrder($quote->getId(), $payload, $cartId);
                    return $orderId;
                } else {
                    /**
                     * Razorpay Signature verification
                     */
                    if (
                        isset($razorpayDetails['razorpay_payment_id']) &&
                        isset($razorpayDetails['razorpay_order_id']) &&
                        isset($razorpayDetails['razorpay_signature'])
                    ) {
                        try {
                            $logger->info($quote->getData('entity_id') . " : Payment Details found.");
                            $verification = $this->razorpayInterface->processcaptureguest(
                                $cartId,
                                $razorpayDetails['razorpay_payment_id'],
                                $razorpayDetails['razorpay_order_id'],
                                $razorpayDetails['razorpay_signature']);
                            if (isset($verification->status) && $verification->status === true) {
                                $logger->info($quote->getData('entity_id') . " : Signature Verified");
                                $signatureVerified = true;
                            }
                        } catch (\Exception $e) {
                            $logger->info($quote->getData('entity_id') . " : ERROR in Signature Verification");
                            throw new LocalizedException(__($e->getMessage()));
                        }

                        /**
                         * Order creation after signature is verified
                         */
                        if ($signatureVerified === true) {
                            $logger->info($quote->getData('entity_id') . " : Order creation starts");
                            if (
                                isset($orderPaymentMethod['method']) &&
                                $orderPaymentMethod['method'] == \Bakeway\Razorpay\Model\PaymentMethod::METHOD_CODE
                            ) {
                                $logger->info($quote->getData('entity_id') . " : Payment Method Code Verified");
                                $this->paymentInterface->setMethod($orderPaymentMethod['method']);
                                try {
                                    $orderId = $this->guestCartManagementInterface->placeOrder($cartId, $this->paymentInterface);
                                    $logger->info($quote->getData('entity_id') . " : ===============>>>>>> Order Placing ENDS Guest Cust <<<<<<===============" . $orderId);
                                    return $orderId;
                                } catch (\Exception $e) {
                                    $logger->info($quote->getData('entity_id') . " : ERROR :" . $e->getMessage());
                                    throw new LocalizedException(__($e->getMessage()));
                                }
                            } else {
                                $logger->info($quote->getData('entity_id') . " : ERROR : Wrong Payment method requested");
                                throw new LocalizedException(__("Wrong Payment method requested"));
                            }
                        }
                    }
                }
            } else {
                $logger->info($quote->getData('entity_id') . " : ERROR : Wrong Request Parameters");
                throw new LocalizedException(__("Required params paymentDetails and paymentMethod not received as expected"));
            }
        } else {
            $logger->info($cartId . " : ERROR : Requested cartId not matching");
            throw new LocalizedException(__("No quote found for the requested cartId"));
        }
    }

    /**
     * Creates a cart for the currently logged-in customer.
     *
     * @param int $customerId
     * @return \Magento\Quote\Model\Quote Cart object.
     * @throws NoSuchEntityException The cart could not be created.
     * @throws LocalizedException
     */
    protected function getCustomerCart($customerId)
    {
        try {
            $quote = $this->quoteRepository->getActiveForCustomer($customerId);
            return $quote;
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            throw new LocalizedException(__("No active cart found for this customer."));
        }
    }


    /**
     * @api
     * @param string $orderId
     * @param mixed $paymentDetails
     * @return mixed
     * @throws \Magento\Framework\Exception\NotFoundException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function createOrderPay($orderId, $paymentDetails)
    {

        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/order_process_check.log');
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);
        $logger->info($orderId . " : ===============>>>>>> Order Pay start <<<<<<===============");


        try {
            $order =  $this->orderFactory->create()->loadByIncrementId($orderId);
            if(empty($order->getEntityId())){
                throw new LocalizedException(__("No Entity is defined"));
            }
        } catch (\Exception $e) {
            throw new LocalizedException(__("No Entity is defind"));
        }
        $paymentMethod = $order->getPayment()->getMethod();
        if (!empty($paymentMethod) && $paymentMethod == \Bakeway\Razorpay\Model\PaymentMethod::METHOD_CODE) {

            if(empty($orderId) || empty($paymentDetails['razorpay_payment_id']) ||  empty($paymentDetails['razorpay_order_id']) ||
                empty($paymentDetails['razorpay_signature']))
            {
                $this->updateOrderStatusonfailurecase($order);
                throw new LocalizedException(__("No Entity is defined"));
            }

        }else if(!empty($paymentMethod) && $paymentMethod == \Bakeway\Paytm\Model\Paytm::CODE) {
            if(empty($orderId) || empty($paymentDetails['CHECKSUMHASH']))
            {
                $this->updateOrderStatusonfailurecase($order);
                throw new LocalizedException(__("No Entity is defined"));
            }

        }


        if (!empty($orderId) && isset($orderId)) {

            try {

                if ($orderId) {

                    try {
                        $order =  $this->orderFactory->create()->loadByIncrementId($orderId);
                        if(empty($order->getEntityId())){
                            throw new LocalizedException(__("No Entity is defined"));
                        }
                        $orderId = $order->getEntityId();
                    } catch (\Exception $e) {
                        throw new LocalizedException(__("No Entity is defind"));
                    }


                }

                $paymentMethod = $order->getPayment()->getMethod();
                $logger->info($orderId . " payment-method: " . $paymentMethod);

                try {
                    $quoteId = $order->getQuoteId();
                    $logger->info("Quote id " . $quoteId . " for order id " . $orderId);
                } catch (\Exception $e) {
                    throw new LocalizedException(__($e->getMessage()));
                }



                if (isset($quoteId)) {

                    $logger->info("Inside when get approve quote id ");

                    $returnOrderPaymentStatus = $this->paymentMethodVerification($paymentMethod, $paymentDetails, $quoteId ,$orderId);

                    return $returnOrderPaymentStatus;
                }

            } catch (\Exception $e) {
                throw new LocalizedException(__($e->getMessage()));
            }

        }

        $logger->info($orderId . " : ===============>>>>>> Order Pay end <<<<<<===============");
    }


    /**
     * @param string $paymentMethod
     * @param mixed $paymentDetails
     * @param string $quoteId
     * @param string $orderId
     * @return mixed
     * @throws LocalizedException
     */

    public function paymentMethodVerification($paymentMethod, $paymentDetails, $quoteId ,$orderId)
    {
        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/order_process.log');
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);
        $logger->info("-------------inside RP After order Placed payment method verification start ");

        $signatureVerified = $response = false;

        if (!empty($paymentMethod) && $paymentMethod == \Bakeway\Razorpay\Model\PaymentMethod::METHOD_CODE)
        {

            $logger->info("-------------RP payment method---------");

            /**
             * Razorpay Signature verification
             */
            if (
                isset($paymentDetails['razorpay_payment_id']) &&
                isset($paymentDetails['razorpay_order_id']) &&
                isset($paymentDetails['razorpay_signature'])
            ) {
                $logger->info("inside RP After order Placed get razorpay response " . $paymentDetails['razorpay_payment_id'] . "-" . $paymentDetails['razorpay_order_id'] . "-" . $paymentDetails['razorpay_signature']);

                try {
                    $verification = $this->razorpayInterface->processcapture(
                        $quoteId,
                        $paymentDetails['razorpay_payment_id'],
                        $paymentDetails['razorpay_order_id'],
                        $paymentDetails['razorpay_signature']);
                    $logger->info("------------signature is approved------");
                    if (isset($verification->status) && $verification->status === true) {
                        $signatureVerified = true;
                    }
                } catch (\Exception $e) {
                    throw new LocalizedException(__($e->getMessage()));
                }

                /**
                 * Quote update after signature is verified
                 */
                if ($signatureVerified === true) {
                    $logger->info("---inside RP After order Placed inside in code after signature got approved-----");
                    if (
                        isset($paymentMethod) &&
                        $paymentMethod == \Bakeway\Razorpay\Model\PaymentMethod::METHOD_CODE
                    ) {

                        $signatureVerified = true;
                        $logger->info("---Razorpay signature verifed for orderid ".$orderId);
                    } else {
                        $logger->info("---Inside RP After order Placed  Wrong Payment method requested for quote id ". $quoteId);
                        throw new LocalizedException(__("Wrong Payment method requested"));
                    }
                }
            }
        } else if (!empty($paymentMethod) && $paymentMethod == \Bakeway\Paytm\Model\Paytm::CODE) {
            $logger->info("-------------Paytm payment method after order placed start---------");
            /**
             * Paytm Payment Method
             */
            $logger->info("quote id ".$quoteId);
            $response = $this->paytmRepository->paytmOrderPayVerification($quoteId, $paymentDetails ,$orderId);

        }

        $orderResponse = [];
        if($signatureVerified === true || $response === true){
            try{
                $order = $this->orderRepositoryInterface->get($orderId);
                /* Dispatch event after order pay method done*/
                $logger->info("---inside RP  After order Placed Event get Fired");
                $this->updateOrderStatusOnSuccess($order);
                $this->eventmanager->dispatch(
                    'op_order_create_after',
                    ['order' => $order]
                );
                $this->eventmanager->dispatch(
                    'op_order_successfully_paid_after',
                    ['order_ids' => [$order->getEntityId()]]
                );

                /* Send order email for awating confirmation message */

                //$this->orderSender->send($order);
                $orderResponse['success']  =  true;
                $orderResponse['increment_id']  = $order->getIncrementId();

                $logger->info("---order id is ".$order->getIncrementId());

                return json_decode(json_encode($orderResponse),false);
            } catch (\Exception $e) {
                $orderResponse['success']  =  false;
                return json_decode(json_encode($orderResponse),false);
            }

        }
    }


    /**
     * @param $order
     * return void
     */
    public function updateOrderStatusonfailurecase($order){
        $paymentState = \Magento\Sales\Model\Order::STATE_PENDING_PAYMENT;
        $paymentStatus = \Magento\Sales\Model\Order::STATE_PENDING_PAYMENT;
        $order->setState($paymentState);
        $order->setStatus($paymentStatus);
        $order->save();

    }
    
    /**
     * @param $order
     */
    public function updateOrderStatusOnSuccess($order) {
        $paymentState = \Magento\Sales\Model\Order::STATE_NEW;
        $paymentStatus = 'pending';
        $order->setState($paymentState);
        $order->setStatus($paymentStatus);
        $order->save();
    }
}