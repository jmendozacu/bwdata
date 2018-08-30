<?php

namespace Bakeway\Quotemanagement\Api;

/**
 * Razor QuoteOrder management API.
 */
interface CreateOrderManagementRepositoryInterface {

    /**
     * @api
     * @param int $customerId
     * @param int $cartId
     * @param mixed $payload
     * @return array
     * @throws \Magento\Framework\Exception\NotFoundException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function createOrder($customerId, $cartId, $payload);

    /**
     * @api
     * @param string $cartId
     * @param mixed $payload
     * @return array
     * @throws \Magento\Framework\Exception\NotFoundException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function createGuestOrder($cartId, $payload);
}
