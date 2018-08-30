<?php
/**
 * Bakeway
 *
 * @category  Bakeway
 * @package   Bakeway_Paytm
 * @author    Bakeway
 */

namespace Bakeway\Paytm\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order as SalesOrder;
use Bakeway\Paytm\Helper\Data as PaytmHelper;
use \Magento\Sales\Model\Order\Payment\Transaction as PaymentTransaction;

class SalesOrderPlaceAfter implements ObserverInterface {

    const AWAITING_CONFIRMATION = 'pending';

    const TXNTYPE = "capture";

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var PaytmHelper
     */
    protected $paytmHelper;

    /**
     * @var PaymentTransaction
     */
    protected $paymentTransaction;

    /**
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param PaytmHelper $paytmHelper
     * @param PaymentTransaction $paymentTransaction
     */
    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager,
        PaytmHelper $paytmHelper,
        PaymentTransaction $paymentTransaction
    ) {
        $this->objectManager = $objectManager;
        $this->paytmHelper = $paytmHelper;
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

        $order = $observer->getEvent()->getOrder();

        $orderTotal = round($order->getGrandTotal(), 2);
        
        $quoteId = $order->getQuoteId();
        $quoteData = $this->objectManager->create('\Magento\Quote\Model\Quote')
            ->load($quoteId);
        /**
         * check if payment method is paytm
         */
        $method = $order->getPayment()->getMethod();

        if ($method == 'paytm') {
            $logger->info("======Inside PAYTM for checking transaction status :: ".$quoteId);
            $orderId = $quoteData->getData('paytm_order_id');
            $merchantId = $this->paytmHelper->getMid();
            $merchantKey = $this->paytmHelper->getMerchantKey();
            $params = [
                'MID' => $merchantId,
                'ORDERID' => $orderId
            ];
            $checksumHash = $this->paytmHelper->getChecksumFromArray($params, $merchantKey);
            $params['CHECKSUMHASH'] = $checksumHash;//str_replace("+", "%2b", $checksumHash);

            if ($this->paytmHelper->getIsStage()) {
                $apiUrl = $this->paytmHelper->NEW_STATUS_QUERY_URL_TEST;
            } else {
                $apiUrl = $this->paytmHelper->NEW_STATUS_QUERY_URL_PROD;
            }

            /**
             * Check transaction status
             */
            try {
                $logger->info("======Inside PAYTM calling transaction status api starts :: ".$quoteId);
                $paytmStatusResponse = $this->paytmHelper->callNewAPI($apiUrl, $params);
                $logger->info("======Inside PAYTM status response :: ".json_encode($paytmStatusResponse));
                $logger->info("======Inside PAYTM calling transaction status api ends :: ".$quoteId);
                if (
                    isset($paytmStatusResponse['STATUS']) &&
                    isset($paytmStatusResponse['TXNID']) &&
                    $quoteData['paytm_txn_id'] == $paytmStatusResponse['TXNID']
                ) {
                    $logger->info("======Inside PAYTM get the required fields from status api :: ".$quoteId);
                    if (
                        $paytmStatusResponse['STATUS'] == 'TXN_SUCCESS' &&
                        $orderTotal == $paytmStatusResponse['TXNAMOUNT']
                    ) {
                        $logger->info("======Inside PAYTM transaction status SUCCESS :: ".$quoteId);
                        $order->getPayment()->setPaytmTxnId($quoteData['paytm_txn_id']);
                        $order->save();
                        $state = SalesOrder::STATE_NEW;
                        $status = self::AWAITING_CONFIRMATION;
                        $this->setOrderStateAndStatus($order, $state, $status);
                        //$this->setOrderTransaction($order, $quoteData['paytm_txn_id']);
                        /* update sales order payment transcation table */
                        $paymentTranscation = $this->paymentTransaction;
                        $paymentTranscation->setOrderId($order->getData('entity_id'));
                        $paymentTranscation->setPaytmTxnId($quoteData['paytm_txn_id']);
                        $paymentTranscation->setTxnId($quoteData['paytm_txn_id']);
                        $paymentTranscation->setTxnType('capture');
                        try{
                            $paymentTranscation->save();
                            $logger->info("---update sales_payment_transaction table with paytm txn id ".$quoteData['paytm_txn_id']);
                        }catch(Exception $e){
                            $logger->info("---update sales_payment_transaction table with paytm txn error is".$e->getError());
                            throw new LocalizedException(__("Error in updating transaction."));
                        }
                    } elseif (
                        ($paytmStatusResponse['STATUS'] == 'PENDING' ||
                        $paytmStatusResponse['STATUS'] == 'OPEN') &&
                        $orderTotal == $paytmStatusResponse['TXNAMOUNT']
                    ) {
                        $logger->info("======Inside PAYTM transaction status PENDING or OPEN  :: ".$quoteId);
                        $order->getPayment()->setPaytmTxnId($quoteData['paytm_txn_id']);
                        $order->save();
                        $state = SalesOrder::STATE_PENDING_PAYMENT;
                        $status = SalesOrder::STATE_PENDING_PAYMENT;
                        $this->setOrderStateAndStatus($order, $state, $status);
                        /* update sales order payment transcation table */
                        $paymentTranscation = $this->paymentTransaction;
                        $paymentTranscation->setOrderId($orderId);
                        $paymentTranscation->setPaytmTxnId($quoteData['paytm_txn_id']);
                        $paymentTranscation->setTxnId($quoteData['paytm_txn_id']);
                        $paymentTranscation->setTxnType('capture');
                        try{
                            $paymentTranscation->save();
                            $logger->info("---update sales_payment_transaction table with paytm txn id ".$quoteData['paytm_txn_id']);
                        }catch(Exception $e){
                            $logger->info("---update sales_payment_transaction table with paytm txn error is".$e->getError());
                            throw new LocalizedException(__("Error in updating transaction."));
                        }
                    } else {
                        if($order->getState() ==  SalesOrder::STATE_NEW && $order->getStatus() == self::AWAITING_CONFIRMATION){
                            $logger->info("======Inside PAYTM transaction status is ".self::AWAITING_CONFIRMATION."   :: ".$quoteId);
                        }{
                            $logger->info("======Inside PAYTM transaction status FAILED  :: ".$quoteId);
                            $state = SalesOrder::STATE_PAYMENT_REVIEW;
                            $status = SalesOrder::STATUS_FRAUD;
                            $this->setOrderStateAndStatus($order, $state, $status);
                            throw new LocalizedException(__("Paytm transaction failed."));
                        }

                    }
                } else {
                    $logger->info("======Inside PAYTM did not received required fields from status api :: ".$quoteId);
                    $state = SalesOrder::STATE_PAYMENT_REVIEW;
                    $status = SalesOrder::STATUS_FRAUD;
                    $this->setOrderStateAndStatus($order, $state, $status);
                    throw new LocalizedException(__("Did not received required fields for paytm."));
                }
            } catch (\Exception $e) {
                $logger->info("======Inside PAYTM transaction status error :: ".$quoteId."Error".$e->getMessage());
                $state = SalesOrder::STATE_PAYMENT_REVIEW;
                $status = SalesOrder::STATUS_FRAUD;
                $this->setOrderStateAndStatus($order, $state, $status);
                throw new LocalizedException(__("Error in paytm transaction status verification."));
            }
        }
    }

    /**
     * @param $order
     * @param string $state
     * @param string $status
     */
    public function setOrderStateAndStatus($order, $state, $status) {
        $order->setState($state, true);
        $order->setStatus($status);
        $order->save();
    }
}
