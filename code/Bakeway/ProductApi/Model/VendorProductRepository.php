<?php

namespace Bakeway\ProductApi\Model;

use Bakeway\ProductApi\Api\VendorProductRepositoryInterface;
use Webkul\Marketplace\Helper\Data as SellerHelper;
use Magento\Catalog\Helper\ImageFactory as ProductImageHelper;
use Magento\Framework\Api\ExtensionAttribute\JoinProcessorInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Customer\Api\GroupManagementInterface;
use Magento\Customer\Model\Session as CustomerModelSession;
use Webkul\Marketplace\Model\Product as VendorProduct;
use Bakeway\ProductApi\Helper\Filter as ProductFilterHelper;

class VendorProductRepository implements VendorProductRepositoryInterface {

    const PROD_FILTER_ATTR = ['cake_weight','cake_flavour','cake_ingredients'];
    
    /**
     * @var CustomerModelSession
     */
    protected $customerSession;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $localeDate;

    /**
     * @var sellerHelper
     */
    protected $sellerHelper;

    /**
     * @var $sellerFactory
     */
    protected $sellerFactory;

    /**
     * @param deliveryrangeHelper
     */
    protected $productapihelper;

    /**
     * @param productRepository
     */
    protected $productRepository;

    /**
     * @var \Magento\Catalog\Helper\ImageFactory
     */
    protected $productImageHelper;

    /**
     * @var \Magento\Store\Model\App\Emulation
     */
    protected $appEmulation;

    /**
     * @var ProductFactory
     */
    protected $productFactory;

    /**
     * @var JoinProcessorInterface
     */
    private $extensionAttributesJoinProcessor;

    /**
     * @var \Magento\Catalog\Helper\Data
     */
    protected $_catalogHelper;

    /**
     * @var VendorProduct
     */
    protected $vendorProduct;

    /**
     * @var ProductFilterHelper
     */
    protected $productFilterHelper;

    /**
     * VendorProductRepository constructor.
     * @param SellerHelper $sellerHelper
     * @param \Webkul\Marketplace\Model\SellerFactory $sellerFactory
     * @param \Bakeway\ProductApi\Helper\Data $productapiHelper
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param ProductImageHelper $productImageHelper
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Store\Model\App\Emulation $appEmulation
     * @param \Magento\Catalog\Model\ProductFactory $productFactory
     * @param JoinProcessorInterface $extensionAttributesJoinProcessor
     * @param \Magento\Catalog\Helper\Data $catalogData
     * @param VendorProduct $vendorProduct
     * @param ProductFilterHelper $productFilterHelper
     */
    public function __construct(
        SellerHelper $sellerHelper,
        \Webkul\Marketplace\Model\SellerFactory $sellerFactory,
        \Bakeway\ProductApi\Helper\Data $productapiHelper,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        ProductImageHelper $productImageHelper,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Store\Model\App\Emulation $appEmulation,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        JoinProcessorInterface $extensionAttributesJoinProcessor,
        \Magento\Catalog\Helper\Data $catalogData,
        VendorProduct $vendorProduct,
        ProductFilterHelper $productFilterHelper
    ) {
        $this->sellerHelper = $sellerHelper;
        $this->sellerFactory = $sellerFactory;
        $this->productapihelper = $productapiHelper;
        $this->productRepository = $productRepository;
        $this->productImageHelper = $productImageHelper;
        $this->storeManager = $storeManager;
        $this->appEmulation = $appEmulation;
        $this->productFactory = $productFactory;
        $this->extensionAttributesJoinProcessor = $extensionAttributesJoinProcessor;
        $this->_catalogHelper = $catalogData;
        $this->vendorProduct = $vendorProduct;
        $this->productFilterHelper = $productFilterHelper;
    }

    /**
     * Get vendor Product details
     * @api
     * @param int $id
     * @param string $sku
     * @return \Magento\Catalog\Api\Data\ProductInterface
     * @throws \Magento\Framework\Exception\NotFoundException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getProductsdetails($id, $sku) {
        $isLiveReady = $this->sellerHelper->getIsLiveReady($id);
        if ($isLiveReady === false) {
            throw new LocalizedException(__('Bakery is not live ready'));
        }

        $_ProductArray = $_ProductApiOp = $_ProductApiMedia = [];
        /*
         * load product from sku
         */
        $_Model = $this->productRepository->get($sku);

        $status = $_Model->getStatus();

        if ($status != 1) {
            throw new LocalizedException(__('Requested product is disabled in system.'));
        }

        $_ProductArray['type_id'] = $_Model->getTypeId();
        switch ($_ProductArray['type_id']):

            /*
             * case for simple product return array
             */
            case "simple":

                $ExtensionAttObject = $_Model->getExtensionAttributes();
                $_DeliveryStatus = $this->getSellerDeliveryStatus($_Model->getId());
                /*
                 * get and set product attributes value
                 */

                $_CfLabel = $this->getAttributeLabel($_Model, 'cake_flavour');
                $_CiLabel = $this->getAttributeLabel($_Model, 'cake_ingredients');
                $_CwLabel = $this->getAttributeLabel($_Model, 'cake_weight');


                $_ProductApiOp['flavour'] = array("label" => $_CfLabel, "values" => array($_Model->getAttributeText("cake_flavour")));
                $_ProductApiOp['ingredients'] = array("label" => $_CiLabel, "values" => array($_Model->getAttributeText("cake_ingredients")));
                $_ProductApiOp['cake_weight'] = array("label" => $_CwLabel, "values" => array($_Model->getAttributeText("cake_weight")));
                $_ProductAtttibute = $ExtensionAttObject->setProductAttributes($_ProductApiOp);
                /*
                 * get and set price
                 */
                $rulePrice = $this->productapihelper->getCatalogRulePrice($_Model->getId());
                $ruleTaxPrice = $this->productapihelper->getCatalogRuleTaxPrice($_Model, $rulePrice);
                if ($rulePrice && $ruleTaxPrice) {
                    $ExtensionAttObject->setCatalogDiscountPrice($rulePrice);
                    $ExtensionAttObject->setCatalogDiscountPriceInclTax($ruleTaxPrice);
                } else {
                    $ExtensionAttObject->setCatalogDiscountPrice("0.00");
                    $ExtensionAttObject->setCatalogDiscountPriceInclTax("0.00");
                }
                if ($_Model->getSpecialPrice()) {

                    $_ProductPriceSP = $ExtensionAttObject->setSpecialPrice($this->productapihelper->setDecimalPrice($_Model->getSpecialPrice()));
                } else {
                    $_ProductPriceSP = $ExtensionAttObject->setSpecialPrice("0.00");
                }

                if ($_Model->getSpecialPrice()) {

                $priceWithoutTaxNotRound = $_Model->getPriceInfo()->getPrice('special_price')->getAmount()->getValue();
                } else {
                $priceWithoutTaxNotRound = $_Model->getPriceInfo()->getPrice('regular_price')->getAmount()->getValue();
                }
                
                $_ExclTaxprice = $this->productapihelper->getProductTaxPrice($_Model);

                if (!empty($priceWithoutTaxNotRound)) {
                    $_ProductPriceExTax = $ExtensionAttObject->setPriceExclTax($priceWithoutTaxNotRound);
                } else {
                    $_ProductPriceExTax = $ExtensionAttObject->setPriceExclTax('0.00');
                }

                if (!empty($_ExclTaxprice)) {
                    $_ProductPriceInTax = $ExtensionAttObject->setPriceInclTax($_ExclTaxprice);
                } else {
                    $_ProductPriceInTax = $ExtensionAttObject->setPriceInclTax('0.00');
                }

                /**
                 * set specific discount dates
                 */
                $ruleData = $this->productapihelper->getRuleDataByProduct($_Model->getId());

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
                $ExtensionAttObject->setDiscountRuleStartDate($discountRuleStartDate);
                $ExtensionAttObject->setDiscountRuleEndDate($discountRuleEndDate);

                /**
                 * Set Product SEO Fields
                 */
                $_Model = $this->setProductSeoField($_Model);

                /*
                 * get and set product description and short_description
                 */
                if ($_Model->getDescription()) {
                    $_ProductDes = $ExtensionAttObject->setDescription($_Model->getDescription());
                } else {
                    $_ProductDes = $ExtensionAttObject->setDescription('null');
                }
                if ($_Model->getShortDescription()) {
                    $_ProductSdes = $ExtensionAttObject->setShortDescription($_Model->getShortDescription());
                } else {
                    $_ProductSdes = $ExtensionAttObject->setShortDescription('null');
                }

                /**
                 * get and set advanced order time unit attributes value
                 */
                if ($_Model->getAdvanceOrderIntimationUnit()) {
                    $productIntimationUnit = $ExtensionAttObject->setAdvancedOrderIntimationUnit($_Model->getAdvanceOrderIntimationUnit());
                } else {
                    $productIntimationUnit = $ExtensionAttObject->setAdvancedOrderIntimationUnit('null');
                }

                /*
                 * get and et product categories
                 */
                $_ProCats = $_Model->getCategoryIds();
                $_CategoryJson = $this->productapihelper->getCategoryName($_ProCats);
                if (!empty($_CategoryJson)) {
                    $_ProductSdes = $ExtensionAttObject->setCategories($_CategoryJson);
                } else {
                    $_ProductSdes = $ExtensionAttObject->setCategories('null');
                }

                /*
                 * get and set media product images
                 */
                $_ProductApiMedia['media'] = array('small' => $this->getImageUrl($_Model, 'product_small_image'),
                    'large' => $this->getImageUrl($_Model, 'product_page_image_large'),
                    'thumb' => $this->getImageUrl($_Model, 'product_thumbnail_image'));

                $_ProductMedia = $ExtensionAttObject->setMedia($_ProductApiMedia);

                return $_Model;

                break;

            /*
             * case for configurable product return array
             */
            case "configurable":
                $product = $this->productFactory->create();
                $product->load($_Model->getId());
                $ExtensionAttObject = $product->getExtensionAttributes();
                /*
                 * get and set price
                 */
                if ($_Model->getSpecialPrice()) {
                    $_ProductPriceSP = $ExtensionAttObject->setSpecialPrice($_Model->getSpecialPrice());
                } else {
                    $_ProductPriceSP = $ExtensionAttObject->setSpecialPrice('0.00');
                }

                $priceWithoutTaxNotRound = number_format($_Model->getPriceInfo()->getPrice('regular_price')->getAmount()->getValue(), 4);
                /*calculaion min price of children products of configurable product*/
                $_minTaxprice =  $this->productapihelper->getMinproductPrice($_Model);
                $_exclTaxprice = $_minTaxprice['min_price'];
                $_inclTaxprice = $this->productapihelper->getMinProductTaxPrice($_minTaxprice['product_id']);
                if (!empty($_exclTaxprice)) {
                    $_ProductPriceExTax = $ExtensionAttObject->setPriceExclTax($_exclTaxprice);
                } else {
                    $_ProductPriceExTax = $ExtensionAttObject->setPriceExclTax('0.00');
                }
                if (!empty($_inclTaxprice)) {
                    $_ProductPriceInTax = $ExtensionAttObject->setPriceInclTax($_inclTaxprice);
                } else {
                    $_ProductPriceInTax = $ExtensionAttObject->setPriceInclTax('0.00');
                }

                /**
                 * Set Product SEO Fields
                 */
                $product = $this->setProductSeoField($product);

                /*
                 * get and set product description and short_description
                 */
                if ($_Model->getDescription()) {
                    $_ProductDes = $ExtensionAttObject->setDescription($_Model->getDescription());
                } else {
                    $_ProductDes = $ExtensionAttObject->setDescription('null');
                }
                if ($_Model->getShortDescription()) {
                    $_ProductSdes = $ExtensionAttObject->setShortDescription($_Model->getShortDescription());
                } else {
                    $_ProductSdes = $ExtensionAttObject->setShortDescription('null');
                }

                /**
                 * get and set advanced order time unit attributes value
                 */
                if ($_Model->getAdvanceOrderIntimationUnit()) {
                    $productIntimationUnit = $ExtensionAttObject->setAdvancedOrderIntimationUnit($_Model->getAdvanceOrderIntimationUnit());
                } else {
                    $productIntimationUnit = $ExtensionAttObject->setAdvancedOrderIntimationUnit('null');
                }

                /*
                 * get and et product categories
                 */
                $_ProCats = $_Model->getCategoryIds();
                $_CategoryJson = $this->productapihelper->getCategoryName($_ProCats);
                if (!empty($_CategoryJson)) {
                    $_ProductSdes = $ExtensionAttObject->setCategories($_CategoryJson);
                } else {
                    $_ProductSdes = $ExtensionAttObject->setCategories('null');
                }
                
                /*
                 * get and set media product images
                 */
                $_ProductApiMedia['media'] = array('small' => $this->getImageUrl($_Model, 'product_small_image'),
                    'large' => $this->getImageUrl($_Model, 'product_page_image_large'),
                    'thumb' => $this->getImageUrl($_Model, 'product_thumbnail_image'));

                $_ProductMedia = $ExtensionAttObject->setMedia($_ProductApiMedia);
                /*
                 * get children sku of parent product
                 */
                $typeInstance = $_Model->getTypeInstance();
                $attributeCollection = $typeInstance->getConfigurableAttributeCollection($_Model);
                $this->extensionAttributesJoinProcessor->process($attributeCollection);

                $_skus = $this->productapihelper->getConfigurableProperties($_Model);

                $_ProductChildSku = $ExtensionAttObject->setConfigurableProductProperties(array($_skus));

                return $product;
                break;
            /*
             * case when product dosent exist
             */
            default:
                $_ProductArray = "";

        endswitch;


        return;
    }

    /**
     * @pro_id
     * return seller name
     */
    public function getSellerName($pro_id) {

        return $this->productapihelper->getSellername($pro_id);
    }

    /**
     * Helper function that provides full cache image url
     * @param \Magento\Catalog\Model\Product
     * @return string
     */
    protected function getImageUrl($product, string $imageType = '') {
        $storeId = $this->storeManager->getStore()->getId();

        $this->appEmulation->startEnvironmentEmulation($storeId, \Magento\Framework\App\Area::AREA_FRONTEND, true);
        $imageUrl = $this->productImageHelper->create()->init($product, $imageType)->getUrl();

        $this->appEmulation->stopEnvironmentEmulation();

        return $imageUrl;
    }

    /*
     * return delivery status for seller
     */

    public function getSellerDeliveryStatus($pro_id) {
        return $this->productapihelper->getSellerDeliveryStatus($pro_id);
    }

    /*
     * return attribute label name from attribute code
     */

    public function getAttributeLabel($product, $code) {
        $_AttData = $product->getResource()->getAttribute($code)->getStoreLabel();
        return $_AttData;
    }

    public function setProductSeoField($product) {
        $ExtensionAttObject = $product->getExtensionAttributes();
        if ($product->getMetaTitle()) {
            $ExtensionAttObject->setMetaTitle($product->getMetaTitle());
        } else {
            $ExtensionAttObject->setMetaTitle('null');
        }
        if ($product->getMetaKeyword()) {
            $ExtensionAttObject->setMetaKeyword($product->getMetaKeyword());
        } else {
            $ExtensionAttObject->setMetaKeyword('null');
        }
        if ($product->getMetaDescription()) {
            $ExtensionAttObject->setMetaDescription($product->getMetaDescription());
        } else {
            $ExtensionAttObject->setMetaDescription('null');
        }

        /** Setting SEO URL Of Product */
        $url = $this->productapihelper->getProductSeoUrl($product->getId());
        $ExtensionAttObject->setSeoUrl($url);

        return $product;
    }


    /**
     * Get Partner Product Filters
     * @param int $id
     * @return array
     */
    public function getPartnerProductsFilters($id) {
        $result = [];
        $partnerProdCatFilter = $this->productFilterHelper->getPartnerProductCategoryFilter($id);
        $advOrdIntFilter = $this->productFilterHelper->getAdvanceOrderIntimationFilter();
        $priceFilter = $this->productFilterHelper->getProductPriceFilter();
        array_push($result, $partnerProdCatFilter);
        array_push($result, $advOrdIntFilter);
        array_push($result, $priceFilter);
        return json_decode(json_encode($result, false));
    }

    /**
     * Get Product Filters
     * @return array
     */
    public function getProductsFilters() {
        $result = [];
        $partnerProdCatFilter = $this->productFilterHelper->getProductCategoryFilter();
        $advOrdIntFilter = $this->productFilterHelper->getAdvanceOrderIntimationFilter();
        $priceFilter = $this->productFilterHelper->getProductPriceFilter();
        $attrFilter = $this->productFilterHelper->getProductAttributeFilter(self::PROD_FILTER_ATTR);
        array_push($result, $partnerProdCatFilter);
        array_push($result, $advOrdIntFilter);
        array_push($result, $priceFilter);
        foreach ($attrFilter as $prodAttrFilter) {
            array_push($result, $prodAttrFilter);
        }
        return json_decode(json_encode($result, false));
    }

}
