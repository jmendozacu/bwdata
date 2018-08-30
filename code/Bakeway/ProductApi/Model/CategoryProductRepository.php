<?php

/**
 * Bakeway
 *
 * @category  Bakeway
 * @package   Bakeway_ProductApi
 * @author    Bakeway
 */

namespace Bakeway\ProductApi\Model;

use Bakeway\ProductApi\Api\CategoryProductRepositoryInterface;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Webkul\Marketplace\Model\Product as VendorProduct;
use Magento\Framework\App\ResourceConnectionFactory;
use Magento\Framework\Exception\NotFoundException;
use Magento\Catalog\Helper\ImageFactory as ProductImageHelper;
use Magento\Store\Model\StoreManagerInterface as StoreManager;
use Magento\Store\Model\App\Emulation as AppEmulation;
use Magento\Catalog\Model\Product\Visibility as CatalogVisibility;
use Webkul\Marketplace\Helper\Data as MarketplaceHelper;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\ObjectManagerInterface;
use Bakeway\HomeDeliveryshipping\Helper\Data as HomeDeliveryHelper;
use Bakeway\Cities\Helper\Data as BakewayCityHelper;
use \Bakeway\Partnerlocations\Model\ResourceModel\Partnerlocations\CollectionFactory as LocationCollection;
use Bakeway\ProductApi\Helper\Filter as ProductFilterHelper;
use \Bakeway\CatalogSync\Model\ResourceModel\CatalogSync\Collection as CatalogSyncCollection;
use \Bakeway\CatalogSync\Helper\Data as CatalogSyncHelper;

/**
 * Class CategoryProductRepository
 * @package Bakeway\ProductApi\Model
 */
class CategoryProductRepository implements CategoryProductRepositoryInterface
{

    const SEARCH_RADIUS = 3.5;

    const PROD_FILTER_ATTR = ['cake_weight', 'cake_flavour', 'cake_ingredients'];

    /**
     * @var ResourceConnectionFactory
     */
    protected $_resourceConnection;

    /**
     * @var ProductCollectionFactory
     */
    protected $_productCollection;

    /**
     * @var VendorProduct
     */
    protected $_vendorProduct;

    /**
     * @var \Magento\Catalog\Api\Data\ProductSearchResultsInterfaceFactory
     */
    protected $searchResultsFactory;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @var \Magento\Catalog\Api\ProductAttributeRepositoryInterface
     */
    protected $metadataService;

    /**
     * @var \Magento\Framework\Api\ExtensionAttribute\JoinProcessorInterface
     */
    protected $extensionAttributesJoinProcessor;

    /**
     * @var \Magento\Catalog\Helper\ImageFactory
     */
    protected $productImageHelper;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\Store\Model\App\Emulation
     */
    protected $appEmulation;

    /**
     * @var CatalogVisibility
     */
    protected $catalogVisibility;

    /**
     * @var \Bakeway\ProductApi\Helper\Data
     */
    protected $productapihelper;

    /**
     * @var MarketplaceHelper
     */
    protected $marketplaceHelper;

    /**
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var HomeDeliveryHelper
     */
    protected $homeDeliveryHelper;

    /**
     * @var BakewayCityHelper
     */
    protected $bakewayCityHelper;

    /**
     * @var LocationCollection
     */
    protected $locationsCollection;

    /**
     * ProductFilterHelper
     */
    protected $productFilterHelper;

    /**
     * @var
     */
    protected $priceFilter;

    /**
     * @var
     */
    protected $priceFilterValue;

    /**
     * @var
     */
    protected $offerFilter;

    /**
     * @var
     */
    protected $offerFilterValue;

    /**
     * @var
     */
    protected $prodFilterArr = [];

    /**
     * @var CatalogSyncCollection
     */
    protected $catalogSyncCollection;

    /**
     * @var CatalogSyncHelper
     */
    protected $catalogSyncHelper;

    /**
     * @var int value for pickup enable filter
     */
    protected $pickupValue = 0;

    /**
     * @var int value for delivery enable filter
     */
    protected $deliveryValue = 0;

    /**
     * VendorProductRepository constructor.
     *
     * @param ResourceConnectionFactory $_resourceConnection
     * @param ProductCollectionFactory $_productCollection
     * @param VendorProduct $_vendorProduct
     * @param \Magento\Catalog\Api\Data\ProductSearchResultsInterfaceFactory $searchResultsFactory
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     * @param \Magento\Catalog\Api\ProductAttributeRepositoryInterface $metadataServiceInterface
     * @param \Magento\Framework\Api\ExtensionAttribute\JoinProcessorInterface $extensionAttributesJoinProcessor
     * @param \Magento\Catalog\Helper\ImageFactory
     * @param \Magento\Store\Model\StoreManagerInterface
     * @param \Magento\Store\Model\App\Emulation
     * @param \Bakeway\ProductApi\Helper\Data
     * @param CatalogVisibility $catalogVisibility
     * @param MarketplaceHelper $marketplaceHelper
     * @param ObjectManagerInterface $objectManager
     * @param HomeDeliveryHelper $homeDeliveryHelper
     * @param BakewayCityHelper $bakewayCityHelper
     * @param LocationCollection $locationsCollection
     * @param CatalogSyncCollection $catalogSyncCollection
     * @param CatalogSyncHelper $catalogSyncHelper
     */
    public function __construct(
        ResourceConnectionFactory $_resourceConnection,
        ProductCollectionFactory $_productCollection,
        VendorProduct $_vendorProduct,
        \Magento\Catalog\Api\Data\ProductSearchResultsInterfaceFactory $searchResultsFactory,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Catalog\Api\ProductAttributeRepositoryInterface $metadataServiceInterface,
        \Magento\Framework\Api\ExtensionAttribute\JoinProcessorInterface $extensionAttributesJoinProcessor,
        ProductImageHelper $productImageHelper,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Store\Model\App\Emulation $appEmulation,
        CatalogVisibility $catalogVisibility,
        \Bakeway\ProductApi\Helper\Data $productapiHelper,
        MarketplaceHelper $marketplaceHelper,
        ObjectManagerInterface $objectManager,
        HomeDeliveryHelper $homeDeliveryHelper,
        BakewayCityHelper $bakewayCityHelper,
        LocationCollection $locationsCollection,
        CatalogSyncCollection $catalogSyncCollection,
        CatalogSyncHelper $catalogSyncHelper
    )
    {
        $this->_resourceConnection = $_resourceConnection;
        $this->_productCollection = $_productCollection;
        $this->_vendorProduct = $_vendorProduct;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->metadataService = $metadataServiceInterface;
        $this->extensionAttributesJoinProcessor = $extensionAttributesJoinProcessor;
        $this->productImageHelper = $productImageHelper;
        $this->storeManager = $storeManager;
        $this->appEmulation = $appEmulation;
        $this->catalogVisibility = $catalogVisibility;
        $this->productapihelper = $productapiHelper;
        $this->marketplaceHelper = $marketplaceHelper;
        $this->objectManager = $objectManager;
        $this->homeDeliveryHelper = $homeDeliveryHelper;
        $this->bakewayCityHelper = $bakewayCityHelper;
        $this->locationsCollection = $locationsCollection;
        $this->catalogSyncCollection = $catalogSyncCollection;
        $this->catalogSyncHelper = $catalogSyncHelper;
    }


    /**
     * Helper function that provides full cache image url
     * @param \Magento\Catalog\Model\Product
     * @return string
     */
    protected function getImageUrl($product, string $imageType = '')
    {
        if ($imageType == 'product_page_image_large') {
            $imageUrl = $this->productImageHelper->create()->init($product, $imageType)->keepAspectRatio(TRUE)->keepFrame(FALSE)->resize(225, 225)->getUrl();
        } else {
            $imageUrl = $imageUrl = $this->productImageHelper->create()->init($product, $imageType)->getUrl();
        }
        return $imageUrl;
    }

    /**
     * Helper function that adds a FilterGroup to the collection.
     *
     * @param \Magento\Framework\Api\Search\FilterGroup $filterGroup
     * @param Collection $collection
     * @return void
     */
    protected function addFilterGroupToCollection(
        \Magento\Framework\Api\Search\FilterGroup $filterGroup, Collection $collection
    )
    {
        $fields = [];
        $categoryFilter = [];
        $prodAttrFields = [];
        foreach ($filterGroup->getFilters() as $filter) {
            $conditionType = $filter->getConditionType() ? $filter->getConditionType() : 'eq';

            if ($filter->getField() == 'category_id') {
                $categoryFilter[$conditionType][] = $filter->getValue();
                continue;
            }

            if ($filter->getField() == 'advance_order_intimation') {
                $fields[] = ['attribute' => $filter->getField(), $conditionType => (int)$filter->getValue()];
                continue;
            }

            if ($filter->getField() == 'price') {
                $this->priceFilter = true;
                $this->priceFilterValue = $filter->getValue();
                continue;
            }

            if ($filter->getField() == 'offer') {
                $this->offerFilter = true;
                $this->offerFilterValue = $filter->getValue();
                continue;
            }

            if ($filter->getField() == 'delivery') {
                $value = $filter->getValue();
                if ($value) {
                    $this->deliveryValue = $filter->getValue();
                }
                continue;
            }

            if ($filter->getField() == 'pickup') {
                $value = $filter->getValue();
                if ($value) {
                    $this->pickupValue = $filter->getValue();
                }
                continue;
            }

            if (in_array($filter->getField(), self::PROD_FILTER_ATTR)) {
                $prodAttrFields[] = ['attribute' => $filter->getField(), $conditionType => $filter->getValue()];
                continue;
            }

            $fields[] = ['attribute' => $filter->getField(), $conditionType => $filter->getValue()];
        }

        if ($prodAttrFields) {
            $this->prodFilterArr[] = $prodAttrFields;
        }

        if ($categoryFilter) {
            $collection->addCategoriesFilter($categoryFilter);
        }

        if ($fields) {
            $collection->addFieldToFilter($fields);
        }
    }

    /**
     * @param \Magento\Framework\Api\Search\FilterGroup $filterGroup
     */
    protected function addDeliveryFilterGroup(
        \Magento\Framework\Api\Search\FilterGroup $filterGroup
    )
    {
        foreach ($filterGroup->getFilters() as $filter) {
            if ($filter->getField() == 'delivery') {
                $value = $filter->getValue();
                if ($value) {
                    $this->deliveryValue = $filter->getValue();
                }
                continue;
            }

            if ($filter->getField() == 'pickup') {
                $value = $filter->getValue();
                if ($value) {
                    $this->pickupValue = $filter->getValue();
                }
                continue;
            }
        }
    }

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
    public function getProducts($categoryId, $city = null, $lat = null, $long = null, \Magento\Framework\Api\SearchCriteria $searchCriteria = null)
    {
        if ($city === null) {
            throw new LocalizedException(__('Please select the city to see product list'));
        }

        if ($categoryId === null) {
            throw new LocalizedException(__('No category requested for listing'));
        }

        $cityId = $this->bakewayCityHelper->getCityIdByName($city);

        $storeLocationCollection = $this->locationsCollection->create();
        $storeLocationCollection->addFieldToSelect(['seller_id', 'store_unique_name', 'store_locality_area']);

        if ($cityId !== false) {
            $storeLocationCollection->addFieldToFilter('main_table.city_id', $cityId);
        } else {
            $storeLocationCollection->addFieldToFilter('main_table.city_id', 0);
        }
        $storeLocationCollection->addFieldToFilter('main_table.is_active', 1);

        /**
         * Joining bakeway_sub_locations Table (SUB-HUB)
         */
        $storeLocationCollection->getSelect()->joinLeft(
            ['sub_loc' => $storeLocationCollection->getTable('bakeway_sub_locations')],
            'main_table.sub_loc_id=sub_loc.id',
            ['area_name']
        );

        /**
         * Code to calculate the distance between store and the delivery area
         * Sorted the stores in ASC order of distance
         */
        if ($lat != '' && $long != '') {
            $storeLocationCollection->getSelect()->columns(
                [
                    'distance' => new \Zend_Db_Expr("ST_Distance_Sphere(POINT(" . $long . ", " . $lat . "), sub_loc_geo_point)/1000")
                ]);
            $storeLocationCollection->setOrder('distance', 'ASC');
            $storeLocationCollection->getSelect()->having('distance<=?', self::SEARCH_RADIUS);
        }
        $storeLocationCollection->getSelect()->joinLeft(
            ['mp_udata' => $storeLocationCollection->getTable('marketplace_userdata')],
            'main_table.seller_id=mp_udata.seller_id',
            ['shop_title', 'business_name', 'userdata_brand']
        );

        $storeLocationCollection->getSelect()->columns(
            [
                'unique_col_grp' => new \Zend_Db_Expr("IFNULL(mp_udata.userdata_brand,UUID())")
            ]);

        $storeLocationCollection->getSelect()->where('mp_udata.is_seller=?', 1);
        $storeLocationCollection->getSelect()->where('mp_udata.is_live_Ready=?', 1);
        $storeLocationCollection->getSelect()->where('mp_udata.userdata_shop_operatational_status=?', 0);
        $storeLocationCollection->getSelect()->group('unique_col_grp');

        $storeDetailsArr = [];
        $sellerIds = [];
        $businessName = [];

        foreach ($storeLocationCollection as $storeObj) {

            if (!in_array($storeObj->getData('seller_id'), $sellerIds) &&
                !in_array($storeObj->getData('business_name'), $businessName)) {
                $sellerIds[] = $storeObj->getData('seller_id');
                $businessName[] = $storeObj->getData('business_name');
            }
            $vendorId = $storeObj->getData('seller_id');
            if (!isset($storeDetailsArr[$vendorId]['locality']) &&
                !isset($storeDetailsArr[$vendorId]['unique_name'])
            ) {
                $storeDetailsArr[$vendorId]['locality'] = $storeObj->getData('store_locality_area');
                $storeDetailsArr[$vendorId]['unique_name'] = $storeObj->getData('store_unique_name');
            }
        }

        //$sellerIds = $storeLocationCollection->getColumnValues('seller_id');
        $sellerIds = array_unique($sellerIds);
        $storeProdCollection = $this->_vendorProduct->getCollection()
            ->addFieldToFilter('main_table.seller_id', ['in' => $sellerIds])
            ->addFieldToFilter('status', 1)
            ->addFieldToSelect(['mageproduct_id', 'seller_id']);
        $storeProdCollection->getSelect()->joinLeft(
            ['mp_udata' => $storeProdCollection->getTable('marketplace_userdata')],
            'main_table.seller_id=mp_udata.seller_id',
            ['business_name', 'is_conglomerate']
        );
        if ($searchCriteria !== null) {
            //Add filters from root filter group to the collection
            foreach ($searchCriteria->getFilterGroups() as $group) {
                $this->addDeliveryFilterGroup($group);
            }

            if ($this->deliveryValue) {
                $storeProdCollection->getSelect()->join(array('bpl' => 'bakeway_partner_locations'), 'main_table.seller_id = bpl.seller_id');
                $storeProdCollection->getSelect()->where('bpl.is_grab_active = 1 OR mp_udata.delivery = 1');
            }
            if ($this->pickupValue) {
                $storeProdCollection->getSelect()->join(array('bpl' => 'bakeway_partner_locations'), 'main_table.seller_id = bpl.seller_id');
                $storeProdCollection->addFieldToFilter('mp_udata.is_pickup', 1);
                $storeProdCollection->addFieldToFilter('bpl.is_grab_active', 0);
                $storeProdCollection->addFieldToFilter('mp_udata.delivery', 0);
            }
        }
        $storeProdCollection->getSelect()->group('mageproduct_id');
        $storeProductIDs = $storeProdCollection->getAllIds();

        $collection = $this->_productCollection->create()
            ->addAttributeToSelect('*');
        $collection->setVisibility($this->catalogVisibility->getVisibleInSiteIds());
        
        /**
         * Setting sort order according to BKWYADMIN-590
         */
        //$collection->addAttributeToSort('advance_order_intimation', 'ASC');
        //$collection->addAttributeToSort('price', 'ASC');
        $this->extensionAttributesJoinProcessor->process($collection);

        if ($categoryId !== null) {
            $collection->addCategoriesFilter(['eq' => $categoryId]);
        }

        foreach ($this->metadataService->getList($this->searchCriteriaBuilder->create())->getItems() as $metadata) {
            $collection->addAttributeToSelect($metadata->getAttributeCode());
        }
        $collection->joinAttribute('status', 'catalog_product/status', 'entity_id', null, 'inner');
        $collection->joinAttribute('visibility', 'catalog_product/visibility', 'entity_id', null, 'inner');

        /*****collection sort by product ordered qty start********/
        /*$collection->getSelect()->joinLeft(array('soi'=>'sales_order_item'), 'e.entity_id = soi.product_id', array('qty_ordered' => 'SUM(soi.qty_ordered)'));
        $collection->getSelect()->group('e.entity_id');
        $collection->getSelect()->order('qty_ordered DESC');*/
        $collection->addAttributeToSort('listing_position', 'DESC');
        $collection->addAttributeToSort('entity_id', 'DESC');
        /*****collection sort by product ordered qty end********/

        $globalStoreProductIdsFilter = true;
        if ($searchCriteria !== null) {
            //Add filters from root filter group to the collection
            foreach ($searchCriteria->getFilterGroups() as $group) {
                $this->addFilterGroupToCollection($group, $collection);
            }
            /** @var SortOrder $sortOrder */
            foreach ((array)$searchCriteria->getSortOrders() as $sortOrder) {
                $field = $sortOrder->getField();
                $collection->addOrder(
                    $field, ($sortOrder->getDirection() == SortOrder::SORT_ASC) ? 'ASC' : 'DESC'
                );
            }

            /**
             * adding custom price filter
             */
            if ($this->priceFilter === true && $this->priceFilterValue != '') {
                $collection->getSelect()->joinLeft(
                    ['cat_sync' => $collection->getTable('bakeway_catalog_sync')],
                    'e.entity_id=cat_sync.product_id',
                    ['price_incl_tax', 'catalog_discount_price_incl_tax']
                );
                $priceArr = explode("-", $this->priceFilterValue);
                if (isset($priceArr[0]) && isset($priceArr[1])) {
                    $collection->getSelect()->where('(cat_sync.price_incl_tax >= ' . $priceArr[0] . ' AND cat_sync.price_incl_tax <= ' . $priceArr[1] . ')');
                    //$collection->getSelect()->where('(cat_sync.catalog_discount_price_incl_tax >= '.$priceArr[0].' AND cat_sync.catalog_discount_price_incl_tax <= '.$priceArr[1].')');
                    $collection->getSelect()->group('e.entity_id');
                }
            }

            /**
             * adding custom offer filter
             */
            if ($this->offerFilter === true && $this->offerFilterValue == 1) {
                $collection->getSelect()->joinLeft(
                    ['bkw_cat_sync' => $collection->getTable('bakeway_catalog_sync')],
                    'e.entity_id=bkw_cat_sync.product_id',
                    ['catalog_discount_price_incl_tax', 'catalog_rule_name']
                );

                $collection->getSelect()->where('bkw_cat_sync.catalog_discount_price_incl_tax >= 0');
                $collection->getSelect()->where('bkw_cat_sync.catalog_rule_name IS NOT NULL');
                $collection->getSelect()->group('e.entity_id');
            }

            /**
             * Adding the prod attribute filter
             */
            if (isset($this->prodFilterArr) && is_array($this->prodFilterArr) && !empty($this->prodFilterArr)) {
                $filteredProdColl = $this->_productCollection->create();
                foreach ($this->prodFilterArr as $filter) {
                    $filteredProdColl->addFieldToFilter($filter);
                }

                $filteredProdColl->getSelect()->joinLeft(['link_table' => 'catalog_product_super_link'],
                    'link_table.product_id = e.entity_id',
                    ['product_id', 'parent_id']
                );
                $sqlQuery = $filteredProdColl->getSelect();
                $connection = $this->_resourceConnection->create()->getConnection();
                $prods = $connection->fetchAll($sqlQuery);
                $confProds = [];
                $filteredProdIds = [];
                if (is_array($prods) && !empty($prods)) {
                    $filteredProdIds = array_column($prods, 'entity_id');
                    $confProds = array_column($prods, 'parent_id');
                }
                $confProds = array_intersect($confProds, $storeProductIDs);
                $prodIds = array_merge(array_intersect($filteredProdIds, $storeProductIDs), $confProds);
                $collection->addFieldToFilter('entity_id', ['in' => array_unique($prodIds)]);
                $globalStoreProductIdsFilter = false;
            }
        }
        if ($globalStoreProductIdsFilter === true) {
            $collection->addFieldToFilter(
                'entity_id', ['in' => $storeProductIDs]
            );
        }
        $collection->getSelect()->reset(\Zend_Db_Select::LIMIT_COUNT);
        $collection->getSelect()->reset(\Zend_Db_Select::LIMIT_OFFSET);
        $result['page_size'] = $searchCriteria->getPageSize();
        $result['current_page'] = $searchCriteria->getCurrentPage();

        $offset = (($result['current_page'] - 1) * $result['page_size']);
        $collection->getSelect()->limit($result['page_size'], $offset);


        //echo $collection->getSelect();exit;
        $collection->load();
        $collection = $this->addBakewayUrlRewrite($collection);
        $filteredProdIds = $collection->getColumnValues('entity_id');

        /* Refining the result */
        $products = array();
        $result = array();

        $catalogSyncCollection = $this->catalogSyncCollection
            ->addFieldToFilter('product_id', ['in' => $filteredProdIds]);
        $syncedCatalog = [];
        foreach ($catalogSyncCollection as $catalogSync) {
            $productId = $catalogSync->getData('product_id');
            $syncedCatalog[$productId] = $catalogSync;
        }

        $businessNamesArr = [];
        $storeProdCollection->addFieldToFilter('mageproduct_id', ['in' => $filteredProdIds]);
        foreach ($storeProdCollection as $storeProd) {
            $sellerId = $storeProd->getData('seller_id');
            $mageProdId = $storeProd->getData('mageproduct_id');
            $businessNamesArr[$mageProdId]['business_name'] = $storeProd->getData('business_name');
            $businessNamesArr[$mageProdId]['is_conglomerate'] = $storeProd->getData('is_conglomerate');
            $businessNamesArr[$mageProdId]['store_unique_name'] = $storeDetailsArr[$sellerId]['unique_name'];
            $businessNamesArr[$mageProdId]['store_locality_area'] = $storeDetailsArr[$sellerId]['locality'];
            $businessNamesArr[$mageProdId]['seller_id'] = $sellerId;
        }

        $i = 0;
        $storeId = $this->storeManager->getStore()->getId();
        $this->appEmulation->startEnvironmentEmulation($storeId, \Magento\Framework\App\Area::AREA_FRONTEND, true);

        foreach ($collection as $product) {
            $products[$i]['name'] = $product->getName();
            $products[$i]['id'] = $product->getEntityId();
            $products[$i]['type_id'] = $product->getTypeId();
            $products[$i]['sku'] = $product->getSku();
            $products[$i]['price'] = $product->getPrice();
            $flavourLabel = $this->getAttributeLabel($product, 'cake_flavour');
            $ingredientLabel = $this->getAttributeLabel($product, 'cake_ingredients');
            $weightLabel = $this->getAttributeLabel($product, 'cake_weight');
            if (isset($syncedCatalog[$product->getEntityId()]) &&
                $syncedCatalog[$product->getEntityId()] != null
            ) {
                $syncedProduct = $syncedCatalog[$product->getEntityId()];
                $prodAttr = $this->catalogSyncHelper->getCatalogAttributes($syncedProduct, $product, $cityId);
            } else {
                $prodAttr = $this->catalogSyncHelper->getCatalogAttributes(null, $product, $cityId);
            }
            $catJson = $prodAttr['category_json'];
            $categoryJson = "";
            if (!empty($catJson)) {
                $categoryJson = $catJson;
            } else {
                $categoryJson = 'null';
            }
            /**
             * getting seller business name to display
             */
            $businessName = $businessNamesArr[$product->getEntityId()]['business_name'];
            $storeLocality = $businessNamesArr[$product->getEntityId()]['store_locality_area'];
            $isConglomerate = $businessNamesArr[$product->getEntityId()]['is_conglomerate'];
            $sellerId = $businessNamesArr[$product->getEntityId()]['seller_id'];
            if ($isConglomerate == 1) {
                $storeUniqueName = $businessNamesArr[$product->getEntityId()]['store_unique_name'];
            } else {
                $storeUniqueName = null;
            }

            $rulePrice = $prodAttr['rule_price'];
            $ruleTaxPrice = $prodAttr['rule_tax_price'];
            $discountRule = $prodAttr['discount_rule'];
//            $discountStartOn = $prodAttr['discount_rule_start_date'];
//            $discountEndsOn = $prodAttr['discount_rule_end_date'];
            $flavour = $prodAttr['flavour'];
            $weight = $prodAttr['weight'];
            $ingredient = $prodAttr['ingredient'];
            $intimationTime = $prodAttr['intimation_time'];
            $specialPrice = $prodAttr['special_price'];
            $priceInclTax = $prodAttr['price_incl_tax'];
            $priceExclTax = $prodAttr['price_excl_tax'];
            $advancedOrderIntimationunit = $prodAttr['advanced_order_intimation_unit'];
            $prodTypeId = $product->getTypeId();
            $ExtensionAttObject = $product->getExtensionAttributes();
            $products[$i]['extension_attributes'] = [
                "product_attributes" => [
                    ["attr_code" => "cake_flavour", "label" => $flavourLabel, "values" => [$flavour]],
                    ["attr_code" => "cake_ingredients", "label" => $ingredientLabel, "values" => [$ingredient]],
                    ["attr_code" => "cake_weight", "label" => $weightLabel, "values" => [$weight]],
                ],
                "special_price" => number_format($specialPrice, 2),
                "price_excl_tax" => number_format($priceExclTax, 2),
                "price_incl_tax" => number_format($priceInclTax, 2),
                "catalog_discount_price" => $rulePrice,
                "catalog_discount_price_incl_tax" => $ruleTaxPrice,
                "discount_rule" => $discountRule,
//                "discount_rule_start_date" => $discountStartOn,
//                "discount_rule_end_date" => $discountEndsOn,
                "description" => $product->getDescription(),
                "short_description" => $product->getShortDescription(),
                "seo_url" => $this->getProductSeoUrl($product->getData('request_path'), $product->getId()),
                "categories" => [$categoryJson],
                'media' => [
                    'small' => $this->getImageUrl($product, 'product_small_image'),
                    'large' => $this->getImageUrl($product, 'product_page_image_large'),
                    //'thumb' => $this->getImageUrl($product, 'product_thumbnail_image'),
                ],
                "advance_order_intimation" => $intimationTime,
                "advanced_order_intimation_unit" => $advancedOrderIntimationunit,
                "business_name" => $businessName,
                "store_locality_area" => $storeLocality,
                "store_unique_name" => $storeUniqueName,
                "seller_id" => $sellerId
            ];
            $i++;
        }

        $this->appEmulation->stopEnvironmentEmulation();

        $priceSortedProds = [];
//        foreach ($products as $key => $row) {
//            //$priceSortedProds[$key] = $row['extension_attributes']['advance_order_intimation'];
//            //$priceSortedProds['catalog_discount_price_incl_tax'][$key] = $row['extension_attributes']['catalog_discount_price_incl_tax'];
//            $priceSortedProds['price_incl_tax'][$key] = $row['extension_attributes']['price_incl_tax'];
//        }
//        if (is_array($priceSortedProds) && !empty($priceSortedProds)) {
//            //array_multisort($priceSortedProds['catalog_discount_price_incl_tax'], SORT_ASC,
//                //$priceSortedProds['price_incl_tax'], SORT_ASC, $products);
//            array_multisort($priceSortedProds['price_incl_tax'], SORT_ASC, $products);
//        }
        $result['products'] = $products;
        $result['current_page'] = $searchCriteria->getCurrentPage();
        $countSelect = clone $collection->getSelect();
        $group = $countSelect->getPart(\Zend_Db_Select::GROUP);
        $countSelect->reset(\Zend_Db_Select::ORDER);
        $countSelect->reset(\Zend_Db_Select::LIMIT_COUNT);
        $countSelect->reset(\Zend_Db_Select::LIMIT_OFFSET);
        $countSelect->reset(\Zend_Db_Select::COLUMNS);
        if (is_array($group) && isset($group[0])) {
            $countSelect->reset(\Zend_Db_Select::GROUP)->reset(\Zend_Db_Select::COLUMNS)->columns("COUNT(DISTINCT {$group[0]})");
        } else {
            $countSelect->reset(\Zend_Db_Select::GROUP)->reset(\Zend_Db_Select::COLUMNS)->columns("COUNT(DISTINCT e.entity_id)");
        }
        $countSelect->resetJoinLeft();
        $totalCount = $collection->getConnection()->fetchOne($countSelect);
        $result['total_count'] = $totalCount;

        return json_decode(json_encode($result, false));
    }

    /*
     * return attribute label name from attribute code
     */

    public function getAttributeLabel($product, $code)
    {
        $_AttData = $product->getResource()->getAttribute($code)->getStoreLabel();
        return $_AttData;
    }

    public function getProductSeoUrl($url, $productId)
    {
        if ($url !== null) {
            $sellerCity = $this->productapihelper->getSellerCity($productId);
            if ($sellerCity !== null) {
                $cityString = preg_replace('#[^0-9a-z]+#i', '-', strtolower($sellerCity));

                $pos = strpos($url, $cityString . "-");
                if ($pos !== false) {
                    $url = substr_replace($url, $cityString . "/", $pos, strlen($cityString . "-"));
                } else {
                    $url = str_replace($cityString . "-", $cityString . "/", $url);
                }
            }
        }
        return $url;
    }

    protected function addBakewayUrlRewrite($collection)
    {
        $productIds = [];
        foreach ($collection as $item) {
            $productIds[] = $item->getEntityId();
        }
        if (!$productIds) {
            return $collection;
        }

        $urlCollectionData = $this->objectManager
            ->create('Magento\UrlRewrite\Model\UrlRewrite')
            ->getCollection()
            ->addFieldToFilter('entity_type', 'bakeway-product')
            ->addFieldToFilter('entity_id', ['in' => $productIds]);

        // more priority is data with category id
        $urlRewrites = [];

        foreach ($urlCollectionData as $row) {
            if (!isset($urlRewrites[$row['entity_id']])) {
                $urlRewrites[$row['entity_id']] = $row['request_path'];
            }
        }

        foreach ($collection as $item) {
            if (isset($urlRewrites[$item->getEntityId()])) {
                $item->setData('request_path', $urlRewrites[$item->getEntityId()]);
            } else {
                $item->setData('request_path', false);
            }
        }

        return $collection;
    }
}
