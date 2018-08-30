<?php
/**
 * Bakeway
 *
 * @category  Bakeway
 * @package   Bakeway_Paytm
 * @author    Bakeway
 */

namespace Bakeway\Paytm\Helper;

use Magento\Framework\App\Helper\Context;
use Magento\Sales\Model\Order;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Quote\Model\QuoteRepository;

class Data extends AbstractHelper
{
    protected $session;
    public $PAYTM_PAYMENT_URL_PROD = "https://secure.paytm.in/oltp-web/processTransaction";
    public $STATUS_QUERY_URL_PROD = "https://secure.paytm.in/oltp/HANDLER_INTERNAL/TXNSTATUS";
    public $NEW_STATUS_QUERY_URL_PROD = "https://secure.paytm.in/oltp/HANDLER_INTERNAL/getTxnStatus";
    public $PAYTM_REFUND_URL_PROD = "https://secure.paytm.in/oltp/HANDLER_INTERNAL/REFUND";

    public $PAYTM_PAYMENT_URL_TEST = "https://pguat.paytm.com/oltp-web/processTransaction";
    public $STATUS_QUERY_URL_TEST = "https://pguat.paytm.com/oltp/HANDLER_INTERNAL/TXNSTATUS";
    public $NEW_STATUS_QUERY_URL_TEST = "https://pguat.paytm.com/oltp/HANDLER_INTERNAL/getTxnStatus";
    public $PAYTM_REFUND_URL_TEST = "https://pguat.paytm.com/oltp/HANDLER_INTERNAL/REFUND";

    /**
     * @var QuoteRepository
     */
    protected $quoteRepository;

    public function __construct(
        Context $context,
        \Magento\Checkout\Model\Session $session,
        QuoteRepository $quoteRepository
    ) {
        $this->session = $session;
        $this->quoteRepository = $quoteRepository;
        parent::__construct($context);
    }

    public function cancelCurrentOrder($comment) {
        $order = $this->session->getLastRealOrder();
        if ($order->getId() && $order->getState() != Order::STATE_CANCELED) {
            $order->registerCancellation($comment)->save();
            return true;
        }
        return false;
    }

    public function restoreQuote() {
        return $this->session->restoreQuote();
    }

    public function getUrl($route, $params = []) {
        return $this->_getUrl($route, $params);
    }

    public function pkcs5_pad_e($text, $blocksize) {
        $pad = $blocksize - (strlen($text) % $blocksize);
        return $text . str_repeat(chr($pad), $pad);
    }

    public function encrypt_e($input, $ky) {
        $key = $ky;
        $size = mcrypt_get_block_size(MCRYPT_RIJNDAEL_128, 'cbc');
        $input = $this->pkcs5_pad_e($input, $size);
        $td = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', 'cbc', '');
        $iv = "@@@@&&&&####$$$$";
        mcrypt_generic_init($td, $key, $iv);
        $data = mcrypt_generic($td, $input);
        mcrypt_generic_deinit($td);
        mcrypt_module_close($td);
        $data = base64_encode($data);
        return $data;
    }

    public function pkcs5_unpad_e($text) {
        $pad = ord($text{strlen($text) - 1});
        if ($pad > strlen($text))
            return false;
        return substr($text, 0, -1 * $pad);
    }

    public function decrypt_e($crypt, $ky) {
        $crypt = base64_decode($crypt);
        $key = $ky;
        $td = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', 'cbc', '');
        $iv = "@@@@&&&&####$$$$";
        mcrypt_generic_init($td, $key, $iv);
        $decrypted_data = mdecrypt_generic($td, $crypt);
        mcrypt_generic_deinit($td);
        mcrypt_module_close($td);
        $decrypted_data = $this->pkcs5_unpad_e($decrypted_data);
        $decrypted_data = rtrim($decrypted_data);
        return $decrypted_data;
    }

    public function generateSalt_e($length) {
        $random = "";
        srand((double) microtime() * 1000000);
        $data = "AbcDE123IJKLMN67QRSTUVWXYZ";
        $data .= "aBCdefghijklmn123opq45rs67tuv89wxyz";
        $data .= "0FGH45OP89";
        for ($i = 0; $i < $length; $i++) {
            $random .= substr($data, (rand() % (strlen($data))), 1);
        }
        return $random;
    }

    public function checkString_e($value) {
        $myvalue = ltrim($value);
        $myvalue = rtrim($myvalue);
        if ($myvalue == 'null')
            $myvalue = '';
        return $myvalue;
    }

    public function getChecksumFromArray($arrayList, $key) {
        ksort($arrayList);
        $str = $this->getArray2Str($arrayList);
        $salt = $this->generateSalt_e(4);
        $finalString = $str . "|" . $salt;
        $hash = hash("sha256", $finalString);
        $hashString = $hash . $salt;
        $checksum = $this->encrypt_e($hashString, $key);
        return $checksum;
    }

    public function verifychecksum_e($arrayList, $key, $checksumvalue) {
        $arrayList = $this->removeCheckSumParam($arrayList);
        ksort($arrayList);
        $str = $this->getArray2StrForVerify($arrayList);
        $paytm_hash = $this->decrypt_e($checksumvalue, $key);
        $salt = substr($paytm_hash, -4);
        $finalString = $str . "|" . $salt;
        $website_hash = hash("sha256", $finalString);
        $website_hash .= $salt;
        $validFlag = FALSE;
        if ($website_hash == $paytm_hash) {
            $validFlag = TRUE;
        } else {
            $validFlag = FALSE;
        }
        return $validFlag;
    }

    public function getArray2StrForVerify($arrayList) {
        $paramStr = "";
        $flag = 1;
        foreach ($arrayList as $key => $value) {
            if ($flag) {
                $paramStr .= $this->checkString_e($value);
                $flag = 0;
            } else {
                $paramStr .= "|" . $this->checkString_e($value);
            }
        }
        return $paramStr;
    }

    public function getArray2Str($arrayList) {
        $findme   = 'REFUND';
        $findmepipe = '|';
        $paramStr = "";
        $flag = 1;
        foreach ($arrayList as $key => $value) {
            $pos = strpos($value, $findme);
            $pospipe = strpos($value, $findmepipe);
            if ($pos !== false || $pospipe !== false)
            {
                continue;
            }
            if ($flag) {
                $paramStr .= $this->checkString_e($value);
                $flag = 0;
            } else {
                $paramStr .= "|" . $this->checkString_e($value);
            }
        }
        return $paramStr;
    }

    public function redirect2PG($paramList, $key) {
        $hashString = $this->getchecksumFromArray($paramList);
        $checksum = $this->encrypt_e($hashString, $key);
    }

    public function removeCheckSumParam($arrayList) {
        if (isset($arrayList["CHECKSUMHASH"])) {
            unset($arrayList["CHECKSUMHASH"]);
        }
        return $arrayList;
    }
    function callAPI($apiURL, $requestParamList)
    {
        $jsonResponse      = "";
        $responseParamList = array();
        $JsonData          = json_encode($requestParamList);
        $postData          = 'JsonData=' . urlencode($JsonData);
        $ch                = curl_init($apiURL);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen($postData)
        ));
        $jsonResponse      = curl_exec($ch);
        $responseParamList = json_decode($jsonResponse, true);
        return $responseParamList;
    }

    function callNewAPI($apiURL, $requestParamList)
    {
        $jsonResponse      = "";
        $responseParamList = array();
        $JsonData          = json_encode($requestParamList);
        $postData          = 'JsonData=' . urlencode($JsonData);
        $ch                = curl_init($apiURL);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen($postData)
        ));
        $jsonResponse      = curl_exec($ch);
        $responseParamList = json_decode($jsonResponse, true);
        return $responseParamList;
    }

    public function callRefundApi($url, $parameters) {
        $dataString = 'JsonData='.json_encode($parameters);

        $ch = curl_init();                    // initiate curl
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);  // tell curl you want to post something
        curl_setopt($ch, CURLOPT_POSTFIELDS, $dataString); // define what you want to post
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // return the output in string format
        $headers = [];
        $headers[] = 'Content-Type: application/json';
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $output = curl_exec ($ch); // execute
        $info = curl_getinfo($ch);
        $responseParamList = json_decode($output, true);
        return $responseParamList;
    }


    /**
     * @return bool
     */
    public function getIsStage() {
        return $this->scopeConfig->getValue('payment/paytm/debug', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    /**
     * @return string
     */
    public function getMid() {
        return $this->scopeConfig->getValue('payment/paytm/MID', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    /**
     * @return string
     */
    public function getMerchantKey() {
        return $this->scopeConfig->getValue('payment/paytm/merchant_key', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    public function getRefundChecksumFromArray($arrayList, $key, $sort=1) {
        if ($sort != 0) {
            ksort($arrayList);
        }
        $str = $this->getRefundArray2Str($arrayList);
        $salt = $this->generateSalt_e(4);
        $finalString = $str . "|" . $salt;
        $hash = hash("sha256", $finalString);
        $hashString = $hash . $salt;
        $checksum = $this->encrypt_e($hashString, $key);
        return $checksum;
    }

    public function getRefundArray2Str($arrayList) {
        $findmepipe = '|';
        $paramStr = "";
        $flag = 1;
        foreach ($arrayList as $key => $value) {
            $pospipe = strpos($value, $findmepipe);
            if ($pospipe !== false)
            {
                continue;
            }

            if ($flag) {
                $paramStr .= $this->checkString_e($value);
                $flag = 0;
            } else {
                $paramStr .= "|" . $this->checkString_e($value);
            }
        }
        return $paramStr;
    }

    public function initiatePaytmRefund($order)
    {
        /**
         * commenting code for refund as per BKWYADMIN-744
         */
//        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/paytm_refund.log');
//        $logger = new \Zend\Log\Logger();
//        $logger->addWriter($writer);
//
//        $orderId = $order->getIncrementId();
//        $logger->info("======Paytm refund requested for order :: ".$orderId);
//
//        try{
//            $quoteId = $order->getQuoteId();
//            $quote = $this->quoteRepository->get($quoteId);
//            $paytmOrderId = $quote->getData('paytm_order_id');
//            /**
//             * Check the transaction status First
//             */
//            $merchantId = $this->getMid();
//            $merchantKey = $this->getMerchantKey();
//            $params = [
//                'MID' => $merchantId,
//                'ORDERID' => $paytmOrderId
//            ];
//            $checksumHash = $this->getChecksumFromArray($params, $merchantKey);
//            $params['CHECKSUMHASH'] = $checksumHash;//str_replace("+", "%2b", $checksumHash);
//
//            if ($this->getIsStage()) {
//                $apiUrl = $this->NEW_STATUS_QUERY_URL_TEST;
//            } else {
//                $apiUrl = $this->NEW_STATUS_QUERY_URL_PROD;
//            }
//            $logger->info("======Call to transaction status API starts :: ".$orderId);
//            $paytmStatusResponse = $this->callNewAPI($apiUrl, $params);
//            $logger->info("======Call to transaction status API response :: ".json_encode($paytmStatusResponse));
//            $logger->info("======Call to transaction status API ends :: ".$orderId);
//
//            if (
//                isset($paytmStatusResponse['STATUS']) &&
//                isset($paytmStatusResponse['TXNID']) &&
//                $paytmStatusResponse['STATUS'] == "TXN_SUCCESS"
//            ) {
//                $logger->info("======Received required params from transactions status API :: ".$orderId);
//                /**
//                 * Refund the success transactions
//                 */
//                $refundParams = [
//                    "MID"=> $merchantId,
//                    "ORDERID"=> $paytmOrderId,
//                    "TXNID"=> $order->getPayment()->getData('paytm_txn_id'),
//                    "TXNTYPE"=> "REFUND",
//                    "REFUNDAMOUNT"=> $paytmStatusResponse['TXNAMOUNT'],
//                    "REFID"=> $orderId."UID".substr(uniqid('', true), -5),
//                ];
//
//                $requestChecksumHash = $this->getRefundChecksumFromArray($refundParams, $merchantKey);
//                $refundParams['CHECKSUM'] = $requestChecksumHash;//str_replace("+", "%2b", $requestChecksumHash);
//
//                if ($this->getIsStage()) {
//                    $refundApiUrl = $this->PAYTM_REFUND_URL_TEST;
//                } else {
//                    $refundApiUrl = $this->PAYTM_REFUND_URL_PROD;
//                }
//                $logger->info("======Call to REFUND API starts :: ".$orderId);
//                $logger->info("======Call to REFUND API request :: ".json_encode($refundParams));
//                $paytmRefundResponse = $this->callRefundApi($refundApiUrl, $refundParams);
//                $logger->info("======Call to REFUND API response :: ".json_encode($paytmRefundResponse));
//                $logger->info("======Call to REFUND API ends :: ".$orderId);
//            }
//            return;
//        } catch (\Exception $e) {
//            $logger->info("======Error catched in refund method :: ".$orderId." :: ".$e->getMessage());
//        }
    }

}