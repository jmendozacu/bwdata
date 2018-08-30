<?php

namespace Bakeway\Import\Model;

use Bakeway\Import\Api\CatalogFeedRepositoryInterface;
use Magento\Framework\Exception\NotFoundException;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Symfony\Component\Config\Definition\Exception\Exception;
use Webkul\Marketplace\Model\Product as VendorProduct;
use Magento\Catalog\Helper\ImageFactory as ProductImageHelper;
use Magento\Catalog\Model\Product\Visibility as CatalogVisibility;
use \Magento\Framework\ObjectManagerInterface;
use Bakeway\Import\Model\CatalogfeedFactory as CatalogfeedFactory;


class CatalogFeedRepository implements CatalogFeedRepositoryInterface {

    const CITY_ID = 1;

    const FRONTEND_BASE_URL = "https://bakeway.com/";

    const MAX_DOWNLOADS_LIMITS = 9;

    /**
     * @var Importhelper
     */
    protected $importHelper;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */

    protected $scopeConfig;

    /**
     * @var VendorProduct
     */
    protected $vendorProduct;

    /**
     * @var ProductCollection
     */
    protected $productCollection;

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
     * @var CatalogVisibility
     */
    protected $catalogVisibility;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $_date;

    /**
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var \Bakeway\ProductApi\Helper\Data
     */
    protected $productApiHelper;

    /**
     * @var  \Magento\Framework\ObjectManagerInterface
     */
    protected $_storeManager;

    /**
     * @var \Magento\Store\Model\App\Emulation
     */
    protected $appEmulation;

    /**
     * @var \Magento\CatalogInventory\Model\Stock\StockItemRepository
     */
    protected $stockItemRepository;

    /**
     * @var \Bakeway\Import\Model\CatalogfeedFactory
     */
    protected $catalogfeedFactory;

    /**
     * CatalogFeedRepository constructor.
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param ProductCollection $productCollection
     * @param VendorProduct $vendorProduct
     * @param \Magento\Catalog\Api\ProductAttributeRepositoryInterface $metadataServiceInterface
     * @param \Magento\Framework\Api\ExtensionAttribute\JoinProcessorInterface $extensionAttributesJoinProcessor
     * @param ProductImageHelper $productImageHelper
     * @param CatalogVisibility $catalogVisibility
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $date
     * @param ObjectManagerInterface $objectManager
     * @param \Bakeway\ProductApi\Helper\Data $productApiHelper
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Store\Model\App\Emulation $appEmulation
     * @param \Magento\CatalogInventory\Model\Stock\StockItemRepository $stockItemRepository
     * @param \Bakeway\Import\Model\CatalogfeedFactory $catalogfeedFactory
     */

    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        ProductCollection $productCollection,
        VendorProduct $vendorProduct,
        \Magento\Catalog\Api\ProductAttributeRepositoryInterface $metadataServiceInterface,
        \Magento\Framework\Api\ExtensionAttribute\JoinProcessorInterface $extensionAttributesJoinProcessor,
        ProductImageHelper $productImageHelper,
        CatalogVisibility $catalogVisibility,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        ObjectManagerInterface $objectManager,
        \Bakeway\ProductApi\Helper\Data $productApiHelper,
       \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Store\Model\App\Emulation $appEmulation,
        \Magento\CatalogInventory\Model\Stock\StockItemRepository $stockItemRepository,
        CatalogfeedFactory $catalogfeedFactory

    ) {
        $this->scopeConfig = $scopeConfig;
        $this->productCollection = $productCollection;
        $this->vendorProduct = $vendorProduct;
        $this->metadataService = $metadataServiceInterface;
        $this->extensionAttributesJoinProcessor = $extensionAttributesJoinProcessor;
        $this->productImageHelper = $productImageHelper;
        $this->catalogVisibility = $catalogVisibility;
        $this->_date = $date;
        $this->objectManager =$objectManager;
        $this->productApiHelper = $productApiHelper;
        $this->_storeManager = $storeManager;
        $this->appEmulation = $appEmulation;
        $this->stockItemRepository =$stockItemRepository;
        $this->catalogfeedFactory=$catalogfeedFactory;

    }

    /**
     * Catalog Feed Api.
     *
     * @api
     * @param string $access_token
     * @return array
     * @throws \Magento\Framework\Exception\NotFoundException
     */
    public function getCatalogFeed($access_token){


        if(empty($access_token)){
            throw new  NotFoundException(__("access token is required field."));
        }

        $catalogFeeStatus = $this->getCatalogFeedStatus();

        if(empty($catalogFeeStatus)){
          throw new  NotFoundException(__("This Feature is disabled now."));
        }

        $catalogFeeAccessToken = $this->getCatalogFeedAccessToken();

        if($catalogFeeAccessToken != $access_token){
            throw new  NotFoundException(__("Access token is wrong"));
        }

        /**
         * update bakeway catalog feed table
         */
        $currentDate = $this->_date->gmtDate('Y-m-d');

        $this->getCurrentDateFeedCount($currentDate);

        $this->getCurrentDateFeedDownloads($currentDate);

        $cityId = self::CITY_ID;
        $date = $this->_date->gmtDate('d-m-Y');
        $storeProdCollection = $this->vendorProduct->getCollection()
            ->addFieldToFilter('status', 1)
            ->addFieldToSelect(['mageproduct_id','seller_id']);
        $storeProdCollection->getSelect()->joinLeft(
            ['mp_udata' => $storeProdCollection->getTable('marketplace_userdata')],
            'main_table.seller_id=mp_udata.seller_id',
            ['business_name','is_conglomerate', 'store_city']
        );
        $storeProdCollection->getSelect()->where('mp_udata.is_seller=?', 1);
        $storeProdCollection->getSelect()->where('mp_udata.is_live_ready=?', 1);
        $storeProdCollection->getSelect()->where('mp_udata.business_name!=?', null);
        $storeProdCollection->getSelect()->group('mageproduct_id');
        //$storeProdCollection->getSelect()->where('mp_udata.seller_id IN (640, 1866)');
        $storeProductIDs = $storeProdCollection->getAllIds();
        //echo "<pre>";print_r($storeProductIDs);exit;
        $storeProductArr = [];
        foreach ($storeProdCollection as $storeProd) {
            $mageProdId = $storeProd->getData('mageproduct_id');
            $storeProductArr[$mageProdId]['business_name'] = $storeProd->getData('business_name');
            $storeProductArr[$mageProdId]['is_conglomerate'] = $storeProd->getData('is_conglomerate');
            $storeProductArr[$mageProdId]['store_city'] = $storeProd->getData('store_city');
        }

        $collection = $this->productCollection
            ->addFieldToFilter(
                'entity_id', ['in' => $storeProductIDs]
            )->addAttributeToSelect('*');
       // $collection->getSelect()->limit(10);
        $collection->setVisibility($this->catalogVisibility->getVisibleInSiteIds());
        $this->extensionAttributesJoinProcessor->process($collection);
        $collection->joinAttribute('status', 'catalog_product/status', 'entity_id', null, 'inner');
        $collection->joinAttribute('visibility', 'catalog_product/visibility', 'entity_id', null, 'inner');
        $collection->addCategoriesFilter(['in'=>(13)]);
        //echo $collection->getSelect();exit;
        $collection->load();
        $collection = $this->addBakewayUrlRewrite($collection);
        //echo "<pre>";print_r($collection->getSelect());exit;
        $storeId = $this->_storeManager->getStore()->getId();
        $this->appEmulation->startEnvironmentEmulation($storeId, \Magento\Framework\App\Area::AREA_FRONTEND, true);
        $i = 0;
        $feedOutput = [];
        foreach ($collection as $product) {
            $productName = $product->getName();
            $productSku = $product->getSku();
            $productId = $product->getEntityId();
            $proCats = $product->getCategoryIds();
            $categoryJson = $this->productApiHelper->getCategoryName($proCats);
            $imageUrl = $this->getImageUrl($product, 'product_page_image_large');
            $isCongloSellerProduct = $storeProductArr[$productId]['is_conglomerate'];
            $businessName = $storeProductArr[$productId]['business_name'];
            $storeCity = $storeProductArr[$productId]['store_city'];
            $prodUrl = $this->getProductSeoUrl($product->getData('request_path'), $product->getId());
            $seoProdUrl = self::FRONTEND_BASE_URL.$prodUrl;
            $description = $product->getDescription();
            $shortDescription = $product->getShortDescription();
            if ($isCongloSellerProduct == 1) {
                if ($storeCity == 1) {
                    $seoProdUrl = $seoProdUrl."?store=all-pune";
                } else {
                    $seoProdUrl = $seoProdUrl."?store=all-bangalore";
                }
            }
            $sku = $product->getData('sku');
            $typeId = $product->getTypeId();
            $weight = null;
            $advanceIntimation = null;
            $ruleTaxPrice = 0;
            if ($typeId == 'simple') {
                $weight = $product->getAttributeText("cake_weight");
                $advanceIntimation = $product->getData('advance_order_intimation');
                $productPrice = number_format($this->productApiHelper->getProductTaxPrice($product, null, null, false, null, $cityId), 2);
                $rulePrice = $this->productApiHelper->getCatalogRulePrice($product->getId());
                $ruleTaxPrice = $this->productApiHelper->getCatalogRuleTaxPrice($product, $rulePrice);
            } elseif ($typeId == 'configurable') {
                $minimumPrice = $this->productApiHelper->getMinproductPrice($product, null, null, $cityId);
                $productPrice = $minimumPrice['tax_incl_price'];
                if (empty($productPrice)) {
                    $productPrice = '0.00';
                }
                if (isset($minimumPrice['simple_prod_obj']) && $minimumPrice['simple_prod_obj'] !== null) {
                    $weight = $minimumPrice['simple_prod_obj']->getAttributeText("cake_weight");
                    $advanceIntimation = $minimumPrice['simple_prod_obj']->getData('advance_order_intimation');
                }
                $rulePrice = $this->productApiHelper->getCatalogRulePrice($minimumPrice['product_id']);

                if ($minimumPrice['simple_prod_obj'] !== null) {
                    $ruleTaxPrice = $this->productApiHelper->getCatalogRuleTaxPrice($minimumPrice['simple_prod_obj'], $rulePrice);
                } else {
                    $ruleTaxPrice = 0;
                }
            }

            $feedOutput[$i]["product_name"] = $productName;
            $feedOutput[$i]["product_sku"] = $productSku;
            $feedOutput[$i]["product_image_url"] = $imageUrl;
            $feedOutput[$i]["product_page_url"] = $seoProdUrl;
            $feedOutput[$i]["product_price"] = $productPrice;
            $feedOutput[$i]["product_discount_price"] = $ruleTaxPrice;
            $feedOutput[$i]["product_category"] = $categoryJson;

            $i++;
        }
        return $feedOutput;
    }


    /**
     * @return mixed
     */

    public  function getCatalogFeedStatus(){
        return $this->scopeConfig->getValue(
            "catalog_feed/catalog_feed/status", \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @return mixed
     */
    public  function getCatalogFeedAccessToken(){
        return $this->scopeConfig->getValue(
            "catalog_feed/catalog_feed/token", \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }


    /**
     * Helper function that provides full cache image url
     * @param \Magento\Catalog\Model\Product
     * @return string
     */
    protected function getImageUrl($product, string $imageType = '') {
        $imageUrl = $this->productImageHelper->create()->init($product, $imageType)->keepAspectRatio(TRUE)->keepFrame(FALSE)->resize(290, 290)->getUrl();
        return $imageUrl;
    }

    /**
     * @param $collection
     * @return mixed
     */
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

    /**
     * @param $url
     * @param $productId
     * @return mixed
     */
    public function getProductSeoUrl($url, $productId) {
        if ($url !== null) {
            $sellerCity = $this->productApiHelper->getSellerCity($productId);
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


    /**
     * @param $date
     * @return count
     */
    public function getCurrentDateFeedCount($date){

       $collection =  $this->catalogfeedFactory->create()->getCollection()
                      ->addFieldToFilter("date", $date)
                      ->getFirstItem();

        if(!empty($collection)){
            return $collection['usage_count'];
        }
        return;
    }


    /**
     * @param $date
     */
    public function getCurrentDateFeedDownloads($date){
        $downloadsCount = $this->getCurrentDateFeedCount($date);

        if($downloadsCount == self::MAX_DOWNLOADS_LIMITS){
          throw new NotFoundException(__("You have reached to maximum downloads limit of today, Please try tomorrow !!!"));
        }

        $collection =  $this->catalogfeedFactory->create();
        $entityId = $this->getEnityIdOfFeedRow($date);
        if(!empty($entityId)){
            $existingRecords =$this->catalogfeedFactory->create()->load($entityId);
            $existingRecords->setUsageCount($downloadsCount+1);
            $existingRecords->setDate($date);
            try{
                $existingRecords->save();
            }catch (Exception $e)
            {
                echo $e->getMessage();
            }

        }else{
            $collection->setUsageCount($downloadsCount);
            $collection->setDate($date);
            try{
                $collection->save();
            }catch (Exception $e)
            {
                echo $e->getMessage();
            }
        }

    }


    public function getEnityIdOfFeedRow($date)
    {
        $collection =  $this->catalogfeedFactory->create()->getCollection()
                        ->addFieldToFilter("date", $date)
                        ->getFirstItem();
       if(!empty($collection)){
           return $collection['entity_id'];
       }
      return;
    }

}
