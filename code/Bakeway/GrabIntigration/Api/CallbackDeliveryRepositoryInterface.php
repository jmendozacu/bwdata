<?php

namespace Bakeway\GrabIntigration\Api;

use \Magento\Framework\Exception\NotFoundException;
/**
 * Seller Information interface.
 */

interface CallbackDeliveryRepositoryInterface
{
    /**
     * Update Customer Delivery status.
     *
     * @api
     * @param int $orderStatus
     * @param int $grabOrderId
     * @param int $clientOrderId
     * @param int $merchantBillNo
     * @param int $merchantId
     * @param string $riderName
     * @param int $riderPhone
     * @param string $riderLatitude
     * @param string $riderLongitude
     * @param int $expectedDeliveryTime
     * @param string $dttm
     * @return array
     * @throws \Magento\Framework\Exception\NotFoundException
     */
    public function updateDeliveryStatus($orderStatus, $grabOrderId , $clientOrderId, $merchantBillNo, $merchantId, $riderName,
                                         $riderPhone, $riderLatitude, $riderLongitude, $expectedDeliveryTime,$dttm);


    }