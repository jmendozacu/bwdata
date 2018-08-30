<?php

/**
 * Bakeway
 *
 * @category  Bakeway
 * @package   Bakeway_PartnerWebapi
 * @author    Bakeway
 */

namespace Bakeway\PartnerWebapi\Model;

use \Bakeway\PartnerWebapi\Api\GlobalSearchInterface;
use \Bakeway\ProductApi\Helper\Filter as ProductApiFilterHelper;
use \Magento\Framework\Exception\LocalizedException;
use \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use \Webkul\Marketplace\Model\ResourceModel\Seller\Collection as SellerCollection;

class GlobalSearchRepository implements GlobalSearchInterface {

    const FLAVOUR_ATTR_CODE = 'cake_flavour';

    /**
     * @var ProductApiFilterHelper
     */
    protected $productApiFilterHelper;

    /**
     * @var CategoryCollectionFactory
     */
    protected $categoryCollectionFactory;

    /**
     * @var SellerCollection
     */
    protected $sellerCollection;

    /**
     * GlobalSearchRepository constructor.
     * @param ProductApiFilterHelper $productApiFilterHelper
     * @param CategoryCollectionFactory $categoryCollectionFactory
     * @param SellerCollection $sellerCollection
     */
    public function __construct(
        ProductApiFilterHelper $productApiFilterHelper,
        CategoryCollectionFactory $categoryCollectionFactory,
        SellerCollection $sellerCollection
    )
    {
        $this->productApiFilterHelper = $productApiFilterHelper;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->sellerCollection = $sellerCollection;
    }

    /**
     * Get Global Autocomplete List.
     *
     * @api
     * @param string|null $searchterm
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getGlobalSearchAutoCompleteList($searchterm = null) {
        $result =[];
        if ($searchterm === null || trim($searchterm) == '') {
            throw new LocalizedException(__('Search Term can not be null.'));
        }
        $searchterm = strtolower($searchterm);

        /**
         * Flavours Search
         */
        $flavours = [];
        $flavours['attr_code'] = self::FLAVOUR_ATTR_CODE;
        $flavours['label'] = 'Flavours';
        $newFlavourVal=[];
        $flavoursArray = $this->productApiFilterHelper->getProductAttributeFilter([self::FLAVOUR_ATTR_CODE]);

        if (isset($flavoursArray[0]['value']) && !empty($flavoursArray[0]['value']) && is_array($flavoursArray[0]['value'])) {
            $i=0;
            foreach ($flavoursArray[0]['value'] as $key => $value) {
                if (strpos(strtolower($value['label']), $searchterm) !== false) {
                    $newFlavourVal[$i]['value'] = $value['value'];
                    $newFlavourVal[$i]['label'] = $value['label'];
                    $i++;
                }
            }
        }
        $flavours['value'] = array_slice($newFlavourVal, 0, 5);

        /**
         * Category Search
         */
        $catArray['attr_code'] = 'category_id';
        $catArray['label'] = 'Categories';
        $catVal = [];
        $cake = $this->categoryCollectionFactory->create()
            ->addFieldToFilter("name", ['eq'=>"Cakes"])
            ->getFirstItem();
        $cakeCatId = $cake->getId();

        $children = [];
        if (isset($cakeCatId)) {
            $childrenCategories = $cake->getChildrenCategories();
            foreach ($childrenCategories as $child) {
                $children[] = $child->getData('entity_id');
            }
        }

        $catCollection = $this->categoryCollectionFactory->create()
                        ->addFieldToFilter("name", ['like'=>"%$searchterm%"])
                        ->addFieldToFilter("entity_id", ['in'=>$children])
                        ->setPageSize(5);
        $i = 0;
        foreach ($catCollection as $category) {
            $catVal[$i]['value'] = $category->getEntityId();
            $catVal[$i]['label'] = $category->getName();
            $i++;
        }
        $catArray['value'] = $catVal;
        $result[] = $catArray;

        /**
         * Seller Search
         */
        $sellers['attr_code'] = 'seller_id';
        $sellers['label'] = 'Brands';
        $sellerVal = [];
        $sellerCollection = $this->sellerCollection
                            ->addFieldToFilter("business_name", ['like'=>"%$searchterm%"])
                            ->addFieldToFilter("is_seller", 1)
                            ->addFieldToFilter("is_live_ready", 1)
                            ->setPageSize(5);
        $i = 0;
        foreach ($sellerCollection as $seller) {
            $sellerVal[$i]['value'] = $seller->getEntityId();
            $sellerVal[$i]['label'] = $seller->getData('business_name');
            $i++;
        }
        $sellers['value'] = $sellerVal;
        $result[] = $sellers;
        $result[] = $flavours;
        return json_decode(json_encode($result));
    }
}