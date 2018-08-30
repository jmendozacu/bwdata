<?php

/**
 * Bakeway
 *
 * @category  Bakeway
 * @package   Bakeway_CustomAttributes
 * @author    Bakeway
 */

namespace Bakeway\CustomAttributes\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Api\Data\OrderExtensionFactory;
use Bakeway\Partnerlocations\Model\ResourceModel\Partnerlocations\Collection as LocationCollection;
use Webkul\Marketplace\Model\ResourceModel\Orders\CollectionFactory as MarketplaceOrderFactory;
use Webkul\Marketplace\Helper\Data as MarketplaceHelper;

class SalesOrderLoadAfter implements ObserverInterface {

    /**
     * @var OrderExtensionFactory
     */
    protected $extensionFactory;

    /**
     * @var LocationCollection
     */
    protected $locationCollection;

    /**
     * @var MarketplaceOrderFactory
     */
    protected $marketplaceOrderFactory;

    /**
     * @var MarketplaceHelper
     */
    protected $marketplaceHelper;

    /**
     * @param OrderExtensionFactory $extensionFactory
     * @param LocationCollection $locationCollection
     */
    public function __construct(
    OrderExtensionFactory $extensionFactory, LocationCollection $locationCollection, MarketplaceHelper $marketplaceHelper, MarketplaceOrderFactory $marketplaceOrderFactory
    ) {
        $this->extensionFactory = $extensionFactory;
        $this->locationCollection = $locationCollection;
        $this->marketplaceOrderFactory = $marketplaceOrderFactory;
        $this->marketplaceHelper = $marketplaceHelper;
    }

    /**
     * sales order get after event handler
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer) {
        $order = $observer->getOrder();
        $extensionAttributes = $order->getExtensionAttributes();
        if ($extensionAttributes === null) {
            $extensionAttributes = $this->extensionFactory->create();
        }
        $deliveryType = $order->getData('delivery_type');
        $deliveryTime = $order->getData('delivery_time');
        $customerNotes = $order->getData('customer_notes');
        $conviencefees = $order->getData('fee');
        $storeUniqueName = $order->getData('store_unique_name');
        $orderfeedbackToken = $order->getData('order_review_token');
        $extensionAttributes->setDeliveryType($deliveryType);
        $extensionAttributes->setDeliveryTime($deliveryTime);
        $extensionAttributes->setCustomerNotes($customerNotes);
        $extensionAttributes->setStoreUniqueName($storeUniqueName);
        $extensionAttributes->setFeedbackToken($orderfeedbackToken);
        $sellerId = false;
        $collection = $this->marketplaceOrderFactory->create()
                ->addFieldToFilter('order_id', $order->getEntityId())
                ->getFirstItem();
        $result = [];

        $sellerId = $collection->getSellerId();

        if ($sellerId !== false) {
            $locationsColl = $this->locationCollection
                    ->addFieldToFilter('seller_id', $sellerId);
            $sellerColl = $this->marketplaceHelper->getSellerDataBySellerId($sellerId);
            $isConglomerate = $this->marketplaceHelper->isConglomerate($sellerId);
            if ($sellerColl->count() > 0) {
                $sellerData = $sellerColl->getFirstItem();
                $result['phone_number'] = $sellerData->getData('store_manager_mobile_no');
                if ($isConglomerate == 1) {
                    $locationsColl->addFieldToFilter('store_unique_name', $storeUniqueName);
                    //$locationsColl->addFieldToFilter('is_active', 1);
                }
                if ($locationsColl->count() > 0) {
                    $locationData = $locationsColl->getFirstItem();
                    $result['street_address'] = $locationData->getData('store_street_address');
                }
            }
        }
        if (!isset($result['street_address'])) {
            $result['street_address'] = null;
        }
        $extensionAttributes->setSellerInformation([$result]);
        $conviencefeesArray = ["fee" => $conviencefees];
        $extensionAttributes->setAdditionalCharges([$conviencefeesArray]);
        $order->setExtensionAttributes($extensionAttributes);

        return;
    }

}
