<?php

namespace Bakeway\Quotemanagement\Model;

use Bakeway\Quotemanagement\Api\CreateOrderManagementRepositoryInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\GuestCartRepositoryInterface;
use Magento\Quote\Api\GuestCartManagementInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Api\CartManagementInterface;
use Bakeway\Razorpay\Api\PaymentgatewayRepositoryInterface as RazorpayInterface;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Sales\Api\OrderRepositoryInterface;

class CreateOrderManagementRepository implements CreateOrderManagementRepositoryInterface
{
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
     * @var GuestCartRepositoryInterface
     */
    protected $guestCartRepository;

    /**
     * @var RazorpayInterface
     */
    protected $razorpayInterface;

    /**
     * @var PaymentInterface
     */
    protected $paymentInterface;

    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * QuoteOrderManagementRepository constructor.
     * @param GuestCartManagementInterface $guestCartManagementInterface
     * @param CartManagementInterface $cartManagementInterface
     * @param \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     * @param GuestCartRepositoryInterface $guestCartRepository
     * @param RazorpayInterface $razorpayInterface
     * @param PaymentInterface $paymentInterface
     * @param OrderRepositoryInterface $orderRepository
     */
    public function __construct(
        GuestCartManagementInterface $guestCartManagementInterface,
        CartManagementInterface $cartManagementInterface,
        CartRepositoryInterface $quoteRepository,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        GuestCartRepositoryInterface $guestCartRepository,
        RazorpayInterface $razorpayInterface,
        PaymentInterface $paymentInterface,
        OrderRepositoryInterface $orderRepository
    ) {
        $this->guestCartManagementInterface = $guestCartManagementInterface;
        $this->cartManagementInterface = $cartManagementInterface;
        $this->quoteRepository = $quoteRepository;
        $this->customerRepository = $customerRepository;
        $this->guestCartRepository = $guestCartRepository;
        $this->razorpayInterface = $razorpayInterface;
        $this->paymentInterface = $paymentInterface;
        $this->orderRepository = $orderRepository;
    }

    /**
     * @api
     * @param int $customerId
     * @param int $cartId
     * @param mixed $payload
     * @return array
     * @throws \Magento\Framework\Exception\NotFoundException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function createOrder($customerId, $cartId, $payload) {
        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/order_process.log');
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);
        $quote = $this->getCustomerCart($customerId);
        $logger->info($quote->getData('entity_id')." : ===============>>>>>> Order Placing Start Logged in Cust <<<<<<===============");
        if (($quote->getId()) && $cartId == $quote->getId()) {
            $logger->info($quote->getData('entity_id')." : Quote Ids get matched for the request.");
            if (
                isset($payload['paymentMethod']) &&
                is_array($payload['paymentMethod'])
            ) {
                $logger->info($quote->getData('entity_id')." : Required Parameters found.");
                $orderPaymentMethod = $payload['paymentMethod'];
                if (
                    isset($orderPaymentMethod['method']) &&
                    ($orderPaymentMethod['method'] == \Bakeway\Razorpay\Model\PaymentMethod::METHOD_CODE ||
                        $orderPaymentMethod['method'] == \Bakeway\Paytm\Model\Paytm::CODE)
                ) {
                    if ($orderPaymentMethod['method'] == \Bakeway\Razorpay\Model\PaymentMethod::METHOD_CODE) {
                        $razorpayOrderId = $quote->getData('razorpay_order_id');
                        if (!isset($razorpayOrderId) && $razorpayOrderId == null) {
                            $razorpayOrderDetails = $this->razorpayInterface->order('null', $quote->getData('entity_id'));
                            if (isset($razorpayOrderDetails->razorpay_order_id)) {
                                $razorpayOrderId = $razorpayOrderDetails->razorpay_order_id;
                            } elseif (isset($razorpayOrderDetails->message)) {
                                $logger->info($quote->getData('entity_id') . " : ERROR in creating razorpay order id :" . $razorpayOrderDetails->message);
                                throw new LocalizedException(__($razorpayOrderDetails->message));
                            }
                        }
                        $response['paymentDetails']['razorpay_order_id'] = $razorpayOrderId;
                    }
                    $logger->info($quote->getData('entity_id')." : Payment Method Code Verified");
                    $this->paymentInterface->setMethod($orderPaymentMethod['method']);
                    try {
                        $orderId = $this->cartManagementInterface->placeOrder($quote->getId(), $this->paymentInterface);
                        $logger->info($quote->getData('entity_id')." : ===============>>>>>> Order Placing ENDS Logged in Cust <<<<<<===============".$orderId);
                        $orderDetails = $this->orderRepository->get($orderId);
                        $response['entity_id'] = $orderId;
                        $response['increment_id'] = $orderDetails->getIncrementId();
                        $response['tracking_token'] = $orderDetails->getOrderTrackingToken();
                        $response['paymentDetails']['method'] = $orderPaymentMethod['method'];
                        return json_decode(json_encode($response, false));
                    } catch (\Exception $e) {
                        $logger->info($quote->getData('entity_id')." : ERROR :".$e->getMessage());
                        throw new LocalizedException(__($e->getMessage()));
                    }
                } else {
                    $logger->info($quote->getData('entity_id')." : ERROR : Wrong Payment method requested");
                    throw new LocalizedException(__("Wrong Payment method requested"));
                }
            } else {
                $logger->info($quote->getData('entity_id')." : ERROR : Wrong Request Parameters");
                throw new LocalizedException(__("Required params paymentDetails not received as expected"));
            }
        } else {
            $logger->info($quote->getData('entity_id')." : ERROR : Requested cartId not matching");
            throw new LocalizedException(__("Active cartId not matching with requested cartId"));
        }
    }

    /**
     * @api
     * @param string $cartId
     * @param mixed $payload
     * @return int
     * @throws \Magento\Framework\Exception\NotFoundException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function createGuestOrder($cartId, $payload) {
        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/order_process.log');
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);
        $logger->info($cartId." : ===============>>>>>> Order Placing Start Guest Cust <<<<<<===============");
        try {
            $quote = $this->guestCartRepository->get($cartId);
        } catch (\Exception $e) {
            $logger->info($cartId." : Error getting the requested cart");
            throw new LocalizedException(__($e->getMessage()));
        }

        if ($quote->getId()) {
            $logger->info($quote->getData('entity_id')." : Quote Ids get matched for the request.");
            if (
                isset($payload['paymentMethod']) &&
                is_array($payload['paymentMethod'])
            ) {
                $logger->info($quote->getData('entity_id')." : Required Parameters found.");
                $orderPaymentMethod = $payload['paymentMethod'];
                if (
                    isset($orderPaymentMethod['method']) &&
                    ($orderPaymentMethod['method'] == \Bakeway\Razorpay\Model\PaymentMethod::METHOD_CODE ||
                        $orderPaymentMethod['method'] == \Bakeway\Paytm\Model\Paytm::CODE)
                ) {
                    if ($orderPaymentMethod['method'] == \Bakeway\Razorpay\Model\PaymentMethod::METHOD_CODE) {
                        $razorpayOrderId = $quote->getData('razorpay_order_id');
                        if (!isset($razorpayOrderId) && $razorpayOrderId == null) {
                            $razorpayOrderDetails = $this->razorpayInterface->guestorder('null', $cartId);
                            if (isset($razorpayOrderDetails->razorpay_order_id)) {
                                $razorpayOrderId = $razorpayOrderDetails->razorpay_order_id;
                            } elseif (isset($razorpayOrderDetails->message)) {
                                $logger->info($quote->getData('entity_id') . " : ERROR in creating razorpay order id :" . $razorpayOrderDetails->message);
                                throw new LocalizedException(__($razorpayOrderDetails->message));
                            }
                        }
                        $response['paymentDetails']['razorpay_order_id'] = $razorpayOrderId;
                    }
                    $logger->info($quote->getData('entity_id')." : Payment Method Code Verified");
                    $this->paymentInterface->setMethod($orderPaymentMethod['method']);
                    try {
                        $orderId = $this->guestCartManagementInterface->placeOrder($cartId, $this->paymentInterface);
                        $logger->info($quote->getData('entity_id')." : ===============>>>>>> Order Placing ENDS Logged in Cust <<<<<<===============".$orderId);
                        $orderDetails = $this->orderRepository->get($orderId);
                        $response['entity_id'] = $orderId;
                        $response['increment_id'] = $orderDetails->getIncrementId();
                        $response['tracking_token'] = $orderDetails->getOrderTrackingToken();
                        $response['paymentDetails']['method'] = $orderPaymentMethod['method'];
                        return json_decode(json_encode($response, false));
                    } catch (\Exception $e) {
                        $logger->info($quote->getData('entity_id')." : ERROR :".$e->getMessage());
                        throw new LocalizedException(__($e->getMessage()));
                    }
                } else {
                    $logger->info($quote->getData('entity_id')." : ERROR : Wrong Payment method requested");
                    throw new LocalizedException(__("Wrong Payment method requested"));
                }
            } else {
                $logger->info($quote->getData('entity_id')." : ERROR : Wrong Request Parameters");
                throw new LocalizedException(__("Required params paymentDetails not received as expected"));
            }
        } else {
            $logger->info($cartId." : ERROR : Requested cartId not matching");
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
}
