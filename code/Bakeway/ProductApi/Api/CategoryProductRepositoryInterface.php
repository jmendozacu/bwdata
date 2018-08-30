<?php
/**
 * Bakeway
 *
 * @category  Bakeway
 * @package   Bakeway_ProductApi
 * @author    Bakeway
 */
namespace Bakeway\ProductApi\Api;

/**
 * Category Product Interface.
 */
interface CategoryProductRepositoryInterface {

    /**
     * Get Category Products
     * 
     * @api
     * @param int $categoryId
     * @param string|null $city
     * @param string|null $lat
     * @param string|null $long
     * @param \Magento\Framework\Api\SearchCriteria|null $searchCriteria The search criteria.
     * @return array
     * @throws NotFoundException
     * @throws LocalizedException
     */
    public function getProducts($categoryId, $city = null, $lat = null, $long = null, \Magento\Framework\Api\SearchCriteria $searchCriteria = null);
}