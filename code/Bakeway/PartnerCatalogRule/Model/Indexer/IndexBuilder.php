<?php

namespace Bakeway\PartnerCatalogRule\Model\Indexer;

use Magento\Catalog\Model\Product;
use Magento\CatalogRule\Model\ResourceModel\Rule\CollectionFactory as RuleCollectionFactory;
use Magento\CatalogRule\Model\Rule;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Bakeway\PartnerCatalogRule\Helper\Data as PartnerRuleHelper;

class IndexBuilder extends \Magento\CatalogRule\Model\Indexer\IndexBuilder
{
    /**
     * @var PartnerRuleHelper
     */
    protected $partnerRuleHelper;

    /**
     * IndexBuilder constructor.
     * @param PartnerRuleHelper $partnerRuleHelper
     * @param RuleCollectionFactory $ruleCollectionFactory
     * @param PriceCurrencyInterface $priceCurrency
     * @param \Magento\Framework\App\ResourceConnection $resource
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Eav\Model\Config $eavConfig
     * @param \Magento\Framework\Stdlib\DateTime $dateFormat
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
     * @param \Magento\Catalog\Model\ProductFactory $productFactory
     * @param int $batchCount
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        PartnerRuleHelper $partnerRuleHelper,
        RuleCollectionFactory $ruleCollectionFactory,
        PriceCurrencyInterface $priceCurrency,
        \Magento\Framework\App\ResourceConnection $resource,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Eav\Model\Config $eavConfig,
        \Magento\Framework\Stdlib\DateTime $dateFormat,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        $batchCount = 1000
    )
    {
        $this->partnerRuleHelper = $partnerRuleHelper;
        parent::__construct(
            $ruleCollectionFactory,
            $priceCurrency,
            $resource,
            $storeManager,
            $logger,
            $eavConfig,
            $dateFormat,
            $dateTime,
            $productFactory,
            $batchCount
        );
    }
    /**
     * @param int $timeStamp
     * @return int
     */
    private function roundTime($timeStamp)
    {
        if (is_numeric($timeStamp) && $timeStamp != 0) {
            $timeStamp = $this->dateTime->timestamp($this->dateTime->date('Y-m-d 00:00:00', $timeStamp));
        }

        return $timeStamp;
    }

    /**
     * @param Rule $rule
     * @param Product $product
     * @return $this
     * @throws \Exception
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    protected function applyRule(Rule $rule, $product)
    {
        $ruleId = $rule->getId();
        $productEntityId = $product->getId();
        $websiteIds = array_intersect($product->getWebsiteIds(), $rule->getWebsiteIds());

        if (!$rule->validate($product)) {
            return $this;
        }

        $this->connection->delete(
            $this->resource->getTableName('catalogrule_product'),
            [
                $this->connection->quoteInto('rule_id = ?', $ruleId),
                $this->connection->quoteInto('product_id = ?', $productEntityId)
            ]
        );

        $ruleInfoArr = $this->partnerRuleHelper->getRuleDataById($ruleId);

        if (isset($ruleInfoArr['fixed_discount_start_date'])) {
            $fixedDiscountStartTime = strtotime($ruleInfoArr['fixed_discount_start_date']);
        } else {
            $fixedDiscountStartTime = 0;
        }
        if (isset($ruleInfoArr['fixed_discount_end_date'])) {
            $fixedDiscountEndTime = strtotime($ruleInfoArr['fixed_discount_end_date']);
        } else {
            $fixedDiscountEndTime = 0;
        }

        $customerGroupIds = $rule->getCustomerGroupIds();
        $fromTime = strtotime($rule->getFromDate());
        $toTime = strtotime($rule->getToDate());
        $toTime = $toTime ? $toTime + self::SECONDS_IN_DAY - 1 : 0;
        $sortOrder = (int)$rule->getSortOrder();
        $actionOperator = $rule->getSimpleAction();
        $actionAmount = $rule->getDiscountAmount();
        $actionStop = $rule->getStopRulesProcessing();
        $rows = [];
        try {
            foreach ($websiteIds as $websiteId) {
                foreach ($customerGroupIds as $customerGroupId) {
                    $rows[] = [
                        'rule_id' => $ruleId,
                        'from_time' => $fromTime,
                        'to_time' => $toTime,
                        'website_id' => $websiteId,
                        'customer_group_id' => $customerGroupId,
                        'product_id' => $productEntityId,
                        'action_operator' => $actionOperator,
                        'action_amount' => $actionAmount,
                        'action_stop' => $actionStop,
                        'sort_order' => $sortOrder,
                        'fixed_discount_start_time' => $fixedDiscountStartTime,
                        'fixed_discount_end_time' => $fixedDiscountEndTime,
                    ];

                    if (count($rows) == $this->batchCount) {
                        $this->connection->insertMultiple($this->getTable('catalogrule_product'), $rows);
                        $rows = [];
                    }
                }
            }

            if (!empty($rows)) {
                $this->connection->insertMultiple($this->resource->getTableName('catalogrule_product'), $rows);
            }
        } catch (\Exception $e) {
            throw $e;
        }

        $this->applyAllRules($product);

        return $this;
    }

    /**
     * @param Rule $rule
     * @return $this
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    protected function updateRuleProductData(Rule $rule)
    {
        $ruleId = $rule->getId();
        if ($rule->getProductsFilter()) {
            $this->connection->delete(
                $this->getTable('catalogrule_product'),
                ['rule_id=?' => $ruleId, 'product_id IN (?)' => $rule->getProductsFilter()]
            );
        } else {
            $this->connection->delete(
                $this->getTable('catalogrule_product'),
                $this->connection->quoteInto('rule_id=?', $ruleId)
            );
        }

        if (!$rule->getIsActive()) {
            return $this;
        }

        $websiteIds = $rule->getWebsiteIds();
        if (!is_array($websiteIds)) {
            $websiteIds = explode(',', $websiteIds);
        }
        if (empty($websiteIds)) {
            return $this;
        }

        $ruleInfoArr = $this->partnerRuleHelper->getRuleDataById($ruleId);

        if (isset($ruleInfoArr['fixed_discount_start_date'])) {
            $fixedDiscountStartTime = strtotime($ruleInfoArr['fixed_discount_start_date']);
        } else {
            $fixedDiscountStartTime = 0;
        }
        if (isset($ruleInfoArr['fixed_discount_end_date'])) {
            $fixedDiscountEndTime = strtotime($ruleInfoArr['fixed_discount_end_date']);
        } else {
            $fixedDiscountEndTime = 0;
        }

        \Magento\Framework\Profiler::start('__MATCH_PRODUCTS__');
        $productIds = $rule->getMatchingProductIds();
        \Magento\Framework\Profiler::stop('__MATCH_PRODUCTS__');

        $customerGroupIds = $rule->getCustomerGroupIds();
        $fromTime = strtotime($rule->getFromDate());
        $toTime = strtotime($rule->getToDate());
        $toTime = $toTime ? $toTime + self::SECONDS_IN_DAY - 1 : 0;
        $sortOrder = (int)$rule->getSortOrder();
        $actionOperator = $rule->getSimpleAction();
        $actionAmount = $rule->getDiscountAmount();
        $actionStop = $rule->getStopRulesProcessing();

        $rows = [];

        foreach ($productIds as $productId => $validationByWebsite) {
            foreach ($websiteIds as $websiteId) {
                if (empty($validationByWebsite[$websiteId])) {
                    continue;
                }
                foreach ($customerGroupIds as $customerGroupId) {
                    $rows[] = [
                        'rule_id' => $ruleId,
                        'from_time' => $fromTime,
                        'to_time' => $toTime,
                        'website_id' => $websiteId,
                        'customer_group_id' => $customerGroupId,
                        'product_id' => $productId,
                        'action_operator' => $actionOperator,
                        'action_amount' => $actionAmount,
                        'action_stop' => $actionStop,
                        'sort_order' => $sortOrder,
                        'fixed_discount_start_time' => $fixedDiscountStartTime,
                        'fixed_discount_end_time' => $fixedDiscountEndTime,
                    ];

                    if (count($rows) == $this->batchCount) {
                        $this->connection->insertMultiple($this->getTable('catalogrule_product'), $rows);
                        $rows = [];
                    }
                }
            }
        }
        if (!empty($rows)) {
            $this->connection->insertMultiple($this->getTable('catalogrule_product'), $rows);
        }

        return $this;
    }

    /**
     * @param Product|null $product
     * @throws \Exception
     * @return $this
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function applyAllRules(Product $product = null)
    {
        $fromDate = mktime(0, 0, 0, date('m'), date('d') - 1);
        $toDate = mktime(0, 0, 0, date('m'), date('d') + 1);

        /**
         * Update products rules prices per each website separately
         * because of max join limit in mysql
         */
        foreach ($this->storeManager->getWebsites() as $website) {
            $productsStmt = $this->getRuleProductsStmt($website->getId(), $product);

            $dayPrices = [];
            $stopFlags = [];
            $prevKey = null;

            while ($ruleData = $productsStmt->fetch()) {
                $ruleProductId = $ruleData['product_id'];
                $productKey = $ruleProductId .
                    '_' .
                    $ruleData['website_id'] .
                    '_' .
                    $ruleData['customer_group_id'];

                if ($prevKey && $prevKey != $productKey) {
                    $stopFlags = [];
                    if (count($dayPrices) > $this->batchCount) {
                        $this->saveRuleProductPrices($dayPrices);
                        $dayPrices = [];
                    }
                }

                $ruleData['from_time'] = $this->roundTime($ruleData['from_time']);
                $ruleData['to_time'] = $this->roundTime($ruleData['to_time']);
                $ruleData['fixed_discount_start_time'] = $this->roundTime($ruleData['fixed_discount_start_time']);
                $ruleData['fixed_discount_end_time'] = $this->roundTime($ruleData['fixed_discount_end_time']);
                /**
                 * Build prices for each day
                 */
                for ($time = $fromDate; $time <= $toDate; $time += self::SECONDS_IN_DAY) {
                    if (($ruleData['from_time'] == 0 ||
                            $time >= $ruleData['from_time']) && ($ruleData['to_time'] == 0 ||
                            $time <= $ruleData['to_time'])
                    ) {
                        $priceKey = $time . '_' . $productKey;

                        if (isset($stopFlags[$priceKey])) {
                            continue;
                        }

                        if (!isset($dayPrices[$priceKey])) {
                            $dayPrices[$priceKey] = [
                                'rule_date' => $time,
                                'website_id' => $ruleData['website_id'],
                                'customer_group_id' => $ruleData['customer_group_id'],
                                'product_id' => $ruleProductId,
                                'rule_price' => $this->calcRuleProductPrice($ruleData),
                                'latest_start_date' => $ruleData['from_time'],
                                'earliest_end_date' => $ruleData['to_time'],
                                'fixed_discount_start_date' => $ruleData['fixed_discount_start_time'],
                                'fixed_discount_end_date' => $ruleData['fixed_discount_end_time'],
                            ];
                        } else {
                            $dayPrices[$priceKey]['rule_price'] = $this->calcRuleProductPrice(
                                $ruleData,
                                $dayPrices[$priceKey]
                            );
                            $dayPrices[$priceKey]['latest_start_date'] = max(
                                $dayPrices[$priceKey]['latest_start_date'],
                                $ruleData['from_time']
                            );
                            $dayPrices[$priceKey]['earliest_end_date'] = min(
                                $dayPrices[$priceKey]['earliest_end_date'],
                                $ruleData['to_time']
                            );
                            $dayPrices[$priceKey]['fixed_discount_start_date'] = $ruleData['fixed_discount_start_time'];
                            $dayPrices[$priceKey]['fixed_discount_end_date'] = $ruleData['fixed_discount_end_time'];
                        }

                        if ($ruleData['action_stop']) {
                            $stopFlags[$priceKey] = true;
                        }
                    }
                }
                $prevKey = $productKey;
            }
            $this->saveRuleProductPrices($dayPrices);
        }

        return $this->updateCatalogRuleGroupWebsiteData();
    }

    /**
     * @param array $arrData
     * @return $this
     * @throws \Exception
     */
    protected function saveRuleProductPrices($arrData)
    {
        if (empty($arrData)) {
            return $this;
        }

        $productIds = [];

        try {
            foreach ($arrData as $key => $data) {
                $productIds['product_id'] = $data['product_id'];
                $arrData[$key]['rule_date'] = $this->dateFormat->formatDate($data['rule_date'], false);
                $arrData[$key]['latest_start_date'] = $this->dateFormat->formatDate($data['latest_start_date'], false);
                $arrData[$key]['earliest_end_date'] = $this->dateFormat->formatDate($data['earliest_end_date'], false);
                $arrData[$key]['fixed_discount_start_date'] = $this->dateFormat->formatDate($data['fixed_discount_start_date'], false);
                $arrData[$key]['fixed_discount_end_date'] = $this->dateFormat->formatDate($data['fixed_discount_end_date'], false);
            }
            $this->connection->insertOnDuplicate($this->getTable('catalogrule_product_price'), $arrData);
        } catch (\Exception $e) {
            throw $e;
        }

        return $this;
    }
}