<?php
/**
 * Bakeway
 *
 * @category  Bakeway
 * @package   Bakeway_Quotemanagement
 * @author    Bakeway
 */

namespace Bakeway\Quotemanagement\Helper;

require(BP . "/vendor/razorpay-php/Razorpay.php");

use Razorpay\Api\Api;
use Bakeway\Quotemanagement\Model\QuoteOrderManagementRepository;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NotFoundException;
use Magento\Sales\Model\OrderFactory as SalesOrderFactory;
use Magento\Quote\Api\CartRepositoryInterface;
use Bakeway\Paytm\Helper\Data as PaytmHelper;

/**
 * Bakeway Quotemanagement Helper Data.
 */
class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    const SHA256 = 'sha256';

    /**
     * @var QuoteOrderManagementRepository
     */
    protected $quoteOrderManagementRepository;

    /**
     * @var \Bakeway\Razorpay\Model\Config
     */
    protected $razorpayConfig;

    /**
     * @var SalesOrderFactory
     */
    protected $salesOrderFactory;

    /**
     * @var CartRepositoryInterface
     */
    protected $cartRepository;

    /**
     * @var PaytmHelper
     */
    protected $paytmHelper;

    /**
     * Data constructor.
     * @param \Magento\Framework\App\Helper\Context $context
     * @param QuoteOrderManagementRepository $quoteOrderManagementRepository
     * @param \Bakeway\Razorpay\Model\Config $razorpayConfig
     * @param SalesOrderFactory $salesOrderFactory
     * @param CartRepositoryInterface $cartRepository
     * @param PaytmHelper $paytmHelper
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        QuoteOrderManagementRepository $quoteOrderManagementRepository,
        \Bakeway\Razorpay\Model\Config $razorpayConfig,
        SalesOrderFactory $salesOrderFactory,
        CartRepositoryInterface $cartRepository,
        PaytmHelper $paytmHelper
    ) {
        $this->quoteOrderManagementRepository = $quoteOrderManagementRepository;
        $this->razorpayConfig = $razorpayConfig;
        $this->salesOrderFactory = $salesOrderFactory;
        $this->cartRepository = $cartRepository;
        $this->paytmHelper = $paytmHelper;
        parent::__construct($context);
    }

    /**
     * @param $incrementId
     * @return mixed
     * @throws LocalizedException
     */
    public function forwardOrder($incrementId) {
        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/ordercreate.log');
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);
        try {
            $logger->info("--------------------start forward order method--------------");
            $logger->info("start forward order method for order id----". $incrementId);
            $order = $this->salesOrderFactory->create()->loadByIncrementId($incrementId);
            $paymentMethod = $order->getPayment()->getMethod();
            $logger->info("Payment method for order ".$incrementId." is". $paymentMethod);
            $quoteId = $order->getQuoteId();
            $logger->info("Quote id for order ".$quoteId);
            $logger->info("forward ordering for order id ".$incrementId);
            $quote = $this->cartRepository->get($quoteId);

            $paymentDetails = null;
            if ($paymentMethod == "paytm") {
                $paytmOrderId = $quote->getData('paytm_order_id');
                $logger->info("paytm order id is ".$paytmOrderId);
                $paymentDetails = $this->checkPaytmPayment($paytmOrderId);
            } elseif ($paymentMethod == "razorpay") {
                $razorpayOrderId = $quote->getData('razorpay_order_id');
                $logger->info("razorpay order id is ".$razorpayOrderId);
                $paymentDetails = $this->checkRazorpayPayment($razorpayOrderId);
            } else {
                throw new NotFoundException(__('Payment method associated with this order is not found.'));
            }

            if ($paymentDetails !== null) {
                $logger->info("-----runing verifyorderpayment for order ".$incrementId);
                $response = $this->verifyOrderPayment($incrementId, $paymentDetails);
                return $response;
            } else {
                throw new LocalizedException(__("Error in fetching payment details."));
            }

        } catch (\Exception $e) {
            throw new LocalizedException(__("Error : ". $e->getMessage()));
        }
    }

    /**
     * @param $orderId
     * @param $paymentDetails
     * @return mixed
     * @throws LocalizedException
     */
    public function verifyOrderPayment($orderId, $paymentDetails) {
        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/ordercreate.log');
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);
        $logger->info("--------inside method verifyOrderPayment for order id -----".$orderId);
        try {
            $response = $this->quoteOrderManagementRepository->createOrderPay($orderId, $paymentDetails);
            return $response;
        } catch (\Exception $e) {
            throw new LocalizedException(__("Error in create order pay : ".$e->getMessage()));
        }
    }

    /**
     * @param $razorpayOrderId
     * @return array
     * @throws LocalizedException
     */
    public function checkRazorpayPayment($razorpayOrderId) {
        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/ordercreate.log');
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);
        try {
            $logger->info("-----------------checkRazorpayPayment started------------");
            $apiKey = $this->razorpayConfig->getKeyId();
            $apiSecret = $this->razorpayConfig->getKeySecret();
            /* get obj of Razor pay api */
            $razorpayObj = new Api($apiKey, $apiSecret);

            $logger->info("razorpay order id is ".$razorpayOrderId);

            $razorpayPayment = $razorpayObj->order->fetch($razorpayOrderId)->payments();

            $paymentId = null;
            /* foreach ($razorpayPayment as $payment) {
                if (isset($payment['id'])) {
                    $paymentId = $payment['id'];
                    $logger->info("razorpay payment id is ".$paymentId);
                }
            }*/
            if(isset($razorpayPayment->items[0]->id)){
                 $paymentId = $razorpayPayment->items[0]->id;
            }

            $logger->info("razorpay payment id is ".$paymentId);

            $payload = $razorpayOrderId . "|" . $paymentId;
            $expectedSignature = hash_hmac(self::SHA256, $payload, $apiSecret);
            $razorpayDetails = [
                'razorpay_order_id' => $razorpayOrderId,
                'razorpay_payment_id' => $paymentId,
                'razorpay_signature' => $expectedSignature
            ];

            $logger->info("--razor pay payload----".'razorpay_order_id'.$razorpayOrderId);
            $logger->info("--razor pay payload----".'razorpay_payment_id'.$paymentId);
            $logger->info("--razor pay payload----".'razorpay_signature'.$expectedSignature);
            return $razorpayDetails;
        } catch (\Exception $e) {
            throw new LocalizedException(__("Error in getting fetching razorpay transaction status : ". $e->getMessage()));
        }
    }

    /**
     * @param $paytmOrderId
     * @return array
     * @throws LocalizedException
     */
    public function checkPaytmPayment($paytmOrderId) {
        try {
            $merchantId = $this->paytmHelper->getMid();
            $merchantKey = $this->paytmHelper->getMerchantKey();
            $params = [
                'MID' => $merchantId,
                'ORDERID' => $paytmOrderId
            ];
            $checksumHash = $this->paytmHelper->getChecksumFromArray($params, $merchantKey);
            $params['CHECKSUMHASH'] = $checksumHash;//str_replace("+", "%2b", $checksumHash);

            if ($this->paytmHelper->getIsStage()) {
                $apiUrl = $this->paytmHelper->NEW_STATUS_QUERY_URL_TEST;
            } else {
                $apiUrl = $this->paytmHelper->NEW_STATUS_QUERY_URL_PROD;
            }
            $paytmStatusResponse = $this->paytmHelper->callNewAPI($apiUrl, $params);
            return $paytmStatusResponse;
        } catch (\Exception $e) {
            throw new LocalizedException(__("Error in getting fetching paytm transaction status : ". $e->getMessage()));
        }
    }
}