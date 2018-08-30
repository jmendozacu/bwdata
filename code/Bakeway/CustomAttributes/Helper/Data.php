<?php
/**
 * Bakeway
 *
 * @category  Bakeway
 * @package   Bakeway_CustomAttributes
 * @author    Bakeway
 */

namespace Bakeway\CustomAttributes\Helper;

use Webkul\Marketplace\Model\ProductFactory as MarketplaceProductFactory;
use Webkul\Marketplace\Model\ResourceModel\Product\CollectionFactory as MarketplaceProductCollectionFactory;
use Magento\Catalog\Model\ProductRepository as ProductRepository;
use Webkul\Marketplace\Model\ResourceModel\Seller\CollectionFactory as MarketplaceSellerCollectionFactory;
use Bakeway\PartnerWebapi\Helper\Data as PartnerWebApiHelper;
use Magento\Framework\Exception\LocalizedException;
use Webkul\Marketplace\Helper\Data as MarketplaceHelper;
use Bakeway\Vendorcontract\Model\ResourceModel\Vendorcontract\Collection as ContractCollection;
use Bakeway\Deliveryrangeprice\Model\ResourceModel\Rangeprice\Collection as DeliveryRangeCollection;
use \DateTime;
use Magento\Framework\Exception\NotFoundException;
use Bakeway\GrabIntigration\Helper\Data as GrabintigrationHhelper;
/**
 * Bakeway CustomAttributes Helper Data.
 */
class Data extends \Magento\Framework\App\Helper\AbstractHelper
{

    CONST MIN_MINUTE_INTIMATION_TIME = 30;

    CONST MAX_MINUTE_INTIMATION_TIME = 59;

    CONST MIN_HOURS_INTIMATION_TIME = 0;

    /**
     * @var MarketplaceProductFactory
     */
    protected $marketplaceProduct;

    /**
     * @var MarketplaceProductCollectionFactory
     */
    protected $marketplaceProductCollection;

    /**
     * @var ProductRepository
     */
    protected $productRepository;

    /**
     * @var MarketplaceSellerCollectionFactory
     */
    protected $marketplaceSellerCollection;

    /**
     * @var PartnerWebApiHelper
     */
    protected $partnerApiHelper;

    /**
     * @var ContractCollection
     */
    protected $contractCollection;

    /**
     * @var MarketplaceHelper
     */
    protected $marketplaceHelper;

    /**
     * @var DeliveryRangeCollection
     */
    protected $deliveryRangeCollection;

    /**
     * @var GrabintigrationHhelper
     */
    protected $grabintigrationHhelper;

    /**
     * Data constructor.
     * @param \Magento\Framework\App\Helper\Context $context
     * @param MarketplaceProductFactory $marketplaceProduct
     * @param MarketplaceProductCollectionFactory $marketplaceProductCollection
     * @param ProductRepository $productRepository
     * @param MarketplaceSellerCollectionFactory $marketplaceSellerCollection
     * @param PartnerWebApiHelper $partnerApiHelper
     * @param ContractCollection $contractCollection
     * @param MarketplaceHelper $marketplaceHelper
     * @param DeliveryRangeCollection $deliveryRangeCollection
     * @param GrabintigrationHhelper $grabintigrationHhelper
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        MarketplaceProductFactory $marketplaceProduct,
        MarketplaceProductCollectionFactory $marketplaceProductCollection,
        ProductRepository $productRepository,
        MarketplaceSellerCollectionFactory $marketplaceSellerCollection,
        PartnerWebApiHelper $partnerApiHelper,
        ContractCollection $contractCollection,
        MarketplaceHelper $marketplaceHelper,
        DeliveryRangeCollection $deliveryRangeCollection,
        GrabintigrationHhelper $grabintigrationHhelper
    )
    {
        $this->marketplaceProduct = $marketplaceProduct;
        $this->marketplaceProductCollection = $marketplaceProductCollection;
        $this->productRepository = $productRepository;
        $this->marketplaceSellerCollection = $marketplaceSellerCollection;
        $this->partnerApiHelper = $partnerApiHelper;
        $this->contractCollection = $contractCollection;
        $this->marketplaceHelper = $marketplaceHelper;
        $this->deliveryRangeCollection = $deliveryRangeCollection;
        $this->grabintigrationHhelper = $grabintigrationHhelper;
        parent::__construct($context);
    }

    /**
     * @param int $productId
     * @return bool
     */
    public function addToCartCheck($productId) {
        return $this->checkUnapprovedProduct($productId);
    }

    /**
     * @param int $productId
     * @return bool
     */
    public function checkUnapprovedProduct($productId) {
        $productCollection = $this->marketplaceProductCollection->create()
            ->addFieldToFilter('mageproduct_id', $productId);

        foreach ($productCollection as $product) {
            if ($product->getStatus() != 1) {
                return true;
            }
        }

        return false;
    }

    public function getSellerIdBySku($sku) {
        $sellerId = false;
        $productId  = $this->getProductIdBySku($sku);
        if ($productId !== false) {
            $productCollection = $this->marketplaceProductCollection->create()
                ->addFieldToFilter('mageproduct_id', $productId);
            foreach ($productCollection as $product) {
                $sellerId = $product->getSellerId();
            }
        }
        return $sellerId;
    }

    public function getProductIdBySku($sku) {
        $product = $this->productRepository->get($sku);
        if ($product->getId()) {
            return $product->getId();
        }
        return false;
    }

    public function getProductTypeBySku($sku) {
        try{
            $product = $this->productRepository->get($sku);
            if ($product->getId()) {
                return $product->getTypeId();
            }
            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function getShopTimingsBySku($sku) {
        $sellerId = $this->getSellerIdBySku($sku);
        $timingArray = [];
        if ($sellerId !== false) {
            $seller = $this->marketplaceSellerCollection->create()
                ->addFieldToFilter('seller_id', $sellerId)
                ->getFirstItem();
            if ($seller->getId()) {
                /**
                 * Shop permanently closed check.
                 */
                if ($seller->getData('userdata_shop_operatational_status') == 1) {
                    return $timingArray;
                }
                $shopOpenTime = $seller->getShopOpenTiming();
                $shopOpenAmPm = $seller->getData('shop_open_AMPM');
                if ($shopOpenAmPm == 1) {
                    $shopOpenAmPm = "AM";
                } elseif ($shopOpenAmPm == 2) {
                    $shopOpenAmPm = "PM";
                }
                $shopCloseTime = $seller->getShopCloseTiming();
                $shopCloseAmPm = $seller->getData('shop_close_AMPM');
                if ($shopCloseAmPm == 1) {
                    $shopCloseAmPm = "AM";
                } elseif ($shopCloseAmPm == 2) {
                    $shopCloseAmPm = "PM";
                }

                $shopOpen = strtotime($shopOpenTime." ".$shopOpenAmPm);

                $shopClose = strtotime($shopCloseTime." ".$shopCloseAmPm);
                if ($shopClose <= $shopOpen) {
                    $shopClose = strtotime("+1 Day", $shopClose);
                }

                while ( $shopOpen <= $shopClose ) {

                    $timingArray[] = date('h A', $shopOpen);
                    $shopOpen = strtotime('+1 hour', $shopOpen);
                }
            }
        }
        return $timingArray;
    }

    public function checkItemForDateTime($sku, $deliveryDate) {
        $result = true;
        $availableTimings = $this->getShopTimingsBySku($sku);
        $deliveryTime = date('h A', strtotime($deliveryDate));
        $deliveryDate = date('Y-m-d', strtotime($deliveryDate));

        /**
         * Shop open close timings check
         */
        if (!in_array($deliveryTime, $availableTimings)) {
            $result = false;
        }

        $sellerId = $this->getSellerIdBySku($sku);

        /**
         * Shop operational dates check
         */
        if ($sellerId !== false) {
            $sellerIdsArray = $this->partnerApiHelper->getClosedBakeriesByDate($deliveryDate);

            if (in_array($sellerId, $sellerIdsArray)) {
                $result = false;
            }
        }
        return $result;
    }

    public function applyQuoteValidations(
        \Magento\Quote\Api\Data\CartInterface $quote,
        $deliveryType,
        $deliveryTime,
        $lastQuoteItem = null,
        $isOrderPlacing = false
    )
    {
        if ($lastQuoteItem !== null) {
            $this->validateQuoteItem($quote,$lastQuoteItem, $deliveryType, $deliveryTime, true, $isOrderPlacing);
        }
        /**
         * Code to check if quote item is valid to add in the quote
         */
        if ($quote->getItems()) {
            foreach ($quote->getItems() as $quoteItem) {
                $this->validateQuoteItem($quote,$quoteItem, $deliveryType, $deliveryTime, false, $isOrderPlacing);
            }
        }
    }

    /**
     * @param $quoteItem
     * @param $deliveryType
     * @param $deliveryTime
     * @param $isLastItem
     * @param $isOrderPlacing
     * @throws LocalizedException
     * @throws NotFoundException
     */
    public function validateQuoteItem(
        $quote,
        $quoteItem,
        $deliveryType,
        $deliveryTime,
        $isLastItem,
        $isOrderPlacing
    )
    {
        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/order_process.log');
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);
        $logger->info("=======Quote Item Validation Starts======".$quoteItem->getData('item_id')."::".$quoteItem->getData('quote_id'));
    }

    /**
     * @param $quoteItem
     * @param $isLastItem
     * @return void
     */
    public function deleteLastItem($quoteItem, $isLastItem) {
        if ($isLastItem === true) {
            $quoteItem->delete();
        }
        return;
    }


    public function getOrderTimeInterval()
    {
        $interval = $this->scopeConfig->getValue('react_site_settings/react_settings_general/order_time_interval',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        return $interval;
    }
}