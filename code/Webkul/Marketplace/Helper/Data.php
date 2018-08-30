<?php

/**
 * Webkul Software.
 *
 * @category  Webkul
 * @package   Webkul_Marketplace
 * @author    Webkul
 * @copyright Copyright (c) 2010-2017 Webkul Software Private Limited (https://webkul.com)
 * @license   https://store.webkul.com/license.html
 */

namespace Webkul\Marketplace\Helper;

use Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\CollectionFactory;
use Webkul\Marketplace\Model\Product as SellerProduct;
use Magento\Framework\App\Http\Context as HttpContext;
use Magento\Customer\Model\Context as CustomerContext;
use Magento\User\Model\ResourceModel\User\CollectionFactory as UserCollectionFactory;
use Magento\Eav\Model\Config as EavConfig;
use Bakeway\Vendorcontract\Model\ResourceModel\Vendorcontract\Collection as ContractCollection;
use Webkul\Marketplace\Model\ResourceModel\Orders\Collection AS OrderCollection;

/**
 * Webkul Marketplace Helper Data.
 */
class Data extends \Magento\Framework\App\Helper\AbstractHelper {

    /**
     * @var ObjectManagerInterface
     */
    protected $_objectManager;

    /**
     * @var Session
     */
    protected $_customerSession;

    /**
     * @var null|array
     */
    protected $_options;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product
     */
    protected $_product;

    /**
     * @var CollectionFactory
     */
    protected $_collectionFactory;

    /**
     * @var HttpContext
     */
    private $httpContext;

    /**
     * @var \Bakeway\Brands\Model\Brands
     */
    private $_brandscollection;

    /**
     * @var \Magento\Framework\Pricing\Helper\Data
     */
    protected $priceHelper;

    /**
     * @var UserCollectionFactory
     */
    protected $userCollectionFactory;

    /**
     * @var  \Bakeway\Vendorcontract\Model\VendorcontractFactory
     */
    protected $vendorcontractFactory;

    /**
     * @var EavConfig
     */
    protected $eavConfig;

    /**
     * @var ContractCollection
     */
    protected $contractCollection;

    /**
     * @var OrderCollection
     */
    protected $orderCollection;

    /**
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param \Magento\Customer\Model\Session $customerSession
     * @param CollectionFactory $collectionFactory
     * @param HttpContext $httpContext
     * @param \Magento\Catalog\Model\ResourceModel\Product $product
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Directory\Model\Currency $currency
     * @param \Magento\Framework\Locale\CurrencyInterface $localeCurrency
     * @param \Magento\Framework\Pricing\Helper\Data $priceHelper
     * @param UserCollectionFactory $userCollectionFactory
     * @param EavConfig $eavConfig
     * @param ContractCollection $contractCollection
     */
    public function __construct(
    \Magento\Framework\App\Helper\Context $context,
    \Magento\Framework\ObjectManagerInterface $objectManager,
    \Magento\Customer\Model\Session $customerSession,
    CollectionFactory $collectionFactory,
    HttpContext $httpContext,
    \Magento\Catalog\Model\ResourceModel\Product $product,
    \Magento\Store\Model\StoreManagerInterface $storeManager,
    \Magento\Directory\Model\Currency $currency,
    \Magento\Framework\Locale\CurrencyInterface $localeCurrency,
    \Bakeway\Brands\Model\BrandsFactory $brandsFactory,
    \Magento\Framework\Pricing\Helper\Data $priceHelper,
    UserCollectionFactory $userCollectionFactory,
    \Bakeway\Vendorcontract\Model\VendorcontractFactory $vendorcontractFactory,
    EavConfig $eavConfig,
    ContractCollection $contractCollection,
    OrderCollection $orderCollection
    ) {
        $this->_objectManager = $objectManager;
        $this->_customerSession = $customerSession;
        $this->_collectionFactory = $collectionFactory;
        $this->httpContext = $httpContext;
        $this->_product = $product;
        parent::__construct($context);
        $this->_currency = $currency;
        $this->_localeCurrency = $localeCurrency;
        $this->_storeManager = $storeManager;
        $this->_brandscollection = $brandsFactory;
        $this->priceHelper = $priceHelper;
        $this->userCollectionFactory = $userCollectionFactory;
        $this->vendorcontractFactory = $vendorcontractFactory;
        $this->eavConfig = $eavConfig;
        $this->contractCollection = $contractCollection;;
        $this->orderCollection = $orderCollection;

    }

    /**
     * Check if customer is logged in
     *
     * @return bool
     * @codeCoverageIgnore
     */
    public function isCustomerLoggedIn() {
        return (bool) $this->httpContext->getValue(CustomerContext::CONTEXT_AUTH);
    }

    /**
     * Return the Customer seller status.
     *
     * @return bool|0|1
     */
    public function isSeller() {
        $sellerStatus = 0;
        $model = $this->_objectManager->create(
                        'Webkul\Marketplace\Model\Seller'
                )
                ->getCollection()
                ->addFieldToFilter(
                'seller_id', $this->_customerSession->getCustomerId()
        );
        foreach ($model as $value) {
            $sellerStatus = $value->getIsSeller();
        }

        return $sellerStatus;
    }

    /**
     * Return the authorize seller status.
     *
     * @return bool|0|1
     */
    public function isRightSeller($productId = '') {
        $data = 0;
        $model = $this->_objectManager->create(
                                'Webkul\Marketplace\Model\Product'
                        )
                        ->getCollection()
                        ->addFieldToFilter(
                                'mageproduct_id', $productId
                        )->addFieldToFilter(
                'seller_id', $this->_customerSession->getCustomerId()
        );
        foreach ($model as $value) {
            $data = 1;
        }

        return $data;
    }

    /**
     * Return the seller Data.
     *
     * @return \Webkul\Marketplace\Model\ResourceModel\Seller\Collection
     */
    public function getSellerData() {
        $model = $this->_objectManager->create(
                        'Webkul\Marketplace\Model\Seller'
                )
                ->getCollection()
                ->addFieldToFilter(
                'seller_id', $this->_customerSession->getCustomerId()
        );

        return $model;
    }

    /**
     * Return the seller Product Data.
     *
     * @return \Webkul\Marketplace\Model\ResourceModel\Product\Collection
     */
    public function getSellerProductData() {
        $model = $this->_objectManager->create(
                        'Webkul\Marketplace\Model\Product'
                )
                ->getCollection()
                ->addFieldToFilter(
                'seller_id', $this->_customerSession->getCustomerId()
        );

        return $model;
    }

    /**
     * Return the seller product data by product id.
     *
     * @return \Webkul\Marketplace\Model\ResourceModel\Product\Collection
     */
    public function getSellerProductDataByProductId($productId = '') {
        $model = $this->_objectManager->create(
                        'Webkul\Marketplace\Model\Product'
                )
                ->getCollection()
                ->addFieldToFilter(
                'mageproduct_id', $productId
        );
        $websiteId = $this->getWebsiteId();
        $joinTable = $this->_objectManager->create(
                        'Webkul\Marketplace\Model\ResourceModel\Seller\Collection'
                )->getTable('customer_grid_flat');
        $model->getSelect()->join(
                $joinTable . ' as cgf', 'main_table.seller_id = cgf.entity_id AND website_id= ' . $websiteId
        );
        return $model;
    }

    /**
     * Return the seller data by seller id.
     *
     * @return \Webkul\Marketplace\Model\ResourceModel\Seller\Collection
     */
    public function getSellerDataBySellerId($sellerId = '') {
        $model = $this->_objectManager->create(
                        'Webkul\Marketplace\Model\Seller'
                )
                ->getCollection()
                ->addFieldToFilter(
                'seller_id', $sellerId
        );
        $websiteId = $this->getWebsiteId();
        $joinTable = $this->_objectManager->create(
                        'Webkul\Marketplace\Model\ResourceModel\Seller\Collection'
                )->getTable('customer_grid_flat');
        $model->getSelect()->join(
                $joinTable . ' as cgf', 'main_table.seller_id = cgf.entity_id AND website_id= ' . $websiteId
        );
        return $model;
    }

    public function getRootCategoryIdByStoreId($storeId = '') {
        return $this->_storeManager->getStore($storeId)->getRootCategoryId();
    }

    public function getAllStores() {
        return $this->_storeManager->getStores();
    }

    public function getCurrentStoreId() {
        // give the current store id
        return $this->_storeManager->getStore()->getStoreId();
    }

    public function getWebsiteId() {
        // give the current store id
        return $this->_storeManager->getStore(true)->getWebsite()->getId();
    }

    public function getAllWebsites() {
        // give the current store id
        return $this->_storeManager->getWebsites();
    }

    public function getSingleStoreStatus() {
        return $this->_storeManager->hasSingleStore();
    }

    public function getSingleStoreModeStatus() {
        return $this->_storeManager->isSingleStoreMode();
    }

    public function setCurrentStore($storeId) {
        return $this->_storeManager->setCurrentStore($storeId);
    }

    public function getCurrentCurrencyCode() {
        return $this->_storeManager->getStore()->getCurrentCurrencyCode();
        // give the currency code
    }

    public function getBaseCurrencyCode() {
        return $this->_storeManager->getStore()->getBaseCurrencyCode();
    }

    public function getConfigAllowCurrencies() {
        return $this->_currency->getConfigAllowCurrencies();
    }

    /**
     * Retrieve currency rates to other currencies.
     *
     * @param string $currency
     * @param array|null $toCurrencies
     *
     * @return array
     */
    public function getCurrencyRates($currency, $toCurrencies = null) {
        // give the currency rate
        return $this->_currency->getCurrencyRates($currency, $toCurrencies);
    }

    /**
     * Retrieve currency Symbol.
     *
     * @return string
     */
    public function getCurrencySymbol() {
        return $this->_localeCurrency->getCurrency(
                        $this->getBaseCurrencyCode()
                )->getSymbol();
    }

    /**
     * Retrieve price format.
     *
     * @return string
     */
    public function getPriceFormat() {
        return $this->_objectManager->create('Magento\Framework\Locale\Format')->getPriceFormat();
    }

    /**
     * @return array|null
     */
    public function getAllowedSets() {
        if (null == $this->_options) {
            $this->_options = $this->_collectionFactory->create()
                    ->addFieldToFilter(
                            'attribute_set_id', ['in' => explode(',', $this->getAllowedAttributesetIds())]
                    )
                    ->setEntityTypeFilter($this->_product->getTypeId())
                    ->toOptionArray();
        }

        return $this->_options;
    }

    /**
     * Options getter.
     *
     * @return array
     */
    public function getAllowedProductTypes() {
        $alloweds = explode(',', $this->getAllowedProductType());
        $data = [
            'simple' => __('Simple'),
            'downloadable' => __('Downloadable'),
            'virtual' => __('Virtual'),
            'configurable' => __('Configurable'),
            'grouped' => __('Grouped Product'),
            'bundle' => __('Bundle Product'),
        ];
        $allowedproducts = [];
        if (isset($alloweds)) {
            foreach ($alloweds as $allowed) {
                if (!empty($data[$allowed])) {
                    array_push(
                            $allowedproducts, ['value' => $allowed, 'label' => $data[$allowed]]
                    );
                }
            }
        }

        return $allowedproducts;
    }

    /**
     * Return the product visibilty options.
     *
     * @return \Magento\Tax\Model\ClassModel
     */
    public function getTaxClassModel() {
        return $this->_objectManager->create('Magento\Tax\Model\ClassModel')
                        ->getCollection()
                        ->addFieldToFilter('class_type', 'PRODUCT');
    }

    /**
     * Return the product visibilty options.
     *
     * @return \Magento\Catalog\Model\Product\Visibility
     */
    public function getVisibilityOptionArray() {
        return $this->_objectManager->create(
                        'Magento\Catalog\Model\Product\Visibility'
                )->getOptionArray();
    }

    /**
     * Return the Seller existing status.
     *
     * @return \Webkul\Marketplace\Model\ResourceModel\Seller\Collection
     */
    public function isSellerExist() {
        $model = $this->_objectManager->create(
                        'Webkul\Marketplace\Model\Seller'
                )
                ->getCollection()
                ->addFieldToFilter(
                'seller_id', $this->_customerSession->getCustomerId()
        );

        return $model->getSize();
    }

    /**
     * Return the Seller data by customer Id stored in the session.
     *
     * @return \Webkul\Marketplace\Model\ResourceModel\Seller\Collection
     */
    public function getSeller() {
        $data = [];
        $bannerpic = '';
        $logopic = '';
        $countrylogopic = '';
        $model = $this->_objectManager->create(
                        'Webkul\Marketplace\Model\Seller'
                )
                ->getCollection()
                ->addFieldToFilter(
                'seller_id', $this->_customerSession->getCustomerId()
        );
        $customer = $this->_objectManager->create(
                        'Magento\Customer\Model\Customer'
                )->load($this->_customerSession->getCustomerId());
        foreach ($model as $value) {
            $data = $value->getData();
            $bannerpic = $value->getBannerPic();
            $logopic = $value->getLogoPic();
            $countrylogopic = $value->getCountryPic();
            if (strlen($bannerpic) <= 0) {
                $bannerpic = '';
            }
            if (strlen($logopic) <= 0) {
                $logopic = 'noimage.png';
            }
            if (strlen($countrylogopic) <= 0) {
                $countrylogopic = '';
            }
        }
        $data['banner_pic'] = $bannerpic;
        $data['taxvat'] = $customer->getTaxvat();
        $data['logo_pic'] = $logopic;
        $data['country_pic'] = $countrylogopic;

        return $data;
    }

    public function getFeedTotal($sellerId) {
        $data = [];
        $collection = $this->_objectManager->create(
                        'Webkul\Marketplace\Model\Feedback'
                )
                ->getCollection()
                ->addFieldToFilter(
                'seller_id', $sellerId
        );
        $collection->addFieldToFilter(
                'status', ['neq' => 0]
        );
        $price = 0;
        $value = 0;
        $quality = 0;
        $totalfeed = 0;
        $feedCount = 0;
        $collectionCount = 1;
        foreach ($collection as $record) {
            $price += $record->getFeedPrice();
            $value += $record->getFeedValue();
            $quality += $record->getFeedQuality();
        }
        $collectionSize = $collection->getSize();
        if ($collectionSize != 0) {
            $feedCount = $collectionSize;
            $collectionCount = $collectionSize;
            $totalfeed = ceil(
                    ($price + $value + $quality) / (3 * $collectionCount)
            );
        }

        $data = [
            'price' => $price / $collectionCount,
            'value' => $value / $collectionCount,
            'quality' => $quality / $collectionCount,
            'totalfeed' => $totalfeed,
            'feedcount' => $feedCount,
        ];

        return $data;
    }

    public function getSelleRating($sellerId) {
        $feeds = $this->getFeedTotal($sellerId);
        $totalRating = (
                $feeds['price'] + $feeds['value'] + $feeds['quality']
                ) / 60;

        return round($totalRating, 1, PHP_ROUND_HALF_UP);
    }

    public function getCatatlogGridPerPageValues() {
        return $this->scopeConfig->getValue(
                        'catalog/frontend/grid_per_page_values', \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getCaptchaEnable() {
        return $this->scopeConfig->getValue(
                        'marketplace/general_settings/captcha', \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getDefaultTransEmailId() {
        return $this->scopeConfig->getValue(
                        'trans_email/ident_general/email', \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getAdminEmailId() {
        return $this->scopeConfig->getValue(
                        'marketplace/general_settings/adminemail', \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getAllowedCategoryIds() {
        $seller = $this->getSeller();
        if ($seller['allowed_categories']) {
            return $seller['allowed_categories'];
        } else {
            return $this->scopeConfig->getValue(
                            'marketplace/product_settings/categoryids', \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            );
        }
    }

    public function getIsProductEditApproval() {
        return $this->scopeConfig->getValue(
                        'marketplace/product_settings/product_edit_approval', \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getIsPartnerApproval() {
        return $this->scopeConfig->getValue(
                        'marketplace/general_settings/seller_approval', \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getIsProductApproval() {
        return $this->scopeConfig->getValue(
                        'marketplace/product_settings/product_approval', \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getAllowedAttributesetIds() {
        return $this->scopeConfig->getValue(
                        'marketplace/product_settings/attributesetid', \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getAllowedProductType() {
        return $this->scopeConfig->getValue(
                        'marketplace/product_settings/allow_for_seller', \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getUseCommissionRule() {
        return $this->scopeConfig->getValue(
                        'mpadvancedcommission/options/use_commission_rule', \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getCommissionType() {
        return $this->scopeConfig->getValue(
                        'mpadvancedcommission/options/commission_type', \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getIsOrderManage() {
        return $this->scopeConfig->getValue(
                        'marketplace/general_settings/order_manage', \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getConfigCommissionRate() {
        return $this->scopeConfig->getValue(
                        'marketplace/general_settings/percent', \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getConfigTaxManage() {
        return $this->scopeConfig->getValue(
                        'marketplace/general_settings/tax_manage', \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getlowStockNotification() {
        return $this->scopeConfig->getValue(
                        'marketplace/inventory_settings/low_stock_notification', \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getlowStockQty() {
        return $this->scopeConfig->getValue(
                        'marketplace/inventory_settings/low_stock_amount', \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getActiveColorPicker() {
        return $this->scopeConfig->getValue(
                        'marketplace/profile_settings/activecolorpicker', \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getSellerPolicyApproval() {
        return $this->scopeConfig->getValue(
                        'marketplace/profile_settings/seller_policy_approval', \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getUrlRewrite() {
        return $this->scopeConfig->getValue(
                        'marketplace/profile_settings/url_rewrite', \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getReviewStatus() {
        return $this->scopeConfig->getValue(
                        'marketplace/review_settings/review_status', \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getMarketplaceHeadLabel() {
        return $this->scopeConfig->getValue(
                        'marketplace/landingpage_settings/marketplacelabel', \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getMarketplacelabel1() {
        return $this->scopeConfig->getValue(
                        'marketplace/landingpage_settings/marketplacelabel1', \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getMarketplacelabel2() {
        return $this->scopeConfig->getValue(
                        'marketplace/landingpage_settings/marketplacelabel2', \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getMarketplacelabel3() {
        return $this->scopeConfig->getValue(
                        'marketplace/landingpage_settings/marketplacelabel3', \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getMarketplacelabel4() {
        return $this->scopeConfig->getValue(
                        'marketplace/landingpage_settings/marketplacelabel4', \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getDisplayBanner() {
        return $this->scopeConfig->getValue(
                        'marketplace/landingpage_settings/displaybanner', \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getBannerImage() {
        return $this->_storeManager->getStore()->getBaseUrl(
                        \Magento\Framework\UrlInterface::URL_TYPE_MEDIA
                ) . 'marketplace/banner/' . $this->scopeConfig->getValue(
                        'marketplace/landingpage_settings/banner', \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getBannerContent() {
        return $this->scopeConfig->getValue(
                        'marketplace/landingpage_settings/bannercontent', \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getDisplayIcon() {
        return $this->scopeConfig->getValue(
                        'marketplace/landingpage_settings/displayicons', \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getIconImage1() {
        return $this->_storeManager->getStore()->getBaseUrl(
                        \Magento\Framework\UrlInterface::URL_TYPE_MEDIA
                ) . 'marketplace/icon/' . $this->scopeConfig->getValue(
                        'marketplace/landingpage_settings/feature_icon1', \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getIconImageLabel1() {
        return $this->scopeConfig->getValue(
                        'marketplace/landingpage_settings/feature_icon1_label', \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getIconImage2() {
        return $this->_storeManager->getStore()->getBaseUrl(
                        \Magento\Framework\UrlInterface::URL_TYPE_MEDIA
                ) . 'marketplace/icon/' . $this->scopeConfig->getValue(
                        'marketplace/landingpage_settings/feature_icon2', \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getIconImageLabel2() {
        return $this->scopeConfig->getValue(
                        'marketplace/landingpage_settings/feature_icon2_label', \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getIconImage3() {
        return $this->_storeManager->getStore()->getBaseUrl(
                        \Magento\Framework\UrlInterface::URL_TYPE_MEDIA
                ) . 'marketplace/icon/' . $this->scopeConfig->getValue(
                        'marketplace/landingpage_settings/feature_icon3', \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getIconImageLabel3() {
        return $this->scopeConfig->getValue(
                        'marketplace/landingpage_settings/feature_icon3_label', \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getIconImage4() {
        return $this->_storeManager->getStore()->getBaseUrl(
                        \Magento\Framework\UrlInterface::URL_TYPE_MEDIA
                ) . 'marketplace/icon/' . $this->scopeConfig->getValue(
                        'marketplace/landingpage_settings/feature_icon4', \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getIconImageLabel4() {
        return $this->scopeConfig->getValue(
                        'marketplace/landingpage_settings/feature_icon4_label', \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getMarketplacebutton() {
        return $this->scopeConfig->getValue(
                        'marketplace/landingpage_settings/marketplacebutton', \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getMarketplaceprofile() {
        return $this->scopeConfig->getValue(
                        'marketplace/landingpage_settings/marketplaceprofile', \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getSellerlisttopLabel() {
        return $this->scopeConfig->getValue(
                        'marketplace/landingpage_settings/sellerlisttop', \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getSellerlistbottomLabel() {
        return $this->scopeConfig->getValue(
                        'marketplace/landingpage_settings/sellerlistbottom', \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getProductHintStatus() {
        return $this->scopeConfig->getValue(
                        'marketplace/producthint_settings/product_hint_status', \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getProductHintCategory() {
        return $this->scopeConfig->getValue(
                        'marketplace/producthint_settings/product_category', \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getProductHintName() {
        return $this->scopeConfig->getValue(
                        'marketplace/producthint_settings/product_name', \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getProductHintDesc() {
        return $this->scopeConfig->getValue(
                        'marketplace/producthint_settings/product_des', \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getProductHintShortDesc() {
        return $this->scopeConfig->getValue(
                        'marketplace/producthint_settings/product_sdes', \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getProductHintSku() {
        return $this->scopeConfig->getValue(
                        'marketplace/producthint_settings/product_sku', \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getProductHintPrice() {
        return $this->scopeConfig->getValue(
                        'marketplace/producthint_settings/product_price', \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getProductHintSpecialPrice() {
        return $this->scopeConfig->getValue(
                        'marketplace/producthint_settings/product_sprice', \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getProductHintStartDate() {
        return $this->scopeConfig->getValue(
                        'marketplace/producthint_settings/product_sdate', \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getProductHintEndDate() {
        return $this->scopeConfig->getValue(
                        'marketplace/producthint_settings/product_edate', \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getProductHintQty() {
        return $this->scopeConfig->getValue(
                        'marketplace/producthint_settings/product_qty', \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getProductHintStock() {
        return $this->scopeConfig->getValue(
                        'marketplace/producthint_settings/product_stock', \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getProductHintTax() {
        return $this->scopeConfig->getValue(
                        'marketplace/producthint_settings/product_tax', \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getProductHintWeight() {
        return $this->scopeConfig->getValue(
                        'marketplace/producthint_settings/product_weight', \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getProductHintImage() {
        return $this->scopeConfig->getValue(
                        'marketplace/producthint_settings/product_image', \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getProductHintEnable() {
        return $this->scopeConfig->getValue(
                        'marketplace/producthint_settings/product_enable', \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getProfileHintStatus() {
        return $this->scopeConfig->getValue(
                        'marketplace/profilehint_settings/profile_hint_status', \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getProfileHintBecomeSeller() {
        return $this->scopeConfig->getValue(
                        'marketplace/profilehint_settings/become_seller', \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getProfileHintShopurl() {
        return $this->scopeConfig->getValue(
                        'marketplace/profilehint_settings/shopurl_seller', \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getProfileHintTw() {
        return $this->scopeConfig->getValue(
                        'marketplace/profilehint_settings/profile_tw', \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getProfileHintFb() {
        return $this->scopeConfig->getValue(
                        'marketplace/profilehint_settings/profile_fb', \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getProfileHintCn() {
        return $this->scopeConfig->getValue(
                        'marketplace/profilehint_settings/profile_cn', \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getProfileHintBc() {
        return $this->scopeConfig->getValue(
                        'marketplace/profilehint_settings/profile_bc', \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getProfileHintShop() {
        return $this->scopeConfig->getValue(
                        'marketplace/profilehint_settings/profile_shop', \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getProfileHintBanner() {
        return $this->scopeConfig->getValue(
                        'marketplace/profilehint_settings/profile_banner', \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getProfileHintLogo() {
        return $this->scopeConfig->getValue(
                        'marketplace/profilehint_settings/profile_logo', \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getProfileHintLoc() {
        return $this->scopeConfig->getValue(
                        'marketplace/profilehint_settings/profile_loc', \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getProfileHintDesc() {
        return $this->scopeConfig->getValue(
                        'marketplace/profilehint_settings/profile_desciption', \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getProfileHintReturnPolicy() {
        return $this->scopeConfig->getValue(
                        'marketplace/profilehint_settings/returnpolicy', \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getProfileHintShippingPolicy() {
        return $this->scopeConfig->getValue(
                        'marketplace/profilehint_settings/shippingpolicy', \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getProfileHintCountry() {
        return $this->scopeConfig->getValue(
                        'marketplace/profilehint_settings/profile_country', \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getProfileHintMeta() {
        return $this->scopeConfig->getValue(
                        'marketplace/profilehint_settings/profile_meta', \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getProfileHintMetaDesc() {
        return $this->scopeConfig->getValue(
                        'marketplace/profilehint_settings/profile_mdesc', \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getProfileHintBank() {
        return $this->scopeConfig->getValue(
                        'marketplace/profilehint_settings/profile_bank', \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getProfileUrl() {
        $targetUrl = $this->getTargetUrlPath();
        if ($targetUrl) {
            $temp = explode('/profile/shop', $targetUrl);
            if (!isset($temp[1])) {
                $temp[1] = '';
            }
            $temp = explode('/', $temp[1]);
            if (isset($temp[1]) && $temp[1] != '') {
                $temp1 = explode('?', $temp[1]);

                return $temp1[0];
            }
        }

        return false;
    }

    public function getCollectionUrl() {
        $targetUrl = $this->getTargetUrlPath();
        if ($targetUrl) {
            $temp = explode('/collection/shop', $targetUrl);
            if (!isset($temp[1])) {
                $temp[1] = '';
            }
            $temp = explode('/', $temp[1]);
            if (isset($temp[1]) && $temp[1] != '') {
                $temp1 = explode('?', $temp[1]);

                return $temp1[0];
            }
        }

        return false;
    }

    public function getLocationUrl() {
        $targetUrl = $this->getTargetUrlPath();
        if ($targetUrl) {
            $temp = explode('/location/shop', $targetUrl);
            if (!isset($temp[1])) {
                $temp[1] = '';
            }
            $temp = explode('/', $temp[1]);
            if (isset($temp[1]) && $temp[1] != '') {
                $temp1 = explode('?', $temp[1]);

                return $temp1[0];
            }
        }

        return false;
    }

    public function getFeedbackUrl() {
        $targetUrl = $this->getTargetUrlPath();
        if ($targetUrl) {
            $temp = explode('/feedback/shop', $targetUrl);
            if (!isset($temp[1])) {
                $temp[1] = '';
            }
            $temp = explode('/', $temp[1]);
            if (isset($temp[1]) && $temp[1] != '') {
                $temp1 = explode('?', $temp[1]);

                return $temp1[0];
            }
        }

        return false;
    }

    public function getRewriteUrl($targetUrl) {
        $requestUrl = $this->_urlBuilder->getUrl(
                '', [
            '_direct' => $targetUrl,
            '_secure' => $this->_request->isSecure(),
                ]
        );
        $urlColl = $this->_objectManager->create('Magento\UrlRewrite\Model\UrlRewrite')
                ->getCollection()
                ->addFieldToFilter('target_path', $targetUrl)
                ->addFieldToFilter('store_id', $this->getCurrentStoreId());
        foreach ($urlColl as $value) {
            $requestUrl = $this->_urlBuilder->getUrl(
                    '', [
                '_direct' => $value->getRequestPath(),
                '_secure' => $this->_request->isSecure(),
                    ]
            );
        }

        return $requestUrl;
    }

    public function getRewriteUrlPath($targetUrl) {
        $requestPath = '';
        $urlColl = $this->_objectManager->create(
                        'Magento\UrlRewrite\Model\UrlRewrite'
                )
                ->getCollection()
                ->addFieldToFilter(
                        'target_path', $targetUrl
                )
                ->addFieldToFilter(
                'store_id', $this->getCurrentStoreId()
        );
        foreach ($urlColl as $value) {
            $requestPath = $value->getRequestPath();
        }

        return $requestPath;
    }

    public function getTargetUrlPath() {
        $urls = explode(
                $this->_urlBuilder->getUrl(
                        '', ['_secure' => $this->_request->isSecure()]
                ), $this->_urlBuilder->getCurrentUrl()
        );
        $targetUrl = '';
        $temp = explode('/?', $urls[1]);
        if (!isset($temp[1])) {
            $temp[1] = '';
        }
        if (!$temp[1]) {
            $temp = explode('?', $temp[0]);
        }
        $requestPath = $temp[0];
        $urlColl = $this->_objectManager->create('Magento\UrlRewrite\Model\UrlRewrite')
                ->getCollection()
                ->addFieldToFilter(
                        'request_path', ['eq' => $requestPath]
                )
                ->addFieldToFilter(
                'store_id', ['eq' => $this->getCurrentStoreId()]
        );
        foreach ($urlColl as $value) {
            $targetUrl = $value->getTargetPath();
        }

        return $targetUrl;
    }

    public function getPlaceholderImage() {
        return $this->_storeManager->getStore()->getBaseUrl(
                        \Magento\Framework\UrlInterface::URL_TYPE_MEDIA
                ) . 'marketplace/placeholder/image.jpg';
    }

    public function getSellerProCount($sellerId) {
        $querydata = $this->_objectManager->create(
                        'Webkul\Marketplace\Model\Product'
                )
                ->getCollection()
                ->addFieldToFilter('seller_id', $sellerId)
                ->addFieldToFilter('status', ['neq' => SellerProduct::STATUS_DISABLED])
                ->addFieldToSelect('mageproduct_id')
                ->setOrder('mageproduct_id');
        $collection = $this->_objectManager->create(
                        'Magento\Catalog\Model\Product'
                )
                ->getCollection();
        $collection->addAttributeToSelect('*');
        $collection->addAttributeToFilter('entity_id', ['in' => $querydata->getData()]);
        $collection->addAttributeToFilter('visibility', ['in' => [4]]);
        $collection->addAttributeToFilter('status', ['neq' => SellerProduct::STATUS_DISABLED]);
        $collection->addStoreFilter();
        return $collection->getSize();
    }

    public function getMediaUrl() {
        return $this->_storeManager->getStore()->getBaseUrl(
                        \Magento\Framework\UrlInterface::URL_TYPE_MEDIA
        );
    }

    public function getMaxDownloads() {
        return $this->scopeConfig->getValue(
                        \Magento\Downloadable\Model\Link::XML_PATH_DEFAULT_DOWNLOADS_NUMBER, \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getConfigPriceWebsiteScope() {
        $scope = $this->scopeConfig->getValue(
                \Magento\Store\Model\Store::XML_PATH_PRICE_SCOPE, \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        if ($scope == \Magento\Store\Model\Store::PRICE_SCOPE_WEBSITE) {
            return true;
        }

        return false;
    }

    public function getSkuType() {
        return $this->scopeConfig->getValue(
                        'marketplace/product_settings/sku_type', \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getSkuPrefix() {
        return $this->scopeConfig->getValue(
                        'marketplace/product_settings/sku_prefix', \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getSellerProfileDisplayFlag() {
        return $this->scopeConfig->getValue(
                        'marketplace/profile_settings/seller_profile_display', \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getAutomaticUrlRewrite() {
        return $this->scopeConfig->getValue(
                        'marketplace/profile_settings/auto_url_rewrite', \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Retrieve YouTube API key
     *
     * @return string
     */
    public function getYouTubeApiKey() {
        return $this->scopeConfig->getValue(
                        'catalog/product_video/youtube_api_key'
        );
    }

    public function getAllowedControllersBySetData($allowedModule) {
        $allowedModuleArr = [];
        if ($allowedModule && $allowedModule != 'all') {
            $allowedModuleControllers = explode(',', $allowedModule);
            foreach ($allowedModuleControllers as $key => $value) {
                array_push($allowedModuleArr, $value);
            }
        } else {
            $controllersRepository = $this->_objectManager->create(
                    'Webkul\Marketplace\Model\ControllersRepository'
            );
            $controllersList = $controllersRepository->getList();
            foreach ($controllersList as $key => $value) {
                array_push($allowedModuleArr, $value['controller_path']);
            }
        }
        return $allowedModuleArr;
    }

    public function isSellerGroupModuleInstalled() {
        if ($this->_moduleManager->isEnabled('Webkul_MpSellerGroup')) {
            return true;
        }
        return false;
    }

    public function isAllowedAction($actionName = '') {
        $sellerGroupHelper = $this->_objectManager->create(
                'Webkul\MpSellerGroup\Helper\Data'
        );
        if (!$sellerGroupHelper->getStatus()) {
            return true;
        }
        $sellerId = $this->_customerSession->getCustomerId();
        $sellerGroupTypeRepository = $this->_objectManager->create(
                'Webkul\MpSellerGroup\Api\SellerGroupTypeRepositoryInterface'
        );
        if (!$sellerGroupTypeRepository->getBySellerCount($sellerId)) {
            $products = $this->_objectManager->create(
                            'Webkul\Marketplace\Model\Product'
                    )->getCollection()
                    ->addFieldToFilter(
                    'seller_id', $this->_customerSession->getCustomerId()
            );
            $getDefaultGroupStatus = $sellerGroupHelper->getDefaultGroupStatus();
            if ($getDefaultGroupStatus) {
                $allowqty = $sellerGroupHelper->getDefaultProductAllowed();
                $allowFunctionalities = explode(',', $sellerGroupHelper->getDefaultAllowedFeatures());
                if ($allowqty >= count($products)) {
                    if (in_array($actionName, $allowFunctionalities, true)) {
                        return true;
                    }
                }
            }
        }
        $getSellerGroup = $sellerGroupTypeRepository->getBySellerId($sellerId);
        if (count($getSellerGroup->getData())) {
            $getSellerTypeGroup = $getSellerGroup;
            $allowedModuleArr = $this->getAllowedControllersBySetData(
                    $getSellerTypeGroup['allowed_modules_functionalities']
            );
            if (in_array($actionName, $allowedModuleArr, true)) {
                return true;
            }
        }
        return false;
    }

    public function getPageLayout() {
        return $this->scopeConfig->getValue(
                        'marketplace/landingpage_settings/pageLayout', \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getDisplayBannerLayout2() {
        return $this->scopeConfig->getValue(
                        'marketplace/landingpage_settings/displaybannerLayout2', \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getBannerImageLayout2() {
        return $this->_storeManager->getStore()->getBaseUrl(
                        \Magento\Framework\UrlInterface::URL_TYPE_MEDIA
                ) . 'marketplace/banner/' . $this->scopeConfig->getValue(
                        'marketplace/landingpage_settings/bannerLayout2', \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getBannerContentLayout2() {
        return $this->scopeConfig->getValue(
                        'marketplace/landingpage_settings/bannercontentLayout2', \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getBannerButtonLayout2() {
        return $this->scopeConfig->getValue(
                        'marketplace/landingpage_settings/marketplacebuttonLayout2', \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getTermsConditionUrlLayout2() {
        return $this->scopeConfig->getValue(
                        'marketplace/landingpage_settings/termConditionLinkLayout2', \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getDisplayBannerLayout3() {
        return $this->scopeConfig->getValue(
                        'marketplace/landingpage_settings/displaybannerLayout3', \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getBannerImageLayout3() {
        return $this->_storeManager->getStore()->getBaseUrl(
                        \Magento\Framework\UrlInterface::URL_TYPE_MEDIA
                ) . 'marketplace/banner/' . $this->scopeConfig->getValue(
                        'marketplace/landingpage_settings/bannerLayout3', \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getBannerContentLayout3() {
        return $this->scopeConfig->getValue(
                        'marketplace/landingpage_settings/bannercontentLayout3', \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getBannerButtonLayout3() {
        return $this->scopeConfig->getValue(
                        'marketplace/landingpage_settings/marketplacebuttonLayout2', \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getTermsConditionUrlLayout3() {
        return $this->scopeConfig->getValue(
                        'marketplace/landingpage_settings/termConditionLinkLayout3', \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getDisplayIconLayout3() {
        return $this->scopeConfig->getValue(
                        'marketplace/landingpage_settings/displayiconsLayout3', \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getIconImage1Layout3() {
        return $this->_storeManager->getStore()->getBaseUrl(
                        \Magento\Framework\UrlInterface::URL_TYPE_MEDIA
                ) . 'marketplace/icon/' . $this->scopeConfig->getValue(
                        'marketplace/landingpage_settings/feature_icon1_layout3', \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getIconImageLabel1Layout3() {
        return $this->scopeConfig->getValue(
                        'marketplace/landingpage_settings/feature_icon1_label_layout3', \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getIconImage2Layout3() {
        return $this->_storeManager->getStore()->getBaseUrl(
                        \Magento\Framework\UrlInterface::URL_TYPE_MEDIA
                ) . 'marketplace/icon/' . $this->scopeConfig->getValue(
                        'marketplace/landingpage_settings/feature_icon2_layout3', \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getIconImageLabel2Layout3() {
        return $this->scopeConfig->getValue(
                        'marketplace/landingpage_settings/feature_icon2_label_layout3', \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getIconImage3Layout3() {
        return $this->_storeManager->getStore()->getBaseUrl(
                        \Magento\Framework\UrlInterface::URL_TYPE_MEDIA
                ) . 'marketplace/icon/' . $this->scopeConfig->getValue(
                        'marketplace/landingpage_settings/feature_icon3_layout3', \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getIconImageLabel3Layout3() {
        return $this->scopeConfig->getValue(
                        'marketplace/landingpage_settings/feature_icon3_label_layout3', \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getIconImage4Layout3() {
        return $this->_storeManager->getStore()->getBaseUrl(
                        \Magento\Framework\UrlInterface::URL_TYPE_MEDIA
                ) . 'marketplace/icon/' . $this->scopeConfig->getValue(
                        'marketplace/landingpage_settings/feature_icon4_layout3', \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getIconImageLabel4Layout3() {
        return $this->scopeConfig->getValue(
                        'marketplace/landingpage_settings/feature_icon4_label_layout3', \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getIconImage5Layout3() {
        return $this->_storeManager->getStore()->getBaseUrl(
                        \Magento\Framework\UrlInterface::URL_TYPE_MEDIA
                ) . 'marketplace/icon/' . $this->scopeConfig->getValue(
                        'marketplace/landingpage_settings/feature_icon5_layout3', \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getIconImageLabel5Layout3() {
        return $this->scopeConfig->getValue(
                        'marketplace/landingpage_settings/feature_icon5_label_layout3', \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getMarketplacelabel1Layout3() {
        return $this->scopeConfig->getValue(
                        'marketplace/landingpage_settings/marketplacelabel1Layout3', \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getMarketplacelabel2Layout3() {
        return $this->scopeConfig->getValue(
                        'marketplace/landingpage_settings/marketplacelabel2Layout3', \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getMarketplacelabel3Layout3() {
        return $this->scopeConfig->getValue(
                        'marketplace/landingpage_settings/marketplacelabel3Layout3', \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getOrderApprovalRequired() {
        return $this->scopeConfig->getValue(
                        'marketplace/order_settings/order_approval', \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getAllowProductLimit() {
        return $this->scopeConfig->getValue(
                        'marketplace/product_settings/allow_product_limit', \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getGlobalProductLimitQty() {
        return $this->scopeConfig->getValue(
                        'marketplace/product_settings/global_product_limit', \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getOrderedPricebyorder($order, $price) {
        $helper = $this->_objectManager->create(
                'Webkul\Marketplace\Helper\Data'
        );
        /*
         * Get Current Store Currency Rate
         */
        $currentCurrencyCode = $order->getOrderCurrencyCode();
        $baseCurrencyCode = $order->getBaseCurrencyCode();
        $allowedCurrencies = $helper->getConfigAllowCurrencies();
        $rates = $helper->getCurrencyRates(
                $baseCurrencyCode, array_values($allowedCurrencies)
        );
        if (empty($rates[$currentCurrencyCode])) {
            $rates[$currentCurrencyCode] = 1;
        }
        return $price / $rates[$currentCurrencyCode];
    }

    public function getBakeryTypeOptionArray() {
        return [
                ['label' => __('---Select Type---'), 'value' => ''],
                ['label' => __('Franchise'), 'value' => '1'],
                ['label' => __('Boutique Shop'), 'value' => '2'],
                ['label' => __('Home Baker'), 'value' => '3'],
        ];
    }

    public function getTimeOptionArray($time) {
        $options = array();
        $options[] = ['label' => __('---Please Select---'), 'value' => ''];
        for ($i = 0; $i <= $time; $i++) {
            $options[] = array('label' => sprintf("%02d", $i), 'value' => sprintf("%02d", $i));
        }
        return $options;
    }
    
    /**
     * Function to get advance
     * intimation unit option values
     * @return array
     */
    public function getUnitOptionArray() {
        $options = array();
        $options[] = ['label' => __('---Please Select---'), 'value' => ''];
        $options[] = array('label' => 'Hour', 'value' => 1);
        $options[] = array('label' => 'Minutes', 'value' => 2);
        return $options;
    }

    public function geAmPmArray() {
        return [
                ['label' => __('---Select Type---'), 'value' => ''],
                ['label' => __('AM'), 'value' => '1'],
                ['label' => __('PM'), 'value' => '2']
        ];
    }

    public function getStoreTimeOptionArray($time) {
        $options = array();
        $options[] = ['label' => __('---Please Select---'), 'value' => ''];
        for ($i = 1; $i <= $time; $i++) {
            $options[] = array('label' => sprintf("%02d", $i), 'value' => sprintf("%02d", $i));
        }
        return $options;
    }

    /**
     * Seller Id get from customer id.
     *
     * @return \Webkul\Marketplace\Model\ResourceModel\Seller\Collection
     */
    public function isSellerCustomerExist($id) {
        $sellerStatus = 0;
        $model = $this->_objectManager->create(
                        'Webkul\Marketplace\Model\Seller'
                )
                ->getCollection()
                ->addFieldToFilter(
                'seller_id', $id
        );
        foreach ($model as $value) {
            $sellerStatus = $value->getIsSeller();
        }

        return $sellerStatus;
    }

    /**
     * Seller status get from customer id.
     *
     * @return \Webkul\Marketplace\Model\ResourceModel\Seller\Collection
     */
    public function isSellerApproved() {
        $sellerStatus = 0;
        $model = $this->_objectManager->create(
                        'Webkul\Marketplace\Model\Seller'
                )
                ->getCollection()
                ->addFieldToFilter(
                'seller_id', $this->_customerSession->getCustomerId()
        );
        foreach ($model as $value) {
            $sellerStatus = $value->getIsSeller();
        }
        return $sellerStatus;
    }

    public function getBrandOptionArray($sellerId = null) {
        if (!is_null($sellerId)) {
            $model = $this->_objectManager->create('Bakeway\Partnerlocations\Model\Partnerlocations');
            $collection = $model->getCollection()->addFieldToFilter('seller_id', ['eq'=>$sellerId]);
            $sellerData = $collection->getData();
            if (isset($sellerData[0])) {
                $cityId = $sellerData[0]['city_id'];
            } else {
                $cityId = 1;//1 is for pune
            }

            $_Collection = $this->_brandscollection->create()->getCollection()
                ->addFieldToFilter("status", 1)
                ->addFieldToFilter("city_id", $cityId);            
        } else {
            $_Collection = $this->_brandscollection->create()->getCollection()
                ->addFieldToFilter("status", 1);
        }

        $_Optionlist = array();
        $_Optionlist[] = ['label' => __('---Please Select---'), 'value' => ''];

        foreach ($_Collection as $_Data) {
            $_Optionlist[] = array("label" => $_Data['brand_name'], "value" => $_Data['entity_id']);
        }
        return $_Optionlist;
    }

    /**
     * @param $price
     * @return float|string
     */
    public function getFormatedPrice($price) {
        return $this->priceHelper->currency($price);
    }

    /**
     * @return custom welcome message
     */
    public function getBakewayWelcomeMessage() {
        $model = $this->_objectManager->create(
                        'Webkul\Marketplace\Model\Seller'
                )
                ->getCollection()
                ->addFieldToFilter(
                'seller_id', $this->_customerSession->getCustomerId()
        );
        foreach ($model as $value) {
            $sellerShoptitle = $value->getShopTitle();
        }

        if (!empty($sellerShoptitle)) {
            return $sellerShoptitle;
        } else {
            return;
        }
    }

    /**
     * @return brand name
     */
    public function getBrandname($id) {

        $_Collection = $this->_brandscollection->create()->getCollection()
                ->addFieldToFilter("entity_id", $id)
                ->getFirstItem();
        return $_Collection['brand_name'];
    }

    /**
     * @return bakery name
     */
    public function getBakeryname($id) {

        $_Collection = $this->getBakeryTypeOptionArray();

        foreach ($_Collection as $_Collection1) {

            if ($id == $_Collection1['value']) {
                echo $_Collection1['label'];
            }
        }
    }

    public function getAdminUserOptionArray() {
        $collection = $this->userCollectionFactory->create();

        $options = [];
        $options[] = ['label' => __('---Please Select---'), 'value' => ''];

        foreach ($collection as $data) {
            $options[] = array("label" => $data['firstname'] . " " . $data['lastname'], "value" => $data['user_id']);
        }

        return $options;
    }

    public function getBakwayPocName($userId) {
        $users = $this->getAdminUserOptionArray();

        foreach ($users as $user) {
            if ($userId == $user['value']) {
                return $user['label'];
            }
        }
    }

    public function getVendorConractdtails() {
        $_Details = $this->vendorcontractFactory->create()->getCollection()
                ->addFieldToFilter('seller_id', $this->_customerSession->getCustomerId())
                ->getFirstItem();

        if ($_Details['start_date'] && $_Details['end_date']):

            return $this->dateDiff($_Details['start_date'], $_Details['end_date']);


        else:
            return "";
        endif;
    }

    public function dateDiff($time1, $time2, $precision = 6) {
        // If not numeric then convert texts to unix timestamps
        if (!is_int($time1)) {
            $time1 = strtotime($time1);
        }
        if (!is_int($time2)) {
            $time2 = strtotime($time2);
        }

        // If time1 is bigger than time2
        // Then swap time1 and time2
        if ($time1 > $time2) {
            $ttime = $time1;
            $time1 = $time2;
            $time2 = $ttime;
        }

        // Set up intervals and diffs arrays
        $intervals = array('year', 'month', 'day', 'hour', 'minute', 'second');
        $diffs = array();

        // Loop thru all intervals
        foreach ($intervals as $interval) {
            // Create temp time from time1 and interval
            $ttime = strtotime('+1 ' . $interval, $time1);
            // Set initial values
            $add = 1;
            $looped = 0;
            // Loop until temp time is smaller than time2
            while ($time2 >= $ttime) {
                // Create new temp time from time1 and interval
                $add++;
                $ttime = strtotime("+" . $add . " " . $interval, $time1);
                $looped++;
            }

            $time1 = strtotime("+" . $looped . " " . $interval, $time1);
            $diffs[$interval] = $looped;
        }

        $count = 0;
        $times = array();
        // Loop thru all diffs
        foreach ($diffs as $interval => $value) {
            // Break if we have needed precission
            if ($count >= $precision) {
                break;
            }
            // Add value and interval 
            // if value is bigger than 0
            if ($value > 0) {
                // Add s if value is not 1
                if ($value != 1) {
                    $interval .= "s";
                }
                // Add value and interval to times array
                $times[] = $value . " " . $interval;
                $count++;
            }
        }

        // Return string with times
        return implode(", ", $times);
    }

    /**
     * @param string $attributeCode
     * @return array
     */
    public function getAttributeOptions($attributeCode) {
        $attribute = $this->eavConfig->getAttribute('catalog_product', $attributeCode);

        if ($attribute) {
            $options = $attribute->getSource()->getAllOptions();
            if (!is_array($options) || empty($options) || !isset($options[1])) {
                return false;
            }
            return $options;
        }

        return false;
    }

    /**
     * @param int $sellerId
     * @return bool
     */
    public function getIsLiveReady($sellerId) {
        $seller = $this->getSellerDataBySellerId($sellerId);
        if ($seller->count() > 0) {
            $sellerData = $seller->getFirstItem();
            if (!$sellerData->getData('is_live_ready')) {
                return false;
            } else {
                $inContract = $this->getIsInContract($sellerId);
                if ($inContract === true) {
                    return true;
                } else {
                    return false;
                }
            }
        } else {
            return false;
        }
    }

    /**
     * @param int $sellerId
     * @return bool
     */
    public function getIsInContract($sellerId) {
        $currentDateTime = new \DateTime('now', new \DateTimezone("Asia/Kolkata"));
        $contracts = $this->contractCollection
                ->addFieldToFilter('seller_id', $sellerId);
        if ($contracts->count() > 0) {
            $contract = $contracts->getFirstItem();
            $contractStartDate = date('Y-m-d', strtotime($contract->getData('start_date')));
            $contractEndDate = date('Y-m-d', strtotime($contract->getData('end_date')));
            $currentDate = $currentDateTime->format('Y-m-d');
            if (($contractStartDate <= $currentDate) && ($currentDate <= $contractEndDate)) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    /*
     * return product status
     * @param int productId
     */

    public function getProductStatus($productId) {

        $productDetails = $this->_objectManager->create(
                        'Webkul\Marketplace\Model\Product'
                )
                ->getCollection()
                ->addFieldToSelect(array('status'))
                ->addFieldToFilter('mageproduct_id', array("eq" => $productId))
                ->getFirstItem();
        if ($productDetails->getStatus()) {
            return $productDetails->getStatus();
        } else {
            return;
        }
    }
    
    /*
     * email id for reciving disapprove product
     */
    public function getDissProEmailId() {
        return $this->scopeConfig->getValue(
                        'disapprove_product_email/email/email', \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
    
    /*
     * name for reciving disapprove product
     */
    public function getDissProName() {
        return $this->scopeConfig->getValue(
                        'disapprove_product_email/email/name', \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @param $sellerId
     * @return bool
     */
    public function isConglomerate($sellerId) {
        $sellerColl = $this->getSellerDataBySellerId($sellerId);
        if ($sellerColl->count() > 0) {
            $seller = $sellerColl->getFirstItem();
            $isConglomerate = $seller->getData('is_conglomerate');
            if ($isConglomerate == 1) {
                return true;
            }
        }
        return false;
    }


    /**
     * Return Sender Name.
     * @return mixed
     */
    public function getSalesConfigname() {
        return $this->scopeConfig->getValue(
                        'order/status/sender_name', \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }


    
    /**
     * Return Sender Name.
     * @return mixed
     */
    public function getStorename() {
        return $this->scopeConfig->getValue(
                        'general/store_information/name', \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Return Sender Name.
     * @return mixed
     */
    public function getOwnerstoreAdddress() {
        return $this->scopeConfig->getValue(
                        'general/store_information/street_line1', \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Return the seller Data.
     * @param int|null $sellerId
     * @return \Webkul\Marketplace\Model\ResourceModel\Seller\Collection
     */
    public function getInvoiceSellerData($sellerId = null) {
        if ($sellerId === null) {
            $sellerId = $this->_customerSession->getCustomerId();
        }
        $model = $this->_objectManager->create(
                        'Webkul\Marketplace\Model\Seller'
                )
                ->getCollection()
                ->addFieldToFilter(
                'seller_id', $sellerId)
                ->getLastItem()
                ;

        return $model;
    }

    /***
     * Return Invoice varriable
     * @return mixed
     */
    public function getInvoicecompanyName() {
        return $this->scopeConfig->getValue(
                        'invoice/pdf/comapny_name', \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

     /***
     * Return Invoice varriable
     * @return mixed
     */
    public function getInvoicecompanyAuthorName() {
        return $this->scopeConfig->getValue(
                        'invoice/pdf/authorised_name', \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }


    public function getBakewayGstin() {
        return $this->scopeConfig->getValue(
                        'payouts_calculation/bakeway_account_details/bakeway_pan', \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getBakewayPan() {
        return $this->scopeConfig->getValue(
                        'payouts_calculation/bakeway_account_details/bakeway_gstin', \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
  

   public function getBakewayStoreName() {
        return $this->scopeConfig->getValue(
                        'general/store_information/name', \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }


    public function getBakewayStoreAddress() {
        return $this->scopeConfig->getValue(
                        'general/store_information/street_line1', \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
  
   /*
   * seller new order alaram alert
   */
   public function getBakewaySellerorderalaramStatus() {
        return $this->scopeConfig->getValue(
                        'seller_bell/seller_bell/status', \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /*
     * email id for reciving approve product
     */
    public function getAppProEmailId() {
        return $this->scopeConfig->getValue(
            'disapprove_product_email/approveemail/email', \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /*
     * name for reciving approve product
     */
    public function getAppProName() {
        return $this->scopeConfig->getValue(
            'disapprove_product_email/approveemail/name', \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /*
   * seller disapprove sender name
   */
    public function getDisapprosellerName() {
        return $this->scopeConfig->getValue(
            'disapprove_seller_email/email/name', \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
    /*
   * nseller disapprove reciever emails
   */
    public function getDisapprosellerEmails() {
        return $this->scopeConfig->getValue(
            'disapprove_seller_email/email/email', \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Return the seller entity id from userdata table.
     *
     * @return \Webkul\Marketplace\Model\ResourceModel\Seller\Collection
     */
    public function getSellerEntityId($sellerid) {
        $model = $this->_objectManager->create(
            'Webkul\Marketplace\Model\Seller'
        )
            ->getCollection()
            ->addFieldToFilter(
                'seller_id', $sellerid)
            ->getLastItem()
        ;
        return $model;
    }


    /**
     * re-turn invoice header
     * @return mixed
     */
    public function getInvoiceStoreName() {
        return $this->scopeConfig->getValue(
            'invoice/pdf/store_name_invoice_pdf', \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @param array $orderId
     */
    public function getAllOrderConvienceFee(array $orderId)
    {
        $itemsFee = [];
        if (!empty($orderId)) {
            $collection = $this->orderCollection
                         ->addFieldToSelect("convenience_fee")
                         ->addFieldToFilter("order_id",array("in" => $orderId));
            $fees = $collection->getData('convenience_fee');
            foreach($fees as $item)
            {
                $itemsFee[] = $item['convenience_fee'];
            }
            return $itemsFee;
        }
    }

    /**
     * @param $number
     * @return string
     */
    public function displaywordsinInvoicePdf($number){
        echo $number;
        $no = round($number);
        $decimal = round($number - ($no = floor($number)), 2) * 100;
        $digits_length = strlen($no);
        $i = 0;
        $str = array();
        $words = array(
            0 => '',
            1 => 'One',
            2 => 'Two',
            3 => 'Three',
            4 => 'Four',
            5 => 'Five',
            6 => 'Six',
            7 => 'Seven',
            8 => 'Eight',
            9 => 'Nine',
            10 => 'Ten',
            11 => 'Eleven',
            12 => 'Twelve',
            13 => 'Thirteen',
            14 => 'Fourteen',
            15 => 'Fifteen',
            16 => 'Sixteen',
            17 => 'Seventeen',
            18 => 'Eighteen',
            19 => 'Nineteen',
            20 => 'Twenty',
            30 => 'Thirty',
            40 => 'Forty',
            50 => 'Fifty',
            60 => 'Sixty',
            70 => 'Seventy',
            80 => 'Eighty',
            90 => 'Ninety');
        $digits = array('', 'Hundred', 'Thousand', 'Lakh', 'Crore');
        while ($i < $digits_length) {
            $divider = ($i == 2) ? 10 : 100;
            $number = floor($no % $divider);
            $no = floor($no / $divider);
            $i += $divider == 10 ? 1 : 2;
            if ($number) {
                $plural = (($counter = count($str)) && $number > 9) ? 's' : null;
                $str [] = ($number < 21) ? $words[$number] . ' ' . $digits[$counter] . $plural : $words[floor($number / 10) * 10] . ' ' . $words[$number % 10] . ' ' . $digits[$counter] . $plural;
            } else {
                $str [] = null;
            }
        }

        $Rupees = implode(' ', array_reverse($str));
        $paise = ($decimal) ? ($words[$decimal - $decimal%10]) ." " .($words[$decimal%10]) ." paise" : '';
        return strtolower(($Rupees ?  $Rupees : '') . $paise);

    }

    /**
     * Return the seller id.
     *
     * @return \Webkul\Marketplace\Model\ResourceModel\Seller\Collection
     */
    public function getSellerId() {
        $model = $this->_objectManager->create(
            'Webkul\Marketplace\Model\Seller'
        )
            ->getCollection()
            ->addFieldToFilter(
                'seller_id', $this->_customerSession->getCustomerId()
            )
            ->addFieldToSelect('seller_id')
            ->getFirstItem();
      if(count($model) > 0){
          return $model;
      }else{
          return;
      }

    }
    
    
    
    public function getBakeryNameFromOrder($orderId)
    {
        $collection = $this->orderCollection
                     ->addFieldToSelect("entity_id");
        $collection->getSelect()->joinLeft(['mu'=>'marketplace_userdata'],'main_table.seller_id =
                mu.seller_id',array('business_name'));
        $collection->getSelect()->where('order_id=?',$orderId);
        $collection->getFirstItem();

        if(!empty($collection->getSize())){
            $businssName = [];
        foreach ($collection->getData("business_name") as $name){
            $businssName = $name["business_name"];
        }
         return $businssName;
        }
        return;

    }
}
