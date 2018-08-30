<?php
/**
 * Bakeway
 *
 * @category  Bakeway
 * @package   Bakeway_ProductApi
 * @author    Bakeway
 */

namespace Bakeway\ProductApi\Model;

use Bakeway\ProductApi\Api\SyncProductCountInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory as OrderItemCollectionFactory;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Catalog\Model\Product\Visibility as CatalogVisibility;
use Magento\Catalog\Api\ProductRepositoryInterface as ProductInterface;

class SyncProductCount implements SyncProductCountInterface
{
    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\CollectionFactory
     */
    protected $orderItemCollectionFactory;

    /**
     * @var ProductCollectionFactory
     */
    protected $productCollectionFactory;

    /**
     * @var CatalogVisibility
     */
    protected $catalogVisibility;

    protected $productInterface;

    /**
     * SyncProductCount constructor.
     * @param OrderItemCollectionFactory $orderCollectionFactory
     * @param ProductCollectionFactory $productCollectionFactory
     * @param CatalogVisibility $catalogVisibility
     * @param ProductInterface $productInterface
     */
    public function __construct(
        OrderItemCollectionFactory $orderCollectionFactory,
        ProductCollectionFactory $productCollectionFactory,
        CatalogVisibility $catalogVisibility,
        ProductInterface $productInterface
    )
    {
        $this->catalogVisibility = $catalogVisibility;
        $this->orderItemCollectionFactory = $orderCollectionFactory;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->productInterface = $productInterface;
    }

    /**
     * SyncProduct Count
     * @return int|null $productId
     */
    public function SyncProductCount() {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $getProducts = $this->productCollectionFactory->create()->getAllIds();
        if (count($getProducts) > 0) {
            $productArray = $getProducts; //product Ids
            $value = 0; //amount
            $productActionObject = $objectManager->create('Magento\Catalog\Model\Product\Action');
            $productActionObject->updateAttributes($productArray, ['listing_position' => $value], 0);
        }
        $validCountStatuses = ['processing','bakeway_partner_accepted', 'bakeway_order_ready', 'bakeway_order_out_for_delivery', 'complete', 'pending', 'bakeway_partner_rejected'];
        $currentDateTime = new \DateTime('now', new \DateTimezone("Asia/Kolkata"));
        $today = $currentDateTime->format('Y-m-d 00:00:00');
        $currentDateTime->modify("-1 month");
        $lastMonthFromToday = $currentDateTime->format('Y-m-d 00:00:00');

        $orderItemCollection = $this->orderItemCollectionFactory->create()
            ->addFieldToSelect(['product_id']);
        $orderItemCollection->getSelect()->joinLeft(['so' => $orderItemCollection->getTable('sales_order')],
            'main_table.order_id=so.entity_id',
            []);
        $orderItemCollection->getSelect()->columns(
            [
                'qty_ordered' => new \Zend_Db_Expr("SUM(main_table.qty_ordered)")
            ]);
        $orderItemCollection->addFieldToFilter('so.status', ['in'=>$validCountStatuses]);
        $orderItemCollection->addFieldToFilter('so.created_at', ['gteq'=>$lastMonthFromToday]);
        $orderItemCollection->addFieldToFilter('so.created_at', ['lteq'=>$today]);
        $orderItemCollection->getSelect()->group('main_table.product_id');

        $productQuantityArray = [];

        foreach ($orderItemCollection as $orderItem) {
            $productId = $orderItem->getData('product_id');
            $qtyOrdered = $orderItem->getData('qty_ordered');
            $productQuantityArray[$productId] = intval($qtyOrdered);
        }

        $productIds = array_keys($productQuantityArray);

        $productCollection = $this->productCollectionFactory->create()
            ->addFieldToFilter('entity_id', ["in"=>$productIds]);
        $productCollection->setVisibility($this->catalogVisibility->getVisibleInSiteIds());
        $productCollection->joinAttribute('status', 'catalog_product/status', 'entity_id', null, 'inner');
        $productCollection->joinAttribute('visibility', 'catalog_product/visibility', 'entity_id', null, 'inner');

        $prodId = 0;
        foreach ($productCollection as $product) {
            $prodId = $product->getData('entity_id');
            if (isset($productQuantityArray[$prodId])) {
                try {
                    $product = $this->productInterface->getById($prodId, true);
                    $qty = $productQuantityArray[$prodId];
                    $product->setData('listing_position', $qty);
                    $product->setData('url_key', bin2hex(random_bytes(10)));
                    $product->save();
                } catch (\Exception $e) {
                    //throw new LocalizedException(__($e->getMessage()));
                }
            }
        }
        return $prodId;
    }
}