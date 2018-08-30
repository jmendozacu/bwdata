<?php
/**
 *
 * Copyright © 2015 Bakewaycommerce. All rights reserved.
 */
namespace Bakeway\Import\Controller\Adminhtml\Export;

use Braintree\Exception;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\Controller\ResultFactory;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Webkul\Marketplace\Model\Product as VendorProduct;
use Magento\Catalog\Helper\ImageFactory as ProductImageHelper;
use Magento\Catalog\Model\Product\Visibility as CatalogVisibility;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\App\Filesystem\DirectoryList;

class Save extends \Magento\Backend\App\Action
{
    const FRONTEND_BASE_URL = "https://bakeway.com/";

    const CITY_ID = 1;
    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var  \Magento\Framework\ObjectManagerInterface
     */
    protected $_storeManager;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $_date;

    /**
     * @var \Bakeway\Import\Helper\Data
     */
    protected $importHelper;

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
     * @var \PHPExcel
     */
    protected $excelParser;

    /**
     * @var \Bakeway\ProductApi\Helper\Data
     */
    protected $productApiHelper;

    /**
     * @var FileFactory
     */
    protected $downloader;

    /**
     * @var \Magento\Framework\Filesystem
     */
    protected $fileSystem;

    /**
     * @var \Magento\Framework\Filesystem\DirectoryList
     */
    protected $directoryList;

    /**
     * @var \Magento\Store\Model\App\Emulation
     */
    protected $appEmulation;

    /**
     * Save constructor.
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $date
     * @param \Bakeway\Import\Helper\Data $importHelper
     * @param ProductCollection $productCollection
     * @param VendorProduct $vendorProduct
     * @param \Magento\Catalog\Api\ProductAttributeRepositoryInterface $metadataServiceInterface
     * @param \Magento\Framework\Api\ExtensionAttribute\JoinProcessorInterface $extensionAttributesJoinProcessor
     * @param ProductImageHelper $productImageHelper
     * @param CatalogVisibility $catalogVisibility
     * @param \PHPExcel $excel
     * @param \Bakeway\ProductApi\Helper\Data $productApiHelper
     * @param \Magento\Framework\App\Response\Http\FileFactory $fileFactory
     * @param \Magento\Framework\Filesystem $fileSystem
     * @param \Magento\Framework\Filesystem\DirectoryList $directoryList
     * @param \Magento\Store\Model\App\Emulation $appEmulation
     */

    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        \Bakeway\Import\Helper\Data $importHelper,
        ProductCollection $productCollection,
        VendorProduct $vendorProduct,
        \Magento\Catalog\Api\ProductAttributeRepositoryInterface $metadataServiceInterface,
        \Magento\Framework\Api\ExtensionAttribute\JoinProcessorInterface $extensionAttributesJoinProcessor,
        ProductImageHelper $productImageHelper,
        CatalogVisibility $catalogVisibility,
        \PHPExcel $excel,
        \Bakeway\ProductApi\Helper\Data $productApiHelper,
        \Magento\Framework\App\Response\Http\FileFactory $fileFactory,
        \Magento\Framework\Filesystem $fileSystem,
        \Magento\Framework\Filesystem\DirectoryList $directoryList,
        \Magento\Store\Model\App\Emulation $appEmulation
    )
    {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->_storeManager = $storeManager;
        $this->_date = $date;
        $this->importHelper = $importHelper;
        $this->productCollection = $productCollection;
        $this->vendorProduct = $vendorProduct;
        $this->metadataService = $metadataServiceInterface;
        $this->extensionAttributesJoinProcessor = $extensionAttributesJoinProcessor;
        $this->productImageHelper = $productImageHelper;
        $this->catalogVisibility = $catalogVisibility;
        $this->excelParser = $excel;
        $this->productApiHelper = $productApiHelper;
        $this->downloader = $fileFactory;
        $this->fileSystem = $fileSystem;
        $this->directoryList = $directoryList;
        $this->appEmulation = $appEmulation;
    }

    /**
     * @return get base url
     */
    public function getBaseUrl()
    {
        return $this->_storeManager->getStore()->getBaseUrl();
    }

    /**
     * Index action
     *
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {
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
        $storeProdCollection->getSelect()->where('mp_udata.seller_id NOT IN (4430, 1374)');
        $storeProdCollection->getSelect()->group('mageproduct_id');
        $storeProductIDs = $storeProdCollection->getAllIds();
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
        $collection->setVisibility($this->catalogVisibility->getVisibleInSiteIds());
        $this->extensionAttributesJoinProcessor->process($collection);
        $collection->joinAttribute('status', 'catalog_product/status', 'entity_id', null, 'inner');
        $collection->joinAttribute('visibility', 'catalog_product/visibility', 'entity_id', null, 'inner');
        $collection->addCategoriesFilter(['in'=>(13)]);
        $collection->load();
        $collection = $this->addBakewayUrlRewrite($collection);

        $storeId = $this->_storeManager->getStore()->getId();
        $this->appEmulation->startEnvironmentEmulation($storeId, \Magento\Framework\App\Area::AREA_FRONTEND, true);
        $i = 1;
        /**
         * Setting the header for the sheet
         */
        $this->excelParser->setActiveSheetIndex(0)
            ->setCellValueExplicit('A' . $i, 'id', \PHPExcel_Cell_DataType::TYPE_STRING)
            ->setCellValueExplicit('B' . $i, 'title', \PHPExcel_Cell_DataType::TYPE_STRING)
            ->setCellValueExplicit('C' . $i, 'description', \PHPExcel_Cell_DataType::TYPE_STRING)
            ->setCellValueExplicit('D' . $i, 'short description', \PHPExcel_Cell_DataType::TYPE_STRING)
            ->setCellValueExplicit('E' . $i, 'shipping weight', \PHPExcel_Cell_DataType::TYPE_STRING)
            ->setCellValueExplicit('F' . $i, 'custom label 1', \PHPExcel_Cell_DataType::TYPE_STRING)
            ->setCellValueExplicit('G' . $i, 'link', \PHPExcel_Cell_DataType::TYPE_STRING)
            ->setCellValueExplicit('H' . $i, 'image link', \PHPExcel_Cell_DataType::TYPE_STRING)
            ->setCellValueExplicit('I' . $i, 'price', \PHPExcel_Cell_DataType::TYPE_STRING)
            ->setCellValueExplicit('J' . $i, 'brand', \PHPExcel_Cell_DataType::TYPE_STRING)
            ->setCellValueExplicit('K' . $i, 'availability', \PHPExcel_Cell_DataType::TYPE_STRING)
            ->setCellValueExplicit('L' . $i, 'google product category -', \PHPExcel_Cell_DataType::TYPE_STRING)
            ->setCellValueExplicit('M' . $i, 'City 1-Pune 3-Bangalore', \PHPExcel_Cell_DataType::TYPE_STRING);
        $i++;
        foreach ($collection as $product) {
            $productName = $product->getName();
            $productId = $product->getEntityId();
            $imageUrl = $this->getImageUrl($product, 'product_page_image_large');
            $isCongloSellerProduct = $storeProductArr[$productId]['is_conglomerate'];
            $businessName = $storeProductArr[$productId]['business_name'];
            $storeCity = $storeProductArr[$productId]['store_city'];
            $prodUrl = $this->getProductSeoUrl($product->getData('request_path'), $product->getId());
            $seoProdUrl = self::FRONTEND_BASE_URL.$prodUrl;
            $description = strip_tags($product->getDescription());
            $shortDescription = strip_tags($product->getShortDescription());
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
            if ($ruleTaxPrice > 0) {
                $productPrice = $ruleTaxPrice;
            }
            $this->excelParser->getSheet()
                ->setCellValueExplicit('A' . $i, $sku, \PHPExcel_Cell_DataType::TYPE_STRING)
                ->setCellValueExplicit('B' . $i, $productName, \PHPExcel_Cell_DataType::TYPE_STRING)
                ->setCellValueExplicit('C' . $i, $description, \PHPExcel_Cell_DataType::TYPE_STRING)
                ->setCellValueExplicit('D' . $i, $shortDescription, \PHPExcel_Cell_DataType::TYPE_STRING)
                ->setCellValueExplicit('E' . $i, $weight, \PHPExcel_Cell_DataType::TYPE_STRING)
                ->setCellValueExplicit('F' . $i, $advanceIntimation, \PHPExcel_Cell_DataType::TYPE_STRING)
                ->setCellValueExplicit('G' . $i, $seoProdUrl, \PHPExcel_Cell_DataType::TYPE_STRING)
                ->setCellValueExplicit('H' . $i, $imageUrl, \PHPExcel_Cell_DataType::TYPE_STRING)
                ->setCellValueExplicit('I' . $i, "INR ".$productPrice, \PHPExcel_Cell_DataType::TYPE_STRING)
                ->setCellValueExplicit('J' . $i, $businessName, \PHPExcel_Cell_DataType::TYPE_STRING)
                ->setCellValueExplicit('K' . $i, 'in stock', \PHPExcel_Cell_DataType::TYPE_STRING)
                ->setCellValueExplicit('L' . $i, 'Food, Beverages & Tobacco > Food Items > Bakery > Cakes & Dessert Bars', \PHPExcel_Cell_DataType::TYPE_STRING)
                ->setCellValueExplicit('M' . $i, $storeCity, \PHPExcel_Cell_DataType::TYPE_STRING);
            $i++;
        }
        $fileName = "product_feeds_".$date.'_'.time().'.xls';
        $fileObj = $this->fileSystem->getDirectoryWrite(DirectoryList::MEDIA)->create('/productfeeds/'.$date.'/');
        $path = $this->fileSystem->getDirectoryWrite(DirectoryList::MEDIA)
            ->getAbsolutePath('/productfeeds/'.$date.'/'.$fileName) ;
        $excelWriter = new \PHPExcel_Writer_Excel2007($this->excelParser);
        $excelWriter->save($path);

        $file = $this->directoryList->getPath("media")."/productfeeds/".$fileName;

        header('Content-Type: application/vnd.ms-excel');
        header("Content-Transfer-Encoding: Binary");
        header("Content-disposition: attachment; filename=\"" . basename($path) . "\"");
        ob_clean();flush();
        readfile($path);
        exit;
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

    protected function addBakewayUrlRewrite($collection)
    {
        $productIds = [];
        foreach ($collection as $item) {
            $productIds[] = $item->getEntityId();
        }
        if (!$productIds) {
            return $collection;
        }

        $urlCollectionData = $this->_objectManager
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
}
