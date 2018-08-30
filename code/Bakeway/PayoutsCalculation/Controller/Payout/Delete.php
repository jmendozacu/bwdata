<?php

namespace Bakeway\PayoutsCalculation\Controller\Payout;

use Symfony\Component\Config\Definition\Exception\Exception;

class Delete extends \Magento\Framework\App\Action\Action
{

    protected $salesListCollection;
    protected $order;
    protected $sales;
    protected $salerTransaction;

    /**
     * Construct
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Webkul\Marketplace\Model\ResourceModel\Saleslist\CollectionFactory $salesListCollection
     * @param \Webkul\Marketplace\Model\Saleslist $sales
     * @param \Webkul\Marketplace\Model\Sellertransaction $salerTransaction
     * @param \Magento\Sales\Model\OrderFactory $order
     */
    public function __construct(\Magento\Framework\App\Action\Context $context,
            \Webkul\Marketplace\Model\ResourceModel\Saleslist\CollectionFactory $salesListCollection,
            \Webkul\Marketplace\Model\Saleslist $sales,
            \Webkul\Marketplace\Model\Sellertransaction $salerTransaction,
            \Magento\Sales\Model\OrderFactory $order)
    {
        parent::__construct($context);
        $this->salesListCollection = $salesListCollection;
        $this->order = $order;
        $this->sales = $sales;
        $this->salerTransaction = $salerTransaction;
    }

    /**
     * Function to delete pay out
     */
    public function execute()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $resource = $objectManager->get('Magento\Framework\App\ResourceConnection');
        $connection = $resource->getConnection();
        $tableName = $resource->getTableName('sales_order');
        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/payout.log');
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);
        //seller id between 7 to 14 aug 2018
        $sellerIds = array(631, 634, 638, 639, 640, 641, 646, 649, 650, 654,
            656, 657, 660, 671, 682, 731, 784, 789, 826, 830, 850, 853, 861,
            913, 967, 1070, 1098, 1164, 1176, 1266, 1267, 1374, 1637, 1691,
            1699, 1700, 1706, 1728, 1773, 1774, 1850, 1954, 1989, 2015, 2017,
            2194, 2602, 3230, 3252, 3289, 3315, 3404, 3406, 3408, 3447, 3478,
            3540, 3546, 3648
        );

        $currentDateTime = new \DateTime('now',
                new \DateTimezone("Asia/Kolkata"));
        $currentDateString = $currentDateTime->format('Y-m-d H:i:s');
        $startInterval = date('Y-m-d H:i:s',
                strtotime('-7 days', strtotime($currentDateString)));
        try {
            foreach ($sellerIds as $sellerId) {
                $salesLists = $this->salesListCollection->create()->addFieldToFilter('seller_id',
                                $sellerId)
                        ->addFieldToFilter('main_table.created_at',
                                ['gteq' => $startInterval])
                        ->addFieldToFilter('main_table.created_at',
                        ['lteq' => $currentDateString]);
                foreach ($salesLists as $sales) {
                    $orderId = $sales->getOrderId();
                    $logger->info('Oder Id: ' . $orderId);
                    $order = $this->order->create()->load($orderId);
                    $createdAt = $order->getCreatedAt();
                    $sql = "Update " . $tableName . " set updated_at ='" . $createdAt . "' where entity_id = $orderId";
                    $connection->query($sql);
                    
                    $saleslisttable = "marketplace_saleslist";
                    $salesListId = $sales->getEntityId();
                    $logger->info('Sales List Id: ' . $salesListId);
                    $salesModel = $this->sales->load($salesListId);
                    $salesCreatedAt = $salesModel->getCreatedAt();
                    $salesModel->setUpdatedAt($salesCreatedAt);
                    $salesModel->setPaidStatus(0);
                    $salesModel->setCpprostatus(1);
                    $salesModel->setTransId(0);
                    $salesModel->save();
                    $sql = "Update " . $saleslisttable . " set updated_at ='" . $salesCreatedAt . "' where entity_id = $salesListId";
                    $connection->query($sql);
                }
                $transactionCollection = $this->salerTransaction->getCollection()->addFieldToFilter('seller_id',
                                $sellerId)
                        ->addFieldToFilter('main_table.created_at',
                                ['gteq' => $startInterval])
                        ->addFieldToFilter('main_table.created_at',
                        ['lteq' => $currentDateString]);
                foreach ($transactionCollection as $transaction) {
                    $entityId = $transaction->getEntityId();
                    $transactionModel = $this->salerTransaction->load($entityId);
                    $transactionModel->delete();
                }
            }
        } catch (Exception $e) {
            $logger->info($e->getMessage());
        }
    }

}
