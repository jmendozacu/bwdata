<?php

namespace Bakeway\ProductApi\Helper;

use Braintree\Exception;
use Magento\Framework\App\ObjectManager;
use Magento\Catalog\Model\Config;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Catalog\Api\Data\ProductCustomOptionInterface;
use Bakeway\Cities\Helper\Data as CitiesHelper;
use Magento\Eav\Model\ResourceModel\Entity\Attribute as EavAttribute;
use Webkul\Marketplace\Helper\Data as MarketplaceHelper;
use \Magento\CatalogRule\Model\ResourceModel\RuleFactory;
use Magento\CatalogRule\Model\Rule;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Webkul\Marketplace\Model\Product as VendorProduct;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Catalog\Model\Product\Visibility as CatalogVisibility;
use Magento\Catalog\Model\ResourceModel\CategoryProduct;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Bakeway\PartnerWebapi\Helper\Data as WebapiHelper;

class Data extends \Magento\Framework\App\Helper\AbstractHelper {

    const PRODUCT_ENABLE_STATUS = 1;
    const URL_KEY_ATTRIBUTE_CODE = 'url_key';
    const PRODUCT_VARCHAR_ENTITY_TABLE = 'catalog_product_entity_varchar';
    const PRODUCT_OPTION_TABLE = 'catalog_product_option';
    const PRODUCT_OPTION_TITLE_TABLE = 'catalog_product_option_title';
    const CURRENT_STORE_ID = 1;
    const STORE_ID = 1;
    /**
     * Store manager.
     *
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var  \Magento\Customer\Model\CustomerFactory
     */
    protected $_sellerFactory;

    /**
     * @var \Webkul\Marketplace\Model\ProductFactory
     */
    protected $productFactory;
    protected $vendorFactory;

    /**
     * @var \Magento\ConfigurableProduct\Model\Product\Type\Configurable
     */
    protected $_configurableobj;

    /**
     * @var Config
     */
    private $catalogConfig;

    /**
     * @param productRepository
     */
    protected $productRepository;

    /**
     * @var \Magento\Catalog\Helper\Data
     */
    protected $_catalogHelper;

    /**
     * @var \Magento\Customer\Model\AddressFactory
     */
    protected $_addressFactory;

    /**
     * @var \Bakeway\HomeDeliveryshipping\Helper\Data
     */
    protected $_homedeliveryHelper;

    /**
     * @var $categoryModel
     */
    protected $_categoryModel;

    /**
     * @var $categoryfactory
     */
    protected $_categoryfactory;

    /**
     * @var CategoryRepositoryInterface
     */
    protected $categoryRepository;

    /**
     * @var ProductCustomOptionInterface
     */
    protected $customOptionRepository;

    /**
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var CitiesHelper
     */
    protected $citiesHelper;

    /**
     * @var MarketplaceHelper
     */
    protected $marketplaceHelper;

    /**
     * @var EavAttribute
     */
    protected $eavAttribute;

    /**
     * @var RuleFactory
     */
    protected $resourceRuleFactory;

    /**
     * @var GroupManagementInterface
     */
    protected $groupManagement;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $localeDate;

    /**
     * @var VendorProduct
     */
    protected $vendorProduct;

    /**
     * @var ProductCollectionFactory
     */
    protected $productCollection;

    /**
     * @var CatalogVisibility
     */
    protected $catalogVisibility;

    /**
     * @var CategoryProduct
     */
    protected $categoryProducts;

    protected $categoryCollection;

    /**
     * Data constructor.
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Customer\Model\CustomerFactory $sellerFactory
     * @param \Webkul\Marketplace\Model\SellerFactory $vendorFactory
     * @param \Webkul\Marketplace\Model\ProductFactory $productFactory
     * @param \Magento\ConfigurableProduct\Model\Product\Type\Configurable $_configurableobj
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param \Magento\Catalog\Helper\Data $catalogData
     * @param \Magento\Customer\Model\AddressFactory $addressFactory
     * @param \Bakeway\HomeDeliveryshipping\Helper\Data $homedeliveryHelper
     * @param \Magento\Catalog\Model\CategoryRepository $categoryModel
     * @param \Magento\Catalog\Model\CategoryFactory $categoryfactory
     * @param CategoryRepositoryInterface $categoryRepository
     * @param ProductCustomOptionInterface $customOptionRepository
     * @param ObjectManagerInterface $objectManager
     * @param CitiesHelper $citiesHelper
     * @param EavAttribute $eavAttribute
     * @param MarketplaceHelper $marketplaceHelper
     * @param StoreManagerInterface $storeManager
     * @param RuleFactory $resourceRuleFactory
     * @param TimezoneInterface $localeDate
     * @param VendorProduct $vendorProduct
     * @param ProductCollectionFactory $productCollection
     * @param CatalogVisibility $catalogVisibility
     * @param CategoryProduct $categoryProducts
     * @param CategoryCollectionFactory $categoryCollection
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Customer\Model\CustomerFactory $sellerFactory,
        \Webkul\Marketplace\Model\SellerFactory $vendorFactory,
        \Webkul\Marketplace\Model\ProductFactory $productFactory,
        \Magento\ConfigurableProduct\Model\Product\Type\Configurable $_configurableobj,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Catalog\Helper\Data $catalogData,
        \Magento\Customer\Model\AddressFactory $addressFactory,
        \Bakeway\HomeDeliveryshipping\Helper\Data $homedeliveryHelper,
        \Magento\Catalog\Model\CategoryRepository $categoryModel,
        \Magento\Catalog\Model\CategoryFactory $categoryfactory,
        CategoryRepositoryInterface $categoryRepository,
        ProductCustomOptionInterface $customOptionRepository,
        ObjectManagerInterface $objectManager,
        CitiesHelper $citiesHelper,
        EavAttribute $eavAttribute,
        MarketplaceHelper $marketplaceHelper,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        RuleFactory $resourceRuleFactory,
        TimezoneInterface $localeDate,
        VendorProduct $vendorProduct,
        ProductCollectionFactory $productCollection,
        CatalogVisibility $catalogVisibility,
        CategoryProduct $categoryProducts,
        CategoryCollectionFactory $categoryCollection
    ) {
        parent::__construct($context);
        $this->_sellerFactory = $sellerFactory;
        $this->vendorFactory = $vendorFactory;
        $this->productFactory = $productFactory;
        $this->_configurableobj = $_configurableobj;
        $this->productRepository = $productRepository;
        $this->_catalogHelper = $catalogData;
        $this->_addressFactory = $addressFactory;
        $this->_homedeliveryHelper = $homedeliveryHelper;
        $this->_categoryModel = $categoryModel;
        $this->_categoryfactory = $categoryfactory;
        $this->categoryRepository = $categoryRepository;
        $this->customOptionRepository = $customOptionRepository;
        $this->objectManager = $objectManager;
        $this->citiesHelper = $citiesHelper;
        $this->eavAttribute = $eavAttribute;
        $this->marketplaceHelper = $marketplaceHelper;
        $this->resourceRuleFactory = $resourceRuleFactory;
        $this->localeDate = $localeDate;
        $this->storeManager = $storeManager;
        $this->vendorProduct = $vendorProduct;
        $this->productCollection = $productCollection;
        $this->catalogVisibility = $catalogVisibility;
        $this->categoryProducts = $categoryProducts;
        $this->categoryCollection = $categoryCollection;
    }

    /*
     * get vendor name from product id
     */

    public function getSellername($prodid) {
        $_collection = $this->productFactory->create()->getCollection();
        $_collection->getSelect()->joinLeft(['mp_userdata' => $_collection->getTable('marketplace_userdata')], 'main_table.seller_id = mp_userdata.seller_id', ['business_name'])
                ->where('main_table.mageproduct_id=' . $prodid);

        if (!empty($_collection->getSize())) {
            return $_collection->getFirstItem()->getData('business_name');
        } else {
            return NULL;
        }
    }

    /*
     * get delivery method on or off of vendor from product id
     */

    public function getSellerDeliveryStatus($prodid) {
        $_collection = $this->productFactory->create()->getCollection();
        $_collection->getSelect()->joinLeft(['mp_userdata' => $_collection->getTable('marketplace_userdata')], 'main_table.seller_id = mp_userdata.seller_id', ['delivery'])
                ->where('main_table.mageproduct_id=' . $prodid);
        if (!empty($_collection->getSize())) {
            return $_collection->getFirstItem()->getData('delivery');
        } else {
            return NULL;
        }
    }

    /*
     * get all child product skus
     */

    public function getChildrenSkus($product) {
        $_Sku = array();
        $_ChildProducts = $this->_configurableobj->getUsedProductCollection($product);
        $_ChildProducts
                ->setFlag('has_stock_status_filter', true)
                ->addAttributeToSelect($this->getCatalogConfig()->getProductAttributes())
                ->addFilterByRequiredOptions()
                ->setStoreId($product->getStoreId());
        foreach ($_ChildProducts as $simple_product) {
            $_Sku[] = $simple_product->getSku();
        }
        return $_Sku;
    }

    /**
     * Get Config instance
     * @return Config
     * @deprecated
     */
    private function getCatalogConfig() {
        if (!$this->catalogConfig) {
            $this->catalogConfig = ObjectManager::getInstance()->get(Config::class);
        }

        return $this->catalogConfig;
    }

    /*
     * filtering sku for options
     * return avaiable child sku 
     */

    public function getOptionsSkus($value_index, $_ChildSkus, $product) {

        $_Sku = $_MainSku = array();
        $_caleflv = $_caleIng = $_caleWeight = [];
        $_ChildProducts = $this->_configurableobj->getUsedProductCollection($product);

        $_ChildProducts
                ->setFlag('has_stock_status_filter', true)
                ->addAttributeToSelect($this->getCatalogConfig()->getProductAttributes())
                ->addFilterByRequiredOptions()
                ->addAttributeToFilter('status', self::PRODUCT_ENABLE_STATUS)
                ->setStoreId($product->getStoreId());
        foreach ($_ChildProducts as $simple_product) {
            $_caleflv[$simple_product->getSku()] = $simple_product->getCakeFlavour();
            $_caleIng[$simple_product->getSku()] = $simple_product->getCakeIngredients();
            $_caleWeight[$simple_product->getSku()] = $simple_product->getCakeWeight();
            $_SKus[] = $simple_product->getSku();
        }
        if (in_array($value_index, $_caleflv)) {
            if (in_array($value_index, $_caleflv)) {
                foreach ($_caleflv as $key => $_caleflv1) {
                    if ($_caleflv1 == $value_index) {
                        $_MainSku[] = $key;
                    }
                }
            }
        }

        if (in_array($value_index, $_caleIng)) {
            if (in_array($value_index, $_caleIng)) {
                foreach ($_caleIng as $key => $_caleIng1) {
                    if ($_caleIng1 == $value_index) {
                        $_MainSku[] = $key;
                    }
                }
            }
        }

        if (in_array($value_index, $_caleWeight)) {
            if (in_array($value_index, $_caleWeight)) {
                foreach ($_caleWeight as $key => $_caleWeight1) {
                    if ($_caleWeight1 == $value_index) {
                        $_MainSku[] = $key;
                    }
                }
            }
        }

        return $_MainSku;
    }

    /*
     * child sku
     * simple, special price
     * price excl tax
     */

    public function getConfigurableProperties($product) {
        $_Sku = array();
        $_ChildProducts = $this->_configurableobj->getUsedProductCollection($product);
        $_ChildProducts
                ->setFlag('has_stock_status_filter', true)
                ->addAttributeToSelect($this->getCatalogConfig()->getProductAttributes())
                ->addFilterByRequiredOptions()
                ->addAttributeToFilter('status', self::PRODUCT_ENABLE_STATUS)
                ->setStoreId($product->getStoreId());
        foreach ($_ChildProducts as $simple_product) {

            $priceExTax = number_format($simple_product->getPrice(), 2);
            /* check if product has special price */
            if ($simple_product->getSpecialPrice()):
                $priceExTax = number_format($simple_product->getSpecialPrice(), 2);
            endif;
            $priceIncTax = number_format($this->getProductTaxPrice($simple_product), 2);

            $rulePrice = $this->getCatalogRulePrice($simple_product->getId());
            $ruleTaxPrice = $this->getCatalogRuleTaxPrice($simple_product, $rulePrice);
            if (!($rulePrice && $ruleTaxPrice)) {
                $rulePrice = 0;
                $ruleTaxPrice = 0;
            }

            $ruleData = $this->getRuleDataByProduct($simple_product->getId());

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


            $_Sku[$simple_product->getSku()] = array(
                "price" => number_format($simple_product->getPrice(), 2),
                "special_price" => number_format($simple_product->getSpecialPrice(), 2),
                "price_excl_tax" => $priceExTax,
                "price_incl_tax" => $priceIncTax,
                "catalog_discount_price" => $rulePrice,
                "catalog_discount_price_incl_tax" =>$ruleTaxPrice,
                "advance_order_intimation" => $simple_product->getData('advance_order_intimation'),
                "advanced_order_intimation_unit" => $simple_product->getData('advance_order_intimation_unit'),
                "discount_rule_start_date" => $discountRuleStartDate,
                "discount_rule_end_date" => $discountRuleEndDate
            );
        }
        return $_Sku;
    }

    /**
     * calclating price with including tax
     * @param $product
     * @param null $sellerId
     * @param null $sellerDetails
     * @param bool $isRulePrice
     * @param null $rulePrice
     * @param null $cityId
     * @return float|null|void
     */
    public function getProductTaxPrice($product, $sellerId=null, $sellerDetails = null, $isRulePrice=false, $rulePrice=null, $cityId = null) {
        $_CatalogHelper = $this->_catalogHelper;
        $_Price = $product->getPrice();

        /** check if product has special price* */
        if ($product->getSpecialPrice()):
            $_Price = $product->getSpecialPrice();
        endif;

        if ($isRulePrice === true && $rulePrice !== null) {
            $_Price = $rulePrice;
        }
        /** get seller region and postcode if cityId not passed **/
        if ($cityId === null){
            if($sellerId === null && $sellerDetails === null) {
                $_SellerId = $this->_homedeliveryHelper->getSellerid($product->getId());
                $_SellerInfo = $this->_homedeliveryHelper->getSellerDetails($_SellerId);
            } else {
                $_SellerId = $sellerId;
                $_SellerInfo = $sellerDetails;
            }
        }

        $_Zip = $_Country = $_City = $_State = "";
        if (!empty($_SellerInfo)):
            $_Zip = $_SellerInfo->getData('store_zipcode');
            $_Country = $_SellerInfo->getData('country_pic');
            $_City = $_SellerInfo->getData('store_city');
            $_State = $_SellerInfo->getData('state');
        endif;

        if ($cityId !== null) {
            $_City = $cityId;
            $_Zip = '000000';
            $_Country = 'IN';
        }
        $address = $this->_addressFactory->create();
        $address->setCountryId($_Country)
                ->setFirstname("FirstName")
                ->setLastname("Lastname")
                ->setTelephone('1234567890')
                ->setPostcode($_Zip)
                ->setCity($_City)
                ->setStreet('Pune');
        $_Price = $_CatalogHelper->getTaxPrice($product, $_Price, true, $address);
        
        if (!empty($_Price)):
            return $_Price;
        else:
            return;
        endif;
    }

    /*
     * return category name from category id
     */

    public function getCategoryName($_Ids) {
        $_Model = $this->_categoryModel;
        $_ReturnData = [];
        $_PcakeFlag = "";
        if (!empty($_Ids)):
            foreach ($_Ids as $id) {
                $_getCategoryData = $_Model->get($id);
                $_Name = $_getCategoryData->getName();
                $catname = strtolower($_Name);
                if (strpos($catname, 'photo') !== false):
                    $_ReturnData[] = array("id" => $id, "label" => $_Name, "upload_media" => true);
                else:
                    $_ReturnData[] = array("id" => $id, "label" => $_Name);
                endif;
            }
            return $_ReturnData;
        else:
            return;
        endif;
    }

    /*
     * return main cake category id and add on category id
     */

    public function getMainandAddoncategory() {

        $_CatArray = [];
        $_catname = "";
        $_Collection = $this->_categoryfactory->create()->getCollection()
                ->addAttributeToFilter(array(
            array('attribute' => 'name', 'eq' => 'Cakes'),
            array('attribute' => 'name', 'eq' => 'Add ons'),
            array('attribute' => 'name', 'like' => '%photo%')
        ));
        
        foreach ($_Collection as $category):
            if ($category->getName() == 'Cakes'):
                $_catname = "cakes";
            elseif(strpos(strtolower($category->getName()), 'photo') !== false):
                $_catname = "photocake";
            else :
                $_catname = "addons";
            endif;
            $_CatArray[$_catname] = array("id" => $category->getId(), "label" => $category->getName(), "upload_media" => true);
        endforeach;
        return !empty($_CatArray) ? $_CatArray : "";
    }

    public function getSellerCity($productId) {
        $_collection = $this->productFactory->create()->getCollection();
        $_collection->getSelect()->joinLeft(['mp_userdata' => $_collection->getTable('marketplace_userdata')], 'main_table.seller_id = mp_userdata.seller_id', ['store_city'])
                ->where('main_table.mageproduct_id=' . $productId);

        if (!empty($_collection->getSize())) {
            $cityId = $_collection->getFirstItem()->getData('store_city');
            if ($cityId) {
                $city = $this->citiesHelper->getCityNameById($cityId);
                return $city;
            } else {
                return null;
            }
        }
        return null;
    }

    public function getSellerLocalityArea($productId) {
        $_collection = $this->productFactory->create()->getCollection();
        $_collection->getSelect()->joinLeft(['bpl' => $_collection->getTable('bakeway_partner_locations')], 'main_table.seller_id = bpl.seller_id', ['store_locality_area'])
                        ->where('main_table.mageproduct_id=' . $productId);
        $_collection->getSelect()->group('main_table.mageproduct_id');
        if (!empty($_collection->getSize())) {
            $locality = $_collection->getFirstItem()->getData('store_locality_area');
            if ($locality) {
                return $locality;
            } else {
                return null;
            }
        }
        return null;
    }

    /**
     * @param int $productId
     * @return void
     */
    public function createProductCustomOptions($productId) {
        if ($productId !== null || $productId != '') {
            $product = $this->objectManager->create('Magento\Catalog\Model\Product')
                    ->load($productId);
            $isCustomOption = $this->checkProductCategories($product);

            /**
             * Delete the product options if any
             */
            if ($product->getOptions() != '') {
                foreach ($product->getOptions() as $opt) {
                    $opt->delete();
                }
                $product->setHasOptions(0)->save();
            }

            if ($isCustomOption === true) {
                /**
                 * Create product option of file type.
                 */
                $customOption = $this->customOptionRepository;
                $customOption->setTitle('Photo Url')
                        ->setType('file')
                        ->setIsRequire(true)
                        ->setSortOrder(1)
                        ->setPriceType('fixed')
                        ->setProductSku($product->getSku());
                $customOptions[] = $customOption;
                $this->objectManager->create('Magento\Catalog\Model\Product')
                        ->load($productId)
                        ->setOptions($customOptions)
                        ->setHasOptions(1)
                        ->save();
                return;
            }
        }
        return;
    }

    /**
     * @param \Magento\Catalog\Api\Data\ProductInterface $product
     * @return bool
     */
    public function checkProductCategories($product) {
        $categories = $product->getCategoryIds();
        foreach ($categories as $category) {
            $cat = $this->categoryRepository->get($category)->getName();
            if (strpos(strtolower($cat), 'photo') !== false) {
                return true;
            }
        }
        return false;
    }

    /*
     * set 2 decimal for product price.
     * @param $_Price
     */

    public function setDecimalPrice($_Price) {
        if ($_Price) {
            return number_format($_Price, 2);
        } else {
            return;
        }
    }

    /**
     * @param $prodid
     * @return null
     */
    public function getSellerByProductId($prodid) {
        $collection = $this->productFactory->create()->getCollection();
        $collection->getSelect()->joinLeft(['mp_userdata' => $collection->getTable('marketplace_userdata')], 'main_table.seller_id = mp_userdata.seller_id', ['*'])
            ->where('main_table.mageproduct_id=' . $prodid);

        if (!empty($collection->getSize())) {
            return $collection->getFirstItem();
        }
        return null;
    }

    /**
     * function to create product SEO urls
     * @param int $productId
     * @return string
     */
    public function createProductUrl($productId,$locality= null) {
        $sellerName = $this->getSellername($productId);

        /*         * * Removing default magento urls ** */
        $this->removeDefaultUrlRewrites($productId);

        $seller = $this->getSellerByProductId($productId);

        if ($seller !== null) {
            $isConglomerate = $seller->getData('is_conglomerate');
        } else {
            $isConglomerate = 0;
        }

        /*         * * creating bakeway product seo url ** */
        $product = $this->objectManager->create('Magento\Catalog\Model\Product')
                ->load($productId);
        $productName = $product->getName();
        $sellerCity = $this->getSellerCity($productId);

        if($locality === null) {
            $locality = $this->getSellerLocalityArea($productId);
         }

        $initialCounter = 0;
        $url = $this->getProcessedProductUrl($sellerName, $productName, $sellerCity, $locality, $initialCounter, $isConglomerate);
        $counterIncrement = $this->checkProductUrlForDuplicates($productId, $url, $initialCounter);
        if ($counterIncrement !== 0) {
            while ($counterIncrement !== $initialCounter) {
                $initialCounter++;
                $url = $this->getProcessedProductUrl($sellerName, $productName, $sellerCity, $locality, $counterIncrement, $isConglomerate);
                $counterIncrement = $this->checkProductUrlForDuplicates($productId, $url, $initialCounter);
            }
            $url = $this->getProcessedProductUrl($sellerName, $productName, $sellerCity, $locality, $counterIncrement, $isConglomerate);
        }
        /*         * * adding bakeway product seo url to url_rewrite ** */
        $urlId = '';
        $collectionRequestUrl = '';
        $urlCollectionData = $this->objectManager
                ->create('Magento\UrlRewrite\Model\UrlRewrite')
                ->getCollection()
                ->addFieldToFilter('request_path', strtolower($url))
                ->addFieldToFilter('target_path', strtolower($url));
        foreach ($urlCollectionData as $value) {
            $urlId = $value->getId();
            $collectionRequestUrl = $value->getRequestPath();
        }
        if ($collectionRequestUrl != $url) {
            $idPath = rand(1, 100000);
            $this->objectManager->create('Magento\UrlRewrite\Model\UrlRewrite')
                    ->load($urlId)
                    ->setIsSystem(0)
                    ->setEntityType('bakeway-product')
                    ->setEntityId($productId)
                    ->setIdPath($idPath)
                    ->setTargetPath(strtolower($url))
                    ->setRequestPath(strtolower($url))
                    ->save();
        }
        return $url;
    }

    /**
     * Function to remove default magento's url rewrites
     * @param int $productId
     * @return void
     */
    public function removeDefaultUrlRewrites($productId) {
        $attributeId = $this->eavAttribute->getIdByCode('catalog_product', self::URL_KEY_ATTRIBUTE_CODE);
        $urlCollectionData = $this->objectManager
                ->create('Magento\UrlRewrite\Model\UrlRewrite')
                ->getCollection()
                ->addFieldToFilter('entity_type', ['in'=>['product','bakeway-product']])
                ->addFieldToFilter('entity_id', $productId);
        foreach ($urlCollectionData as $value) {
            $value->delete();
        }

        $resource = $this->objectManager
                ->create('Magento\Framework\App\ResourceConnection');
        $connection = $resource->getConnection();
        $tableName = $resource->getTableName(self::PRODUCT_VARCHAR_ENTITY_TABLE);
        $sql = "DELETE FROM " . $tableName . " WHERE attribute_id = " . $attributeId . " AND entity_id = " . $productId;
        $connection->query($sql);

        return;
    }

    /**
     * function to create seller SEO urls
     * @param int $sellerId
     * @return string
     */
    public function createVendorUrl($sellerId,$locality = null) {
        $seller = $this->marketplaceHelper->getSellerDataBySellerId($sellerId)->getFirstItem();

        /*         * * Removing seller old urls ** */
        $this->removeSellerOldUrlRewrites($sellerId);

        /*         * * creating bakeway seller seo url ** */
        $cityId = $seller->getData('store_city');
        $sellerCity = $this->citiesHelper->getCityNameById($cityId);
        if($locality === null) {
            $locality = $seller->getData('store_locality_area');
        }
        $sellerName = $seller->getData('business_name');
        $isConglomerate = $seller->getData('is_conglomerate');

        $initialCounter = 0;
        $url = $this->getProcessedVendorUrl($sellerName, $locality, $sellerCity, $initialCounter, $isConglomerate);
        $counterIncrement = $this->checkVendorUrlForDuplicates($sellerId, $url, $initialCounter);
        if ($counterIncrement !== 0) {
            while ($counterIncrement !== $initialCounter) {
                $initialCounter++;
                $url = $this->getProcessedVendorUrl($sellerName, $locality, $sellerCity, $counterIncrement, $isConglomerate);
                $counterIncrement = $this->checkVendorUrlForDuplicates($sellerId, $url, $initialCounter);
            }
            $url = $this->getProcessedVendorUrl($sellerName, $locality, $sellerCity, $counterIncrement, $isConglomerate);
        }
        if ($url) {
            /*             * * adding bakeway seller seo url to url_rewrite ** */
            $idPath = rand(1, 100000);
            $this->objectManager->create('Magento\UrlRewrite\Model\UrlRewrite')
                    ->setIsSystem(0)
                    ->setEntityType('customer')
                    ->setEntityId($sellerId)
                    ->setIdPath($idPath)
                    ->setTargetPath(strtolower($url))
                    ->setRequestPath(strtolower($url))
                    ->save();
        }
        return $url;
    }

    /**
     * Function to remove old url rewrites for seller
     * @param int $sellerId
     * @return void
     */
    public function removeSellerOldUrlRewrites($sellerId) {
        $urlCollectionData = $this->objectManager
                ->create('Magento\UrlRewrite\Model\UrlRewrite')
                ->getCollection()
                ->addFieldToFilter('entity_type', 'customer')
                ->addFieldToFilter('entity_id', $sellerId);
        foreach ($urlCollectionData as $value) {
            $value->delete();
        }
        return;
    }

    /**
     * Check if url is already exist with another user
     * Return the counter increment if it exist
     * @param int $sellerId
     * @param string $url
     * @param int $initialCounter
     * @return int
     */
    public function checkVendorUrlForDuplicates($sellerId, $url, $initialCounter) {
        $urlCollectionData = $this->objectManager
                ->create('Magento\UrlRewrite\Model\UrlRewrite')
                ->getCollection()
                ->addFieldToFilter('entity_type', 'customer')
                ->addFieldToFilter('request_path', $url)
                ->addFieldToFilter('target_path', $url);
        $counterIncrement = $initialCounter;
        foreach ($urlCollectionData as $value) {
            $entityId = $value->getEntityId();
            if ($entityId != $sellerId) {
                $counterIncrement++;
            }
        }
        return $counterIncrement;
    }

    public function getProcessedVendorUrl($sellerName, $locality, $sellerCity, $counterIncrement = 0, $isConglomerate) {
        if ($counterIncrement == 0) {
            if ($isConglomerate == 0) {
                $urlString = preg_replace('#[^0-9a-z]+#i', '-', $sellerName . " " . $locality);
            } else {
                $urlString = preg_replace('#[^0-9a-z]+#i', '-', $sellerName);
            }
        } else {
            if ($isConglomerate == 0) {
                $urlString = preg_replace('#[^0-9a-z]+#i', '-', $sellerName . " " . $counterIncrement . " " . $locality);
            } else {
                $urlString = preg_replace('#[^0-9a-z]+#i', '-', $sellerName . " " . $counterIncrement);
            }
        }
        $url = $sellerCity . "-" . $urlString;
        $url = strtolower(trim($url, "-"));
        return $url;
    }

    public function getProcessedProductUrl($sellerName, $productName, $sellerCity, $locality, $counterIncrement = 0, $isConglomerate) {
        if ($counterIncrement == 0) {
            if ($isConglomerate == 0) {
                $urlString = preg_replace('#[^0-9a-z]+#i', '-', $productName . " " . $sellerName . " " . $locality);
            } else {
                $urlString = preg_replace('#[^0-9a-z]+#i', '-', $productName . " " . $sellerName);
            }
        } else {
            if ($isConglomerate == 0) {
                $urlString = preg_replace('#[^0-9a-z]+#i', '-', $productName . " " . $sellerName . " " . $counterIncrement . " " . $locality);
            } else {
                $urlString = preg_replace('#[^0-9a-z]+#i', '-', $productName . " " . $sellerName . " " . $counterIncrement);
            }
        }
        $url = $sellerCity . "-" . $urlString;
        $url = strtolower(trim($url, "-"));
        return $url;
    }

    /**
     * Check if url is already exist with another product
     * Return the counter increment if it exist
     * @param int $productId
     * @param string $url
     * @param int $initialCounter
     * @return int
     */
    public function checkProductUrlForDuplicates($productId, $url, $initialCounter) {
        $urlCollectionData = $this->objectManager
                ->create('Magento\UrlRewrite\Model\UrlRewrite')
                ->getCollection()
                ->addFieldToFilter('entity_type', 'bakeway-product')
                ->addFieldToFilter('request_path', $url)
                ->addFieldToFilter('target_path', $url);
        $counterIncrement = $initialCounter;
        foreach ($urlCollectionData as $value) {
            $entityId = $value->getEntityId();
            if ($entityId != $productId) {
                $counterIncrement++;
            }
        }
        return $counterIncrement;
    }

    /**
     * @param int $sellerId
     * @return void
     */
    public function createSellerAllProductUrls($sellerId,$locality= null) {
        $collection = $this->productFactory->create()
                ->getCollection()
                ->addFieldToSelect('mageproduct_id')
                ->addFieldToFilter('seller_id', $sellerId);
        foreach ($collection as $marketplaceProduct) {
            $product = $this->objectManager
                    ->create('Magento\Catalog\Model\Product')
                    ->load($marketplaceProduct->getData('mageproduct_id'));
            /**
             * Setting the product url
             */
            $urlKey = $this->createProductUrl($marketplaceProduct->getData('mageproduct_id'),$locality);
            $product->setUrlKey($urlKey);
            $product->save();
        }
        return;
    }

    /**
     * Get Seller SEO Url
     * @param int $sellerId
     * @return null|string
     */
    public function getSellerSeoUrl($sellerId) {
        $url = null;
        $urlCollectionData = $this->objectManager
                ->create('Magento\UrlRewrite\Model\UrlRewrite')
                ->getCollection()
                ->addFieldToFilter('entity_type', 'customer')
                ->addFieldToFilter('entity_id', $sellerId);
        foreach ($urlCollectionData as $collection) {
            $url = $collection->getData('request_path');
        }
        if ($url !== null) {
            $seller = $this->marketplaceHelper->getSellerDataBySellerId($sellerId)->getFirstItem();
            $cityId = $seller->getData('store_city');
            $sellerCity = $this->citiesHelper->getCityNameById($cityId);
            $cityString = preg_replace('#[^0-9a-z]+#i', '-', strtolower($sellerCity));
            $url = str_replace($cityString . "-", $cityString . "/", $url);
        }
        return $url;
    }

    /**
     * Get Product SEO Url
     * @param int $productId
     * @return null|string
     */
    public function getProductSeoUrl($productId) {
        $url = null;
        $urlCollectionData = $this->objectManager
                ->create('Magento\UrlRewrite\Model\UrlRewrite')
                ->getCollection()
                ->addFieldToFilter('entity_type', 'bakeway-product')
                ->addFieldToFilter('entity_id', $productId);
        foreach ($urlCollectionData as $collection) {
            $url = $collection->getData('request_path');
        }
        if ($url !== null) {
            $sellerCity = $this->getSellerCity($productId);
            if ($sellerCity !== null) {
                $cityString = preg_replace('#[^0-9a-z]+#i', '-', strtolower($sellerCity));

                $url = str_replace($cityString . "-", $cityString . "/", $url);
            }
        }
        return $url;
    }

    /**
     * return min product price
     * @param $product
     * @param null $sellerId
     * @param null $sellerDetails
     * @param null $cityId
     * @return array|void
     */
    public function getMinproductPrice($product, $sellerId = null, $sellerDetails=null, $cityId = null) {
        $_price = [];
        $simpleProdObjArr = [];
        $_ChildProducts = $this->_configurableobj->getUsedProductCollection($product);
        $_ChildProducts
                ->setFlag('has_stock_status_filter', true)
                ->addAttributeToSelect($this->getCatalogConfig()->getProductAttributes())
                ->addFilterByRequiredOptions()
                ->addAttributeToFilter('status', self::PRODUCT_ENABLE_STATUS)
                ->setStoreId($product->getStoreId());
        foreach ($_ChildProducts as $simple_product) {
            /* check if product has special price */
            if ($simple_product->getSpecialPrice()):
                $_price[$simple_product->getId()] = $simple_product->getSpecialPrice();
            else:
                $_price[$simple_product->getId()] = $simple_product->getPrice();
            endif;
            $taxInclPrice[$simple_product->getId()] = $this->getProductTaxPrice($simple_product, $sellerId, $sellerDetails, false, null, $cityId);
            $simpleProdObjArr[$simple_product->getId()] = $simple_product;
        }
 
        if (!empty($_price)):
            $simpleProdId = array_search(min($_price), $_price);
            $data = array(
                "product_id" => array_search(min($_price), $_price),
                "min_price" => min($_price),
                "tax_incl_price" =>$taxInclPrice[$simpleProdId],
                "simple_prod_obj" => $simpleProdObjArr[$simpleProdId]
            );
            return $data;
        else:
            return;
        endif;
    }

    /*
     * calclating min price with including tax
     * @param int $productId
     */

    public function getMinProductTaxPrice($productId) {

        $product = $this->objectManager->create('Magento\Catalog\Model\Product')
                ->load($productId);

        $_incltaxPrice = $this->getProductTaxPrice($product);
        return $_incltaxPrice;
    }

    public function getCatalogRuleTaxPrice($product, $rulePrice) {
        $price = $this->getProductTaxPrice($product, null, null, true, $rulePrice);
        return $price;
    }

    public function getCatalogRulePrice($productId) {

        $store = $this->storeManager->getStore(self::CURRENT_STORE_ID);
        $websiteId = $store->getWebsiteId();
        $date = (new \DateTime())->setTimestamp($this->localeDate->scopeTimeStamp($store));
        $groupId = 0;
        $rulePrice = $this->resourceRuleFactory->create()->getRulePrice(
            $date,
            $websiteId,
            $groupId,
            $productId
        );
        return $rulePrice;
    }

    public function getRuleDataByProduct($productId) {

        $store = $this->storeManager->getStore(self::CURRENT_STORE_ID);
        $websiteId = $store->getWebsiteId();
        $date = (new \DateTime())->setTimestamp($this->localeDate->scopeTimeStamp($store));
        $groupId = 0;
        $ruleData = $this->resourceRuleFactory->create()->getRulesFromProduct(
            $date->format('Y-m-d H:i:s'),
            $websiteId,
            $groupId,
            $productId
        );
        if (is_array($ruleData) && isset($ruleData[0]['rule_id'])) {
            $catalogRule = $this->objectManager->create('\Magento\CatalogRule\Model\Rule')
                ->load($ruleData[0]['rule_id']);
            return $catalogRule->getData();
        }
        return $ruleData;
    }

    public function getSellerUrlListArray($sellerIdsArr, $cityId) {
        $urlArray = [];
        $urlCollectionData = $this->objectManager
            ->create('Magento\UrlRewrite\Model\UrlRewrite')
            ->getCollection()
            ->addFieldToFilter('entity_type', 'customer')
            ->addFieldToFilter('entity_id', ['in'=>$sellerIdsArr]);
        foreach ($urlCollectionData as $collection) {
            $url = $collection->getData('request_path');
        }
        $sellerCity = $this->citiesHelper->getCityNameById($cityId);
        foreach ($urlCollectionData as $collection) {
            $url = $collection->getData('request_path');
            if ($url !== null) {
                $cityString = preg_replace('#[^0-9a-z]+#i', '-', strtolower($sellerCity));
                $pos = strpos($url, $cityString . "-");
                if ($pos !== false) {
                    $url = substr_replace($url, $cityString . "/", $pos, strlen($cityString . "-"));
                } else {
                    $url = str_replace($cityString . "-", $cityString . "/", $url);
                }
            }
            $urlArray[$collection->getData('entity_id')] = $url;
        }
        return $urlArray;
    }

    public function replaceFirstString($search, $replace, $subject) {

    }
}
