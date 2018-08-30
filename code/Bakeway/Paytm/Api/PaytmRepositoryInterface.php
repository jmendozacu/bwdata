<?php
/**
 * Bakeway
 *
 * @category  Bakeway
 * @package   Bakeway_Paytm
 * @author    Bakeway
 */

namespace Bakeway\Paytm\Api;

/**
 * Paytm payment gateway interface.
 */

interface PaytmRepositoryInterface
{
    /**
     * @api
     * @param string $cartId
     * @param mixed $payload
     * @return int $orderId
     * @throws \Magento\Framework\Exception\NotFoundException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function createPaytmOrder($cartId, $payload);

    /**
     * @api
     * @param string $cartId
     * @param mixed $payload
     * @param string $maskedCartId
     * @return int $orderId
     * @throws \Magento\Framework\Exception\NotFoundException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function createPaytmGuestOrder($cartId, $payload, $maskedCartId);


    /**
     * @param int $quoteId
     * @param mixed  $payload
     * @param int $orderId
     * @return boolean
     * @return mixed
     */
    public function paytmOrderPayVerification($quoteId,$payload ,$orderId);
}