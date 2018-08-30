<?php
/**
 * Bakeway
 *
 * @category  Bakeway
 * @package   Bakeway_Crons
 * @author    Bakeway
 */

namespace Bakeway\Crons\Model;

use Magento\Catalog\Api\ProductRepositoryInterface as ProductRepository;
use Webkul\Marketplace\Model\ResourceModel\Product\Collection as SellerProductCollection;
use Webkul\Marketplace\Helper\Data as MarketplaceHelper;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Bakeway\ProductApi\Helper\Data as ProductApiHelper;
use Webkul\Marketplace\Model\Seller as MarketplaceSeller;

class SetAddonAvailabilityCron {

    /**
     * @var ProductRepository
     */
    protected $productRepository;

    /**
     * @var SellerProductCollection
     */
    protected $sellerProductCollection;

    /**
     * @var MarketplaceHelper
     */
    protected $marketplaceHelper;

    /**
     * @var ProductCollection
     */
    protected $productCollection;

    /**
     * @var ProductApiHelper
     */
    protected $productApiHelper;

    /**
     * @var MarketplaceSeller
     */
    protected $marketplaceSeller;

    /**
     * SetAddonAvailabilityCron constructor.
     * @param ProductRepository $productRepository
     * @param SellerProductCollection $sellerProductCollection
     * @param MarketplaceHelper $marketplaceHelper
     * @param ProductCollection $productCollection
     * @param ProductApiHelper $productApiHelper
     * @param MarketplaceSeller $marketplaceSeller
     */
    public function __construct(
        ProductRepository $productRepository,
        SellerProductCollection $sellerProductCollection,
        MarketplaceHelper $marketplaceHelper,
        ProductCollection $productCollection,
        ProductApiHelper $productApiHelper,
        MarketplaceSeller $marketplaceSeller
    )
    {
        $this->productRepository = $productRepository;
        $this->sellerProductCollection = $sellerProductCollection;
        $this->marketplaceHelper = $marketplaceHelper;
        $this->productCollection = $productCollection;
        $this->productApiHelper = $productApiHelper;
        $this->marketplaceSeller = $marketplaceSeller;
    }

    public function setAddonAvailability()
    {
        $sellerProdColl = $this->sellerProductCollection
                            ->addFieldToSelect(['seller_id', 'mageproduct_id']);

        $sellerProdColl->getSelect()->joinInner(['mp_seller'=>$sellerProdColl->getTable('marketplace_userdata')],
            'main_table.seller_id=mp_seller.seller_id',
            ['is_seller', 'is_live_ready', 'is_addon_available', 'entity_id as seller_entity_id']);

        $sellerProdColl->addFieldToFilter('is_seller', 1);

        $sellerProdArray = [];
        foreach ($sellerProdColl as $sellerProd) {
            $sellerProdArray[$sellerProd->getData('mageproduct_id')] = $sellerProd->getData('seller_entity_id');
        }

        $sellerProdIds = $sellerProdColl->getColumnValues('mageproduct_id');

        $prodColl = $this->productCollection
                        ->addFieldToFilter('entity_id', ['in'=>$sellerProdIds]);

        $categoryArray = $this->productApiHelper->getMainandAddoncategory();

        if (is_array($categoryArray) && isset($categoryArray['addons']['id'])) {
            $addonId = $categoryArray['addons']['id'];
            foreach ($prodColl as $product) {
                $categoryIds = $product->getCategoryIds();
                if (is_array($categoryIds)) {
                    if (in_array($addonId, $categoryIds)) {
                        if (isset($sellerProdArray[$product->getEntityId()])) {
                            $sellerId = $sellerProdArray[$product->getEntityId()];
                            $sellerObj = $this->marketplaceSeller->load($sellerId);
                            $sellerObj->setData('is_addon_available', 1);
                            $sellerObj->save();
                        }
                    }
                }
            }
        }

    }
}
