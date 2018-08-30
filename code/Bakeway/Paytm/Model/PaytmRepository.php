<?php
/**
 * Bakeway
 *
 * @category  Bakeway
 * @package   Bakeway_Paytm
 * @author    Bakeway
 */

namespace Bakeway\Paytm\Model;

use Bakeway\Paytm\Api\PaytmRepositoryInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\GuestCartRepositoryInterface;
use Bakeway\Paytm\Model\Paytm as PaytmModel;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Api\GuestCartManagementInterface;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use \Magento\Sales\Model\Order\Payment\Transaction as PaymentTransaction;
use Magento\Sales\Model\Order\Email\Sender\OrderSender as OrderSender;

class PaytmRepository implements PaytmRepositoryInterface
{
    const VALID_STATUS_ARRAY = ['OPEN', 'PENDING', 'TXN_SUCCESS'];

    const TXNTYPE = "capture";

    protected $cartRepository;

    /**
     * @var GuestCartRepositoryInterface
     */
    protected $guestCartRepository;

    /**
     * @var PaytmModel
     */
    protected $paytmModel;

    /**
     * @var CartManagementInterface
     */
    protected $cartManagementRepository;

    /**
     * @var GuestCartManagementInterface
     */
    protected $guestCartManagementRepository;

    /**
     * @var PaymentInterface
     */
    protected $paymentInterface;

    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var PaymentTransaction
     */
    protected $paymentTransaction;

    /**
     * @var OrderSender
     */
    protected $orderSender;

    /**
     * PaytmRepository constructor.
     * @param CartRepositoryInterface $cartRepository
     * @param GuestCartRepositoryInterface $guestCartRepository
     * @param Paytm $paytmModel
     * @param CartManagementInterface $cartManagementRepository
     * @param GuestCartManagementInterface $guestCartManagementRepository
     * @param PaymentInterface $paymentInterface
     * @param OrderRepositoryInterface $orderRepository
     * @param PaymentTransaction $paymentTransaction
     */
    public function __construct(
        CartRepositoryInterface $cartRepository,
        GuestCartRepositoryInterface $guestCartRepository,
        PaytmModel $paytmModel,
        CartManagementInterface $cartManagementRepository,
        GuestCartManagementInterface $guestCartManagementRepository,
        PaymentInterface $paymentInterface,
        OrderRepositoryInterface $orderRepository,
        PaymentTransaction $paymentTransaction,
        OrderSender $orderSender
    ){
        $this->cartRepository = $cartRepository;
        $this->guestCartRepository = $guestCartRepository;
        $this->paytmModel = $paytmModel;
        $this->cartManagementRepository = $cartManagementRepository;
        $this->guestCartManagementRepository = $guestCartManagementRepository;
        $this->paymentInterface = $paymentInterface;
        $this->orderRepository = $orderRepository;
        $this->paymentTransaction = $paymentTransaction;
        $this->orderSender = $orderSender;
    }
    /**
     * @api
     * @param string $cartId
     * @param mixed $payload
     * @return int $orderId
     * @throws \Magento\Framework\Exception\NotFoundException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function createPaytmOrder($cartId, $payload)
    {
        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/order_process.log');
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);

        $logger->info("======Inside PAYTM for registered customers :: ".$cartId);

        try {
            $quote = $this->cartRepository->get($cartId);
        } catch (\Exception $e) {
            throw new LocalizedException(__($e->getMessage()));
        }
        $logger->info("======Inside PAYTM printing request PARAMETERS :: ".json_encode($payload));
        if ($quote->getId()) {
            $logger->info("======Inside PAYTM :: Before checking request payload :: ".$quote->getId());
            if (
                isset($payload['paymentDetails']) &&
                is_array($payload['paymentDetails']) &&
                isset($payload['paymentMethod']) &&
                is_array($payload['paymentMethod'])
            ) {
                $logger->info("======Inside PAYTM :: Required payload keys found :: ".$quote->getId());
                $paytmDetails = $payload['paymentDetails'];
                $orderPaymentMethod = $payload['paymentMethod'];

                /**
                 * Paytm Checksum hash verification
                 */
                try {
                    $logger->info("======Inside PAYTM :: paytm response verification  starts :: ".$quote->getId());
                    $verification = $this->paytmModel->validateResponse($paytmDetails);
                    $logger->info("======Inside PAYTM :: paytm response verification  ends :: ".$quote->getId());
                } catch (\Exception $e) {
                    $logger->info("======Inside PAYTM :: error in paytm response verification :: ".$quote->getId()."Error".$e->getMessage());
                    throw new LocalizedException(__($e->getMessage()));
                }

                if ($verification === true &&
                    isset($paytmDetails['STATUS']) &&
                    in_array($paytmDetails['STATUS'], self::VALID_STATUS_ARRAY)
                ) {
                    $logger->info("======Inside PAYTM :: paytm response verification SUCCESS :: ".$quote->getId());
                    $this->assignPaytmDetailsToQuote($quote, $paytmDetails);
                    if (
                        isset($orderPaymentMethod['method']) &&
                        $orderPaymentMethod['method'] == \Bakeway\Paytm\Model\Paytm::CODE
                    ) {
                        $logger->info("======Inside PAYTM :: payment method code verified :: ".$quote->getId());
                        $this->paymentInterface->setMethod($orderPaymentMethod['method']);
                        try {
                            $logger->info("======Inside PAYTM :: Order placing starts :: ".$quote->getId());
                            $orderId = $this->cartManagementRepository->placeOrder($cartId, $this->paymentInterface);
                            $logger->info("======Inside PAYTM :: Order placing ends :: ".$quote->getId());
                            $this->setOrderTransaction($orderId, $payload['paymentDetails']['TXNID'], $quote->getId());
                            return $orderId;
                        } catch (\Exception $e) {
                            $logger->info("======Inside PAYTM :: Error in order placing :: ".$quote->getId()."Error".$e->getMessage());
                            throw new LocalizedException(__($e->getMessage()));
                        }
                    } else {
                        $logger->info("======Inside PAYTM :: payment method code failed :: ".$quote->getId());
                        throw new LocalizedException(__("Wrong Payment method requested."));
                    }
                } else {
                    $logger->info("======Inside PAYTM :: paytm response verification FAILED :: ".$quote->getId());
                    throw new LocalizedException(__("Invalid checksum received or transaction is failed on paytm."));
                }
            }
        }
    }

    /**
     * @api
     * @param string $cartId
     * @param mixed $payload
     * @param string $maskedCartId
     * @return int $orderId
     * @throws \Magento\Framework\Exception\NotFoundException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function createPaytmGuestOrder($cartId, $payload, $maskedCartId)
    {
        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/order_process.log');
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);

        $logger->info("======Inside PAYTM for guest customers :: ".$cartId);
        try {
            $quote = $this->cartRepository->get($cartId);
        } catch (\Exception $e) {
            throw new LocalizedException(__($e->getMessage()));
        }
        $logger->info("======Inside PAYTM printing request PARAMETERS :: ".json_encode($payload));
        if ($quote->getId()) {
            $logger->info("======Inside PAYTM :: Before checking request payload :: ".$quote->getId());
            if (
                isset($payload['paymentDetails']) &&
                is_array($payload['paymentDetails']) &&
                isset($payload['paymentMethod']) &&
                is_array($payload['paymentMethod'])
            ) {
                $logger->info("======Inside PAYTM :: Required payload keys found :: ".$quote->getId());
                $paytmDetails = $payload['paymentDetails'];
                $orderPaymentMethod = $payload['paymentMethod'];

                /**
                 * Paytm Checksum hash verification
                 */
                try {
                    $logger->info("======Inside PAYTM :: paytm response verification  starts :: ".$quote->getId());
                    $verification = $this->paytmModel->validateResponse($paytmDetails);
                    $logger->info("======Inside PAYTM :: paytm response verification  ends :: ".$quote->getId());
                } catch (\Exception $e) {
                    $logger->info("======Inside PAYTM :: error in paytm response verification :: ".$quote->getId()."Error".$e->getMessage());
                    throw new LocalizedException(__($e->getMessage()));
                }

                if ($verification === true &&
                    isset($paytmDetails['STATUS']) &&
                    in_array($paytmDetails['STATUS'], self::VALID_STATUS_ARRAY)
                ) {
                    $logger->info("======Inside PAYTM :: paytm response verification SUCCESS :: ".$quote->getId());
                    $this->assignPaytmDetailsToQuote($quote, $paytmDetails);
                    if (
                        isset($orderPaymentMethod['method']) &&
                        $orderPaymentMethod['method'] == \Bakeway\Paytm\Model\Paytm::CODE
                    ) {
                        $logger->info("======Inside PAYTM :: payment method code verified :: ".$quote->getId());
                        $this->paymentInterface->setMethod($orderPaymentMethod['method']);
                        try {
                            $logger->info("======Inside PAYTM :: Order placing starts :: ".$quote->getId());
                            $orderId = $this->guestCartManagementRepository->placeOrder($maskedCartId, $this->paymentInterface);
                            $logger->info("======Inside PAYTM :: Order placing ends :: ".$quote->getId());
                            $this->setOrderTransaction($orderId, $payload['paymentDetails']['TXNID'], $quote->getId());
                            return $orderId;
                        } catch (\Exception $e) {
                            $logger->info("======Inside PAYTM :: Error in order placing :: ".$quote->getId()."Error".$e->getMessage());
                            throw new LocalizedException(__($e->getMessage()));
                        }
                    } else {
                        $logger->info("======Inside PAYTM :: payment method code failed :: ".$quote->getId());
                        throw new LocalizedException(__("Wrong Payment method requested."));
                    }
                } else {
                    $logger->info("======Inside PAYTM :: paytm response verification FAILED :: ".$quote->getId());
                    throw new LocalizedException(__("Invalid checksum received or transaction is failed on paytm."));
                }
            }
        }
    }

    /**
     * @param $quote
     * @param $paytmDetails
     * @throws LocalizedException
     */
    public function assignPaytmDetailsToQuote($quote, $paytmDetails)
    {
        try {
            $quote->setData("paytm_txn_id", $paytmDetails['TXNID']);
            $quote->setData("paytm_order_id", $paytmDetails['ORDERID']);
            $quote->save();
            return;
        } catch (\Exception $e) {
            throw new LocalizedException(__($e->getMessage()));
        }
    }

    /**
     * @param $orderId
     * @param string $paytmTxnId
     * @param int $quoteId
     * @return void
     */
    public function setOrderTransaction($orderId, $paytmTxnId, $quoteId) {
        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/order_process.log');
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);
        try {
            $order = $this->orderRepository->get($orderId);
        } catch (\Exception $e) {
            $logger->info("======Inside PAYTM can not load order for transaction :: ".$quoteId."Error".$e->getMessage());
        }
        $quoteId = $order->getQuoteId();
        $logger->info("======Inside PAYTM setting magento transaction :: ".$quoteId);
        $logger->info("======Inside PAYTM setting order ID:: ".$order->getEntityId());
        $logger->info("======Inside PAYTM setting payment ID:: ".$order->getPayment()->getEntityId());
        $transaction = $this->paymentTransaction;
        $transaction->setOrderId($order->getEntityId());
        $transaction->setPaymentId($order->getPayment()->getEntityId());
        $transaction->setTxnId($paytmTxnId);
        $transaction->setTxnType(self::TXNTYPE);
        $transaction->setPaytmTxnId($paytmTxnId);
        try {
            $transaction->save();
        } catch (\Exception $e) {
            $logger->info("======Inside PAYTM error in setting magento transaction :: ".$quoteId."Error".$e->getMessage());
        }
    }

    
    /**
     * @param int $quoteId
     * @param mixed $payload
     * @param int $orderId
     * @return boolean
     * @throws LocalizedException
     */
    public function paytmOrderPayVerification($quoteId,$payload ,$orderId)
    {
        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/order_process.log');
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);
        try {
            $order = $this->orderRepository->get($orderId);
        } catch (\Exception $e) {
            $logger->info("======Inside PAYTM After order Placed can not load order for transaction :: ".$quoteId."Error".$e->getMessage());
        }

        $success = false;
        if ($quoteId) {
            $quote = $this->cartRepository->get($quoteId);
            $logger->info("======Inside PAYTM After order Placed :: Before checking request payload :: ".$quoteId);

            if (
                isset($payload) &&
                is_array($payload)
            ) {
                $logger->info("======Inside PAYTM After order Placed :: Required payload keys found :: ".$quoteId);
                $paytmDetails = $payload;
                try {
                    $order = $this->orderRepository->get($orderId);
                } catch (\Exception $e) {
                    $logger->info("======Inside PAYTM can not load order for transaction :: ".$quoteId."Error".$e->getMessage());
                }

                $orderPaymentMethod = $order->getPayment()->getMethod();
                /**
                 * Paytm Checksum hash verification
                 */
                try {
                    $logger->info("======Inside PAYTM After order Placed :: paytm response verification  starts :: ".$quoteId);
                    $verification = $this->paytmModel->validateResponse($paytmDetails);
                    $logger->info("======Inside PAYTM After order Placed :: paytm response verification  ends :: ".$quoteId);
                } catch (\Exception $e) {
                    $logger->info("======Inside PAYTM After order Placed :: error in paytm response verification :: ".$quoteId."Error".$e->getMessage());
                    throw new LocalizedException(__($e->getMessage()));
                }

                if ($verification === true &&
                    isset($paytmDetails['STATUS']) &&
                    in_array($paytmDetails['STATUS'], self::VALID_STATUS_ARRAY)
                ) {
                    $logger->info("======Inside PAYTM After order Placed :: paytm response verification SUCCESS :: ".$quoteId);
                    $this->assignPaytmDetailsToQuote($quote, $paytmDetails);

                    $logger->info("======Inside PAYTM After order Placed :: payment method code is:: ".$orderPaymentMethod."for quote id ".$quoteId);


                    if (
                        isset($orderPaymentMethod) &&
                        $orderPaymentMethod == \Bakeway\Paytm\Model\Paytm::CODE
                    ) {
                        $logger->info("======Inside PAYTM After order Placed :: payment method code verified :: ".$quoteId);
                        $this->paymentInterface->setMethod($orderPaymentMethod);
                        try {
                            $logger->info("======Inside PAYTM After order Placed :: Order placing starts :: ".$quoteId);
                            $this->setOrderTransaction($orderId, $payload['TXNID'], $quoteId);
                            $success = true;
                            $logger->info("======Inside PAYTM After order Placed :: Order placing ends :: ".$quoteId);

                        } catch (\Exception $e) {
                            $logger->info("======Inside PAYTM After order Placed :: Error in order placing :: ".$quoteId."Error".$e->getMessage());
                            throw new LocalizedException(__($e->getMessage()));
                        }
                    } else {
                        $logger->info("======Inside PAYTM After order Placed :: payment method code failed :: ".$quoteId);
                        throw new LocalizedException(__("Wrong Payment method requested."));
                    }
                } else {
                    $logger->info("======Inside PAYTM After order Placed :: paytm response verification FAILED :: ".$quoteId);
                    throw new LocalizedException(__("Invalid checksum received or transaction is failed on paytm."));
                }
            }
        }
        return $success;
    }
}