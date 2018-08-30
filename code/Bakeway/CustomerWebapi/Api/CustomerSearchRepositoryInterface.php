<?php
/**
 * Bakeway
 *
 * @category  Bakeway
 * @package   Bakeway_CustomerWebapi
 * @author    Bakeway
 */

namespace Bakeway\CustomerWebapi\Api;

/**
 * Customer Search interface.
 */

interface CustomerSearchRepositoryInterface
{
    /**
     * Main search by locality and bakery
     * 
     * @param int $cityId
     * @param string|null $searchTerm
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function search($cityId, $searchTerm = null);
}