<?php
/**
 * Bakeway
 *
 * @category  Bakeway
 * @package   Bakeway_CatalogSync
 * @author    Bakeway
 */

namespace Bakeway\CatalogSync\Helper;

use Bakeway\CatalogSync\Model\CatalogProductSync;
use Bakeway\ProductApi\Helper\Data as ProductApiHelper;

/**
 * Bakeway CatalogSync Helper Data.
 */
class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var CatalogProductSync
     */
    protected $catalogProductSync;

    protected $productApiHelper;

    /**
     * @param \Magento\Framework\App\Helper\Context $context
     * @param CatalogProductSync $catalogProductSync
     * @param ProductApiHelper $productApiHelper
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        CatalogProductSync $catalogProductSync,
        ProductApiHelper $productApiHelper
    ) {
        parent::__construct($context);
        $this->catalogProductSync = $catalogProductSync;
        $this->productApiHelper = $productApiHelper;
    }

    public function syncToBakewayCatalog() {
        $this->catalogProductSync->syncProductAttributes();
    }

    public function getCatalogAttributes($syncedProduct = null, $product, $cityId) {
        $result = [];
        if ($syncedProduct !== null) {
            $rulePrice = $syncedProduct->getData('catalog_discount_price');
            $ruleTaxPrice = $syncedProduct->getData('catalog_discount_price_incl_tax');
            $discountRule = $syncedProduct->getData('catalog_rule_name');
            $specificRuleStartDate = $syncedProduct->getData('fixed_discount_start_date');
            $specificRuleEndDate = $syncedProduct->getData('fixed_discount_end_date');
            $flavour = $syncedProduct->getData('cake_flavour');
            $weight = $syncedProduct->getData('cake_weight');
            $ingredient = $syncedProduct->getData('cake_ingredients');
            $intimationTime = $syncedProduct->getData('advance_order_intimation');
            $advancedOrderintimationTimeUnit = $syncedProduct->getData('advanced_order_intimation_unit');
            $specialPrice = $syncedProduct->getData('special_price');
            $priceInclTax = $syncedProduct->getData('price_incl_tax');
            $priceExclTax = $syncedProduct->getData('price_excl_tax');
            $categoryJson = unserialize($syncedProduct->getData('categories_json'));
        } else {
            $type = $product->getTypeId();
            $catIds = $product->getCategoryIds();
            $catJson = $this->productApiHelper->getCategoryName($catIds);
            $categoryJson = "";
            if (!empty($catJson)) {
                $categoryJson = $catJson;
            } else {
                $categoryJson = 'null';
            }
            if ($type == "simple") {
                $ruleData = $this->productApiHelper->getRuleDataByProduct($product->getId());
                if (isset($ruleData['name'])) {
                    $discountRule = $ruleData['name'];
                } else {
                    $discountRule = null;
                }
                if (isset($ruleData['fixed_discount_start_date'])) {
                    $specificRuleStartDate = $ruleData['fixed_discount_start_date'];
                } else {
                    $specificRuleStartDate = null;
                }
                if (isset($ruleData['fixed_discount_end_date'])) {
                    $specificRuleEndDate = $ruleData['fixed_discount_end_date'];
                } else {
                    $specificRuleEndDate = null;
                }
                $rulePrice = $this->productApiHelper->getCatalogRulePrice($product->getId());
                $ruleTaxPrice = $this->productApiHelper->getCatalogRuleTaxPrice($product, $rulePrice);
                if (!($rulePrice && $ruleTaxPrice)) {
                    $rulePrice = 0;
                    $ruleTaxPrice = 0;
                }
                $flavour = $product->getAttributeText('cake_flavour');
                $weight = $product->getAttributeText('cake_weight');
                $ingredient = $product->getAttributeText('cake_ingredients');
                $intimationTime = $product->getData('advance_order_intimation');
                $advancedOrderintimationTimeUnit = $product->getData('advanced_order_intimation_unit');
                $specialPrice = $product->getSpecialPrice();
                $priceExclTax = $product->getPrice();
                $priceInclTax = $this->productApiHelper->getProductTaxPrice($product, null, null, false, null, $cityId);
            } elseif ($type == "configurable") {
                $minPriceSimpleProd = $this->productApiHelper->getMinproductPrice($product, null, null, $cityId);
                $priceExclTax = $minPriceSimpleProd['min_price'];
                $priceInclTax = $minPriceSimpleProd['tax_incl_price'];

                $ruleData = $this->productApiHelper->getRuleDataByProduct($minPriceSimpleProd['product_id']);
                if (isset($ruleData['name'])) {
                    $discountRule = $ruleData['name'];
                } else {
                    $discountRule = null;
                }
                if (isset($ruleData['fixed_discount_start_date'])) {
                    $specificRuleStartDate = $ruleData['fixed_discount_start_date'];
                } else {
                    $specificRuleStartDate = null;
                }
                if (isset($ruleData['fixed_discount_end_date'])) {
                    $specificRuleEndDate = $ruleData['fixed_discount_end_date'];
                } else {
                    $specificRuleEndDate = null;
                }
                $rulePrice = $this->productApiHelper->getCatalogRulePrice($minPriceSimpleProd['product_id']);

                if ($minPriceSimpleProd['simple_prod_obj'] !== null) {
                    $ruleTaxPrice = $this->productApiHelper->getCatalogRuleTaxPrice($minPriceSimpleProd['simple_prod_obj'], $rulePrice);
                } else {
                    $ruleTaxPrice = 0;
                }
                if (!($rulePrice && $ruleTaxPrice)) {
                    $rulePrice = 0;
                    $ruleTaxPrice = 0;
                }
                if (empty($priceExclTax)) {
                    $priceExclTax = '0.00';
                }
                if (empty($priceInclTax)) {
                    $priceInclTax = '0.00';
                }

                if ($minPriceSimpleProd['simple_prod_obj'] == null) {
                    $flavour = $weight = $ingredient = $intimationTime = $specialPrice = $advancedOrderintimationTimeUnit = null;
                } else {
                    $flavour = $minPriceSimpleProd['simple_prod_obj']->getAttributeText('cake_flavour');
                    $weight = $minPriceSimpleProd['simple_prod_obj']->getAttributeText('cake_weight');
                    $ingredient = $minPriceSimpleProd['simple_prod_obj']->getAttributeText('cake_ingredients');
                    $intimationTime = $minPriceSimpleProd['simple_prod_obj']->getData('advance_order_intimation');
                    $advancedOrderintimationTimeUnit = $minPriceSimpleProd['simple_prod_obj']->getData('advance_order_intimation_unit');
                    $specialPrice = $minPriceSimpleProd['simple_prod_obj']->getSpecialPrice();
                }
            }
        }
        $result['rule_price'] = $rulePrice;
        $result['rule_tax_price'] = $ruleTaxPrice;
        $result['special_price'] = $specialPrice;
        $result['price_incl_tax'] = $priceInclTax;
        $result['price_excl_tax'] = $priceExclTax;
        $result['flavour'] = $flavour;
        $result['weight'] = $weight;
        $result['ingredient'] = $ingredient;
        $result['intimation_time'] = $intimationTime;
        $result['advanced_order_intimation_unit'] = $advancedOrderintimationTimeUnit;
        $result['discount_rule'] = $discountRule;
        $result['discount_rule_start_date'] = $specificRuleStartDate;
        $result['discount_rule_end_date'] = $specificRuleEndDate;
        $result['category_json'] = $categoryJson;

        return $result;
    }
}