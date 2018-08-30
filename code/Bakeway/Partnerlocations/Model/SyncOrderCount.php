<?php
/**
 * Bakeway
 *
 * @category  Bakeway
 * @package   Bakeway_Partnerlocations
 * @author    Bakeway
 */

namespace Bakeway\Partnerlocations\Model;

use Bakeway\Partnerlocations\Api\SyncOrderCountInterface;
use Bakeway\Partnerlocations\Model\ResourceModel\Partnerlocations as LocationResource;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Bakeway\Partnerlocations\Model\PartnerlocationsFactory as LocationModelFactory;
use Bakeway\Partnerlocations\Model\ResourceModel\Partnerlocations\CollectionFactory as LocationFactory;

class SyncOrderCount implements SyncOrderCountInterface
{
    protected $locationResource;

    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\CollectionFactory
     */
    protected $orderCollectionFactory;

    protected $locationModel;

    protected $locationFactory;

    public function __construct(
        LocationResource $locationResource,
        OrderCollectionFactory $orderCollectionFactory,
        LocationModelFactory $locationModel,
        LocationFactory $locationFactory
    )
    {
        $this->locationResource = $locationResource;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->locationModel = $locationModel;
        $this->locationFactory = $locationFactory;
    }

    /**
     * SyncOrder Count
     * @return int $orderId
     */
    public function SyncOrderCount() {
        $validCountStatuses = ['processing','bakeway_partner_accepted', 'bakeway_order_ready', 'bakeway_order_out_for_delivery', 'complete', 'pending', 'bakeway_partner_rejected'];
        /*$lastSyncedEntity = $this->locationResource->getLastSyncedOrderId();
        $lastSyncedId = false;
        if (isset($lastSyncedEntity) &&
            $lastSyncedEntity > 0) {
            $lastSyncedId = $lastSyncedEntity;
        }*/

        $currentDateTime = new \DateTime('now', new \DateTimezone("Asia/Kolkata"));
        $today = $currentDateTime->format('Y-m-d h:i:s');
        $currentDateTime->modify("-1 month");
        $lastMonthFromToday = $currentDateTime->format('Y-m-d h:i:s');

        $orderCollection = $this->orderCollectionFactory->create()
            ->addFieldToSelect(['entity_id', 'store_unique_name']);
        $orderCollection->getSelect()->joinInner(['mp_order' => $orderCollection->getTable('marketplace_orders')],
            'main_table.entity_id=mp_order.order_id',
            ['seller_id']);
        $orderCollection->addFieldToFilter('main_table.status', ['in'=>$validCountStatuses]);
        $orderCollection->addFieldToFilter('main_table.created_at', ['gteq'=>$lastMonthFromToday]);
        $orderCollection->addFieldToFilter('main_table.created_at', ['lteq'=>$today]);
        /*if ($lastSyncedId !== false) {
            $orderCollection->addFieldToFilter('main_table.entity_id', ['gt'=>$lastSyncedId]);
        }*/
        $orderCollection->getSelect()->group('main_table.entity_id');

        $locationCollection = $this->locationFactory->create();
        $locArr = [];
        $singleLocArray = [];

        foreach ($locationCollection as $loc) {
            $sellerId = $loc->getData('seller_id');
            $uniqueName = $loc->getData('store_unique_name');
            $id = $loc->getData('id');
            /**
             * Order count keeping as 0
             * For incremental order count uncomment below line.
             */
            //$orderCount = $loc->getData('number_of_orders');
            /**
             * Order count keeping as 0
             * For incremental order count uncomment below line.
             */
            $orderCount = 0;
            $oldOrderCount = $loc->getData('number_of_orders');
            if (($orderCount != $oldOrderCount) || empty($oldOrderCount)) {
                $loc->setData('number_of_orders', 0);
                $loc->save();
            }

            $locArr[$sellerId.'#'.$uniqueName] = $id.'#'.$orderCount;
            $singleLocArray[$sellerId.'#'] = $id.'#'.$orderCount;
        }
        $finalLocArray = [];
        $lastSyncedId = 0;
        foreach ($orderCollection as $order) {
            $countArr = [];
            $sellerId = $order->getData('seller_id');
            $uniqueName = $order->getData('store_unique_name');
            if (isset($locArr[$sellerId.'#'.$uniqueName])) {
                $countArr = explode('#', $locArr[$sellerId.'#'.$uniqueName]);
            } elseif (isset($singleLocArray[$sellerId.'#'])) {
                $countArr = explode('#', $singleLocArray[$sellerId.'#']);
            }
            if (isset($countArr[0]) && isset($countArr[1])) {
                if (isset($finalLocArray[$countArr[0]])) {
                    $countArr[1] = $finalLocArray[$countArr[0]];
                }
                if ($countArr[1] == 0 || $countArr[1] == '' || empty($countArr[1])) {
                    $orderCount = 1;
                } else {
                    $orderCount = $countArr[1]+1;
                    if (isset($finalLocArray[$countArr[0]])) {
                        $finalLocArray[$countArr[0]] = $orderCount;
                    }
                }
                if (!isset($finalLocArray[$countArr[0]])) {
                    $finalLocArray[$countArr[0]] = $orderCount;
                }
            }
            $lastSyncedId = $order->getData('entity_id');
        }
        foreach ($finalLocArray as $key=>$value) {
            $locModel = $this->locationModel->create()->load($key);
            $locModel->setData('number_of_orders', $value);
            $locModel->save();
        }
        //$this->locationResource->setLastSyncedOrderId($lastSyncedId);
        return $lastSyncedId;
    }
}