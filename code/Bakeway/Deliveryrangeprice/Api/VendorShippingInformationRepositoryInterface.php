<?php

namespace Bakeway\Deliveryrangeprice\Api;

/**
 * Seller Information interface.
 */

interface VendorShippingInformationRepositoryInterface
{
    /**
     * Get vendor Shipping Value
     * @api
     * @param int $vendorId
     * @param string $sku
     * @param string $latitude
     * @param string $longitude
     * @param string $storename
     * @return array
     * @throws \Magento\Framework\Exception\NotFoundException
     */
    public function getDelivery($vendorId ,$sku ,$latitude ,$longitude ,$storename = null);
}