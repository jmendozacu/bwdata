<?php

namespace Bakeway\PartnerCatalogRule\Model\ResourceModel;

use Magento\Catalog\Model\Product;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Quote\Model\QuoteFactory;
use Magento\Quote\Model\QuoteIdMaskFactory;

class Rule extends \Magento\CatalogRule\Model\ResourceModel\Rule
{
    /**
     * @var \Magento\Framework\Webapi\Rest\Request
     */
    protected $webApiRequest;

    /**
     * @var QuoteFactory
     */
    protected $quoteFactory;

    /**
     * @var QuoteIdMaskFactory
     */
    protected $quoteIdMaskFactory;

    /**
     * Rule constructor.
     * @param \Magento\Framework\Model\ResourceModel\Db\Context $context
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param Product\ConditionFactory $conditionFactory
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $coreDate
     * @param \Magento\Eav\Model\Config $eavConfig
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     * @param \Magento\CatalogRule\Helper\Data $catalogRuleData
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\Stdlib\DateTime $dateTime
     * @param PriceCurrencyInterface $priceCurrency
     * @param \Magento\Framework\Webapi\Rest\Request $webApiRequest
     * @param QuoteFactory $quoteFactory
     * @param QuoteIdMaskFactory $quoteIdMaskFactory
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Catalog\Model\Product\ConditionFactory $conditionFactory,
        \Magento\Framework\Stdlib\DateTime\DateTime $coreDate,
        \Magento\Eav\Model\Config $eavConfig,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\CatalogRule\Helper\Data $catalogRuleData,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Stdlib\DateTime $dateTime,
        PriceCurrencyInterface $priceCurrency,
        \Magento\Framework\Webapi\Rest\Request $webApiRequest,
        QuoteFactory $quoteFactory,
        QuoteIdMaskFactory $quoteIdMaskFactory
    ) {
        $this->webApiRequest = $webApiRequest;
        $this->quoteFactory = $quoteFactory;
        $this->quoteIdMaskFactory = $quoteIdMaskFactory;
        parent::__construct(
            $context,
            $storeManager,
            $conditionFactory,
            $coreDate,
            $eavConfig,
            $eventManager,
            $catalogRuleData,
            $logger,
            $dateTime,
            $priceCurrency
        );
    }
    /**
     * Retrieve product prices by catalog rule for specific date, website and customer group
     * Collect data with  product Id => price pairs
     *
     * @param \DateTime $date
     * @param int $websiteId
     * @param int $customerGroupId
     * @param array $productIds
     * @return array
     */
    public function getRulePrices(\DateTime $date, $websiteId, $customerGroupId, $productIds)
    {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from($this->getTable('catalogrule_product_price'), ['product_id', 'rule_price'])
            ->where('rule_date = ?', $date->format('Y-m-d'))
            ->where('website_id = ?', $websiteId)
            ->where('customer_group_id = ?', $customerGroupId)
            ->where('product_id IN(?)', $productIds);

        $deliveryDate = null;
        $specificDiscountStartDate = null;
        $specificDiscountEndDate = null;
        $deliveryTime = null;
        $isQuoteGuest = false;
        $isCustomerQuote = false;
        $customerQuoteId = 0;
        $guestQuoteId = "";
        $quoteId= 0;

        $requestPath = $this->webApiRequest->getPathInfo();
        /**
         * Checking the request is for guest cart or logged in customer cart
         */
        if (strpos($requestPath, '/guest-carts/') !== false) {
            $pathArray = explode("/", $requestPath);
            $cartStringKey = array_search('guest-carts', $pathArray);
            $cartStringKey = $cartStringKey+1;
            $guestQuoteId = $pathArray[$cartStringKey];
            $isQuoteGuest = true;
        } else {
            $authTokenArr = explode(" ", $this->webApiRequest->getHeader('authorization'));

            if (isset($authTokenArr[0]) && isset($authTokenArr[1]) &&
                strtolower($authTokenArr[0]) == "bearer") {
                if (strpos($authTokenArr[1], ':') !== false) {
                    $splitToken = explode(":", $authTokenArr[1]);
                    if (isset($splitToken[0]) && isset($splitToken[1])) {
                        $tokenSelect = $connection->select()
                            ->from($this->getTable('oauth_token'), ['customer_id'])
                            ->where('token = ?', $splitToken[0]);
                        $customerId = $connection->fetchOne($tokenSelect);
                        if (empty($customerId) || $customerId == "") {
                            $tokenSelect = $connection->select()
                                ->from($this->getTable('oauth_token'), ['customer_id'])
                                ->where('token = ?', $splitToken[1]);
                            $customerId = $connection->fetchOne($tokenSelect);
                        }
                    }
                } else {
                    $tokenSelect = $connection->select()
                        ->from($this->getTable('oauth_token'), ['customer_id'])
                        ->where('token = ?', $authTokenArr[1]);
                    $customerId = $connection->fetchOne($tokenSelect);
                }

                $quoteSelect = $connection->select()
                    ->from($this->getTable('quote'), ['entity_id'])
                    ->where('customer_id = ?', $customerId)
                    ->where('is_active = ?', 1);
                $customerQuoteId = $connection->fetchOne($quoteSelect);
                $isCustomerQuote = true;
            }
        }

        $method = $this->webApiRequest->getHttpMethod();
        /**
         * Logic to fetch delivery date from cart and start-end date from applied rule.
         */
        if (strtolower($method) == "post" && ($isCustomerQuote === true || $isQuoteGuest === true)) {
            if ($isCustomerQuote === true && $customerQuoteId != 0) {
                $quoteId = $customerQuoteId;
            } elseif ($isQuoteGuest === true && $guestQuoteId != "") {
                $quoteId = $guestQuoteId;
            } else {
                $quoteId = 0;
            }
            try {
                if (!is_numeric($quoteId)) {
                    $maskedIdObj = $this->quoteIdMaskFactory->create()->load($quoteId, 'masked_id');
                    $quoteId = $maskedIdObj->getQuoteId();
                }
                $quoteSelect = $connection->select()
                    ->from($this->getTable('quote'), ['delivery_time'])
                    ->where('entity_id = ?', $quoteId);
                $quoteResult = $connection->fetchOne($quoteSelect);

                if (isset($quoteResult)) {
                    $deliveryTime = $quoteResult;
                }
                if ($deliveryTime !== null) {
                    $deliveryDateObj = new \DateTime($deliveryTime);
                    $deliveryDate = $deliveryDateObj->format('Y-m-d');
                }

                $dateSelectQuery = $connection->select()
                    ->from($this->getTable('catalogrule_product_price'), ['fixed_discount_start_date', 'fixed_discount_end_date'])
                    ->where('rule_date = ?', $date->format('Y-m-d'))
                    ->where('website_id = ?', $websiteId)
                    ->where('customer_group_id = ?', $customerGroupId)
                    ->where('product_id IN(?)', $productIds);
                $queryResult = $connection->fetchRow($dateSelectQuery);
                if (isset($queryResult['fixed_discount_start_date'])) {
                    $specificDiscountStartDate = $queryResult['fixed_discount_start_date'];
                } else {
                    $specificDiscountStartDate = null;
                }
                if (isset($queryResult['fixed_discount_end_date'])) {
                    $specificDiscountEndDate = $queryResult['fixed_discount_end_date'];
                } else {
                    $specificDiscountEndDate = null;
                }
            } catch (\Exception $e) {

            }
        }

        $result = $connection->fetchPairs($select);
        /**
         * Checking if delivery date come in between specific discount range.
         */
        if ($deliveryDate !== null &&
            ($specificDiscountStartDate !== null || $specificDiscountEndDate !== null)) {
            if (strtotime($specificDiscountStartDate) <= strtotime($deliveryDate) &&
                strtotime($deliveryDate) <= strtotime($specificDiscountEndDate)) {
                return $result;
            } else {
                return [];
            }
        }

        return $result;
    }
}