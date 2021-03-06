<?php
/**
 * Bakeway
 *
 * @category  Bakeway
 * @package   Bakeway_GuestOrder
 * @author    Bakeway
 */

namespace Bakeway\GuestOrder\Observer;

use Magento\Framework\Event\ObserverInterface;
use Bakeway\CustomAttributes\Helper\Data as CustomAttributesHelper;

class SalesQuoteSubmitBefore implements ObserverInterface
{
    /**
     * @var CustomAttributesHelper
     */
    protected $customAttributeHelper;

    /**
     * SalesQuoteSubmitBefore constructor.
     * @param CustomAttributesHelper $customAttributeHelper
     */
    public function __construct(
        CustomAttributesHelper $customAttributeHelper
    )
    {
        $this->customAttributeHelper = $customAttributeHelper;
    }
    /**
     * sales quote submit before event handler
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/order_process.log');
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);

        $quote = $observer->getQuote();
        $order = $observer->getOrder();
        $deliveryTime = '';
        $deliveryType = '';
        $logger->info("======Starting the quote item validations before placing order======".$quote->getData('entity_id'));

        if ($quote->getData('delivery_type')) {
            $deliveryType = $quote->getData('delivery_type');
        }
        if ($quote->getData('delivery_time')) {
            $deliveryTime = $quote->getData('delivery_time');
        }

        $logger->info("=======End the quote item validations before placing order======".$quote->getData('entity_id'));
        $isGuest = $quote->getData('customer_is_guest');
        $logger->info("=======Tracking token start======".$quote->getData('entity_id'));
        //if ($isGuest != 0) {
            $tokenString = $this->generateUniqueString(48);
            $order->setData('order_tracking_token', $tokenString);
       // }
        $logger->info("=======Tracking token ends======".$quote->getData('entity_id'));
        return;
    }

    /**
     * @param int $min
     * @param int $max
     * @return int
     */
    protected function getRandomInteger($min, $max)
    {
        $range = ($max - $min);

        if ($range < 0) {
            return $min;
        }

        $log = log($range, 2);

        $bytes = (int) ($log / 8) + 1;

        $bits = (int) $log + 1;

        $filter = (int) (1 << $bits) - 1;

        do {
            $rnd = hexdec(bin2hex(openssl_random_pseudo_bytes($bytes)));
            $rnd = $rnd & $filter;

        } while ($rnd >= $range);

        return ($min + $rnd);
    }

    /**
     * @param int $length
     * @return string
     */
    public function generateUniqueString($length)
    {
        $alphabet = implode(range('a', 'z')) . implode(range('A', 'Z')) . implode(range(0, 9));
        $token = '';

        for ($i = 0; $i < $length; $i++) {
            $randomKey = $this->getRandomInteger(0, strlen($alphabet));
            $token .= $alphabet[$randomKey];
        }

        return $token;
    }
}