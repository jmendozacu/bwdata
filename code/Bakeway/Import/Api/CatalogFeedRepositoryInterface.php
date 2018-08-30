<?php

namespace Bakeway\Import\Api;

/**
 * Seller Information interface.
 */

interface CatalogFeedRepositoryInterface
{
    /**
     * Catalog Feed Api.
     *
     * @api
     * @param string $access_token
     * @return array
     * @throws \Magento\Framework\Exception\NotFoundException
     */
    public function getCatalogFeed($access_token);


}