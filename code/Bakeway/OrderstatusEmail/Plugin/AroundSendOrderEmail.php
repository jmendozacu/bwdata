<?php
/**
 * Bakeway
 *
 * @category  Bakeway
 * @package   Bakeway_OrderstatusEmail
 * @author    Bakeway
 */

namespace Bakeway\OrderstatusEmail\Plugin;

class AroundSendOrderEmail
{
    public function aroundSend(
        \Magento\Sales\Model\Order\Email\Sender\OrderSender\Interceptor $interceptor,
        \Closure $proceed,
        \Magento\Sales\Model\Order $order,
        $forceSyncMode = false
    ) {
        $sendEmail = false;
        $paymentMethod = $order->getPayment()->getMethod();

        if ($paymentMethod == \Bakeway\Razorpay\Model\PaymentMethod::METHOD_CODE) {
            if ($order->getPayment()->getData('razorpay_order_id') != null &&
                $order->getPayment()->getData('razorpay_payment_id') != null &&
                $order->getPayment()->getData('razorpay_signature') != null
            ) {
                $sendEmail = true;
            }
        } elseif ($paymentMethod == \Bakeway\Paytm\Model\Paytm::CODE) {
            if ($order->getPayment()->getData('paytm_txn_id') != null
            ) {
                $sendEmail = true;
            }
        }

        if ($sendEmail === true) {
            $result = $proceed($order, $forceSyncMode);
        }
    }
}