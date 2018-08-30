<?php
/**
 * Bakeway
 *
 * @category  Bakeway
 * @package   Bakeway_CatalogSync
 * @author    Bakeway
 */

namespace Bakeway\CatalogSync\Model;

use Webkul\Marketplace\Model\Product as SellerProduct;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Bakeway\ProductApi\Helper\Data as ProductApiHelper;
use Magento\Catalog\Model\Product\Visibility as CatalogVisibility;
use Bakeway\Cities\Helper\Data as BakewayCityHelper;
use Bakeway\CatalogSync\Model\ResourceModel\CatalogSync\Collection as CatalogSyncCollection;

class CatalogProductSync {

    const PUNE_CITY_ID = 1;

    const STORE_ID = 1;

    /**
     * @var SellerProduct
     */
    protected $sellerProduct;

    /**
     * @var ProductCollectionFactory
     */
    protected $productCollection;

    /**
     * @var ProductApiHelper
     */
    protected $productApiHelper;

    /**
     * @var CatalogVisibility
     */
    protected $catalogVisibility;

    /**
     * @var BakewayCityHelper
     */
    protected $citiesHelper;

    /**
     * @var CatalogSyncCollection
     */
    protected $catalogSyncCollection;

    /**
     * @var \Bakeway\CatalogSync\Model\CatalogSync
     */
    protected $catalogSync;

    /**
     * @var \Magento\Catalog\Model\Product\Attribute\Source\Status
     */
    protected $productStatus;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * CatalogProductSync constructor.
     * @param SellerProduct $sellerProduct
     * @param ProductCollectionFactory $productCollection
     * @param ProductApiHelper $productApiHelper
     * @param CatalogVisibility $catalogVisibility
     * @param BakewayCityHelper $citiesHelper
     * @param CatalogSyncCollection $catalogSyncCollection
     * @param \Bakeway\CatalogSync\Model\CatalogSync $catalogSync
     * @param \Magento\Catalog\Model\Product\Attribute\Source\Status $productStatus
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     */
    public function __construct(
        SellerProduct $sellerProduct,
        ProductCollectionFactory $productCollection,
        ProductApiHelper $productApiHelper,
        CatalogVisibility $catalogVisibility,
        BakewayCityHelper $citiesHelper,
        CatalogSyncCollection $catalogSyncCollection,
        \Bakeway\CatalogSync\Model\CatalogSync $catalogSync,
        \Magento\Catalog\Model\Product\Attribute\Source\Status $productStatus,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\ObjectManagerInterface $objectManager
    ) {
        $this->sellerProduct = $sellerProduct;
        $this->productCollection = $productCollection;
        $this->productApiHelper = $productApiHelper;
        $this->catalogVisibility = $catalogVisibility;
        $this->citiesHelper = $citiesHelper;
        $this->catalogSyncCollection = $catalogSyncCollection;
        $this->catalogSync = $catalogSync;
        $this->productStatus = $productStatus;
        $this->storeManager = $storeManager;
        $this->objectManager = $objectManager;
    }

    public function syncProductAttributes() {
        $syncedProductsArray = [];
        $sellerProductArray = [];
        $storeProdCollection = $this->sellerProduct->getCollection()
            ->addFieldToFilter('status', 1)
            ->addFieldToSelect(['mageproduct_id','seller_id']);
        $storeProductIDs = $storeProdCollection->getAllIds();
        $storeProdCollection->getSelect()->joinLeft(
            ['bw_loc' => $storeProdCollection->getTable('bakeway_partner_locations')],
            'main_table.seller_id=bw_loc.seller_id',
            ['city_id']
        );
        $storeProdCollection->getSelect()->group('mageproduct_id');

        foreach ($storeProdCollection as $sellerProduct) {
            $productId = $sellerProduct->getData('mageproduct_id');
            $sellerProductArray[$productId]['city_id'] = $sellerProduct->getData('city_id');
            $sellerProductArray[$productId]['seller_id'] = $sellerProduct->getData('seller_id');
        }

        $collection = $this->productCollection->create()
            ->addFieldToFilter('entity_id', ['in' => $storeProductIDs])
            ->addAttributeToFilter('status', ['in' => $this->productStatus->getVisibleStatusIds()])
            ->addAttributeToSelect('*');
        $collection->addStoreFilter(self::STORE_ID);
        $collection->setVisibility($this->catalogVisibility->getVisibleInSiteIds());
        //$collection->joinAttribute('status', 'catalog_product/status', 'entity_id', null, 'inner');
        $collection->joinAttribute('visibility', 'catalog_product/visibility', 'entity_id', null, 'inner');

        $syncCollection = $this->catalogSyncCollection
                            ->addFieldToSelect('*');
        foreach ($syncCollection as $syncedProduct) {
            $syncedProductsArray[$syncedProduct->getData('product_id')] = $syncedProduct->getData('id');
        }
        $collection->load();
        foreach ($collection as $product) {
            $productId = $product->getEntityId();
            $type = $product->getTypeId();
            if (isset($sellerProductArray[$productId]['city_id'])) {
                $cityId = $sellerProductArray[$productId]['city_id'];
            } else {
                $cityId = self::PUNE_CITY_ID;
            }
            if (isset($sellerProductArray[$productId]['seller_id'])) {
                $sellerId = $sellerProductArray[$productId]['seller_id'];
            } else {
                $sellerId = null;
            }

            $categories = $product->getCategoryIds();
            $categoryNameArray = $this->productApiHelper->getCategoryName($categories);
            $categoryJson = null;
            if (is_array($categoryNameArray)) {
                $categoryJson = serialize($categoryNameArray);
            }
            switch ($type) {
                case 'simple' :
                    $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/mksync_log.log');
                    $logger = new \Zend\Log\Logger();
                    $logger->addWriter($writer);

                    $isConfigurable = 0;
                    $ruleData = $this->productApiHelper->getRuleDataByProduct($product->getId());

                    $logger->info("Below is the rule data for PRODUCT ID : ". $product->getId());
                    $logger->info(json_encode($ruleData));
                    /**
                     * adding rule name field
                     */
                    if (isset($ruleData['name'])) {
                        $discountRule = $ruleData['name'];
                    } else {
                        $discountRule = null;
                    }
                    if (isset($ruleData['fixed_discount_start_date'])) {
                        $discountRuleStartDate = $ruleData['fixed_discount_start_date'];
                    } else {
                        $discountRuleStartDate = null;
                    }
                    if (isset($ruleData['fixed_discount_end_date'])) {
                        $discountRuleEndDate = $ruleData['fixed_discount_end_date'];
                    } else {
                        $discountRuleEndDate = null;
                    }
                    $rulePrice = $this->productApiHelper->getCatalogRulePrice($product->getId());
                    $ruleTaxPrice = $this->productApiHelper->getCatalogRuleTaxPrice($product, $rulePrice);
                    if (!($rulePrice && $ruleTaxPrice)) {
                        $rulePrice = 0;
                        $ruleTaxPrice = 0;
                    }
                    $flavour = $product->getAttributeText("cake_flavour");
                    $weight = $product->getAttributeText("cake_weight");
                    $ingredient = $product->getAttributeText("cake_ingredients");
                    $specialPrice = $product->getSpecialPrice();
                    $priceExclTax = $product->getPrice();
                    $priceInclTax = $this->productApiHelper->getProductTaxPrice($product, null, null, false, null, $cityId);
                    $advanceOrderIntimation = $product->getData('advance_order_intimation');
                    $advanceOrderIntimationUnit = $product->getData('advance_order_intimation_unit');
                    break;
                
                case 'configurable' :
                    $isConfigurable = 1;
                    $minPriceProd = $this->productApiHelper->getMinproductPrice($product, null, null, $cityId);
                    $priceExclTax = $minPriceProd['min_price'];
                    $priceInclTax = $minPriceProd['tax_incl_price'];

                    if (isset($minPriceProd['simple_prod_obj']) &&
                        $minPriceProd['simple_prod_obj'] != null) {
                        $simpleProduct = $minPriceProd['simple_prod_obj'];
                    } else {
                        $simpleProduct = null;
                    }

                    $ruleData = $this->productApiHelper->getRuleDataByProduct($minPriceProd['product_id']);
                    /**
                     * adding rule name field
                     */
                    if (isset($ruleData['name'])) {
                        $discountRule = $ruleData['name'];
                    } else {
                        $discountRule = null;
                    }
                    if (isset($ruleData['fixed_discount_start_date'])) {
                        $discountRuleStartDate = $ruleData['fixed_discount_start_date'];
                    } else {
                        $discountRuleStartDate = null;
                    }
                    if (isset($ruleData['fixed_discount_end_date'])) {
                        $discountRuleEndDate = $ruleData['fixed_discount_end_date'];
                    } else {
                        $discountRuleEndDate = null;
                    }
                    $rulePrice = $this->productApiHelper->getCatalogRulePrice($minPriceProd['product_id']);
                    if ($simpleProduct != null) {
                        $ruleTaxPrice = $this->productApiHelper->getCatalogRuleTaxPrice($minPriceProd['simple_prod_obj'], $rulePrice);
                    }

                    if (!($rulePrice && $ruleTaxPrice)) {
                        $rulePrice = 0;
                        $ruleTaxPrice = 0;
                    }

                    if ($simpleProduct != null) {
                        $flavour = $simpleProduct->getAttributeText("cake_flavour");
                        $weight = $simpleProduct->getAttributeText("cake_weight");
                        $ingredient = $simpleProduct->getAttributeText("cake_ingredients");
                        $specialPrice = $simpleProduct->getSpecialPrice();
                        $advanceOrderIntimation = $simpleProduct->getData('advance_order_intimation');
                        $advanceOrderIntimationUnit = $simpleProduct->getData('advance_order_intimation_unit');

                    } else {
                        $flavour = null;
                        $weight = null;
                        $ingredient = null;
                        $specialPrice = null;
                        $advanceOrderIntimation = null;
                        $advanceOrderIntimationUnit = null;
                    }
                    break;
            }

            $syncModel = $this->objectManager->create(
                '\Bakeway\CatalogSync\Model\CatalogSync');
            if (isset($syncedProductsArray[$productId])) {
                $syncModel->load($syncedProductsArray[$productId]);
            }
            $syncModel->setData('product_id', $productId);
            $syncModel->setData('seller_id', $sellerId);
            $syncModel->setData('is_configurable', $isConfigurable);
            $syncModel->setData('special_price', $specialPrice);
            $syncModel->setData('price_incl_tax', $priceInclTax);
            $syncModel->setData('price_excl_tax', $priceExclTax);
            $syncModel->setData('catalog_discount_price', $rulePrice);
            $syncModel->setData('catalog_discount_price_incl_tax', $ruleTaxPrice);
            $syncModel->setData('catalog_rule_name', $discountRule);
            $syncModel->setData('cake_flavour', $flavour);
            $syncModel->setData('cake_ingredients', $ingredient);
            $syncModel->setData('cake_weight', $weight);
            $syncModel->setData('advance_order_intimation', $advanceOrderIntimation);
            $syncModel->setData('advanced_order_intimation_unit', $advanceOrderIntimationUnit);
            $syncModel->setData('categories_json', $categoryJson);
            $syncModel->setData('fixed_discount_start_date', $discountRuleStartDate);
            $syncModel->setData('fixed_discount_end_date', $discountRuleEndDate);
            
            try {
                $syncModel->save();
            } catch (\Exception $e) {
            }
        }
    }
}