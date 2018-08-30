<?php

namespace Bakeway\PayoutsCalculation\Controller\Payout;

use Symfony\Component\Config\Definition\Exception\Exception;
use Magento\Framework\ObjectManagerInterface as objectManager;
use Bakeway\PayoutsCalculation\Helper\Data as Payoutshelper;
use Magento\Sales\Api\OrderRepositoryInterface;
class Script extends \Magento\Framework\App\Action\Action
{

    protected $salesListCollection;
    protected $order;
    protected $sales;
    protected $salerTransaction;
    protected $payouthelper;
    protected $orderRepositoryInterface;

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
            \Magento\Sales\Model\OrderFactory $order,
             Payoutshelper $payouthelper,
            OrderRepositoryInterface $orderRepositoryInterface)
    {
        parent::__construct($context);
        $this->salesListCollection = $salesListCollection;
        $this->order = $order;
        $this->sales = $sales;
        $this->salerTransaction = $salerTransaction;
        $this->payouthelper = $payouthelper;
        $this->orderRepositoryInterface = $orderRepositoryInterface;
    }

    
    /**
     * update paid_status and trans_id of order wise seller
     */
    public function execute()
    {
        
        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/payoutcal.log');
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);
        
        $filename = BP . '/var/import/manual_payout/Manual_Paid_Orders.csv';
        $fp = fopen($filename, "r");    
        $headerLine = true;
        $orderIds = [];
        while (($row = fgetcsv($fp, "5400", ",")) != FALSE) {
        if ($headerLine) {
        $headerLine = false;
        } else {
        $orderId =  $row[0]; //order id  
        $payOutDate  = $row[1]; //payout date

        $order = $this->order->create()->getCollection()
                           ->addFieldToFilter("increment_id",array("like"=>"%".$orderId."%"))
                           ->getFirstItem();
        $sellerId = $this->getSellerIdByOrderId($order->getEntityId());
        $sellerData[$order->getIncrementId()] = $sellerId;
        $orderIds[] = $order->getIncrementId();
        
        }
        
        }
        
        $salesListCollection =  $this->sales->getCollection()
                                ->addFieldToSelect("seller_id")
                                ->addFieldToFilter("magerealorder_id",["in"=> implode(",",$orderIds)]);
        $salesListCollection->getSelect()->group("seller_id");
        
          
        if(count($salesListCollection) > 0){
             foreach($salesListCollection as $sellerId){
                 $orderEntityId = [];
                  $sellerIds =  $sellerId['seller_id'];
                  $sellerOrders = array_keys($sellerData,$sellerIds);
                  if(count($sellerOrders) > 0){
                      foreach($sellerOrders as $sellerOrder){
                          $orderEntityId[] =  $sellerOrder;
                      }
                      
                  }
                  
          $sellerId =  $sellerId->getData("seller_id");
                  
          $payOutDate  = $row[1]; //payout date
          $transcationAmount = 200; //payout date
          $bakewayTrasAmount = 100;
          $sellerTransId = $this->checkTransId($sellerId ,$payOutDate);
          $bakewayTransId = $this->payouthelper->checkBakewayTransId();
          $logger->info("seller id ".$sellerId);
          $logger->info("seller id ".$sellerId ." antrans key are ".$sellerTransId." and bakeway trans key ".$bakewayTransId);  
         /**
          * adding record to seller_transaction
          */
         
            $sellerTrans = $this->_objectManager->create(
                'Webkul\Marketplace\Model\Sellertransaction'
            );
            $sellerTrans->setTransactionId($sellerTransId);
            $sellerTrans->setTransactionAmount($transcationAmount);
            $sellerTrans->setType('Manual');
            $sellerTrans->setMethod('Manual');
            $sellerTrans->setSellerId($sellerId);
            $sellerTrans->setCustomNote(Null);
            $sellerTrans->setCreatedAt("2018-08-17 12:54:50");
            $sellerTrans->setUpdatedAt("2018-08-17 12:54:50");
            $sellerTrans->setSellerPendingNotification(1);
            $sellerTrans->setTransactionStatus(2);
            $sellerTrans->setTransactionInvoiceNumber($this->payouthelper->getTransactionInvoiceNo());
            $sellerTrans = $sellerTrans->save();
            $sellerTransEntityId = $sellerTrans->getId();
            $transactionNumber = $sellerTrans->getTransactionId();
            
            $logger->info("invoice no ".$this->payouthelper->getTransactionInvoiceNo());
            $logger->info("seller trans table entity id is ".$sellerTransEntityId);
            $logger->info("seller trans table trans id is ".$transactionNumber);
            /**
             *add record to bakeway trans. table 
             */
          
         $bakewayTrans = $this->_objectManager->create(
               'Bakeway\PayoutsCalculation\Model\Bakewaytransaction'
           );
           $bakewayTrans->setTransactionId($bakewayTransId);
           $bakewayTrans->setSellerTransactionId($sellerTransId);
           $bakewayTrans->setTransactionAmount($bakewayTrasAmount);
           $bakewayTrans->setCreatedAt("2018-08-17 12:54:50");
           $bakewayTrans->setUpdatedAt("2018-08-17 12:54:50");
           $bakewayTrans->setTransactionStatus(2);
           $bakewayTrans = $bakewayTrans->save();
           $bakewayTransEntityId = $bakewayTrans->getId();
           $transactionNumber = $bakewayTrans->getTransactionId();
           $logger->info("bakeway trans table entity id is ".$bakewayTransEntityId);
           $logger->info("bakeway trans table trans id is ".$transactionNumber);
           
          
           $saleslistids = implode(",",$orderEntityId);
           $collection = $this->_objectManager->create(
                    'Webkul\Marketplace\Model\Saleslist'
                )->getCollection()
                    ->addFieldToFilter('magerealorder_id', ['in' => $saleslistids])
                    ->addFieldToFilter('order_id', ['neq' => 0])
                    ->addFieldToFilter('paid_status', 0)
                    ->addFieldToFilter('cpprostatus', ['neq' => 0]);
           $collection->getSelect()->where('parent_item_id IS NULL'); 
          
           foreach ($collection as $entityId) {
                $logger->info("updating these value in trans table id is ".$entityId->getEntityId());
                    $collection->setSalesListData(
                        $entityId->getEntityId(),
                        ['trans_id' => $sellerTransEntityId]
                    );
                }
                  
             }
             
        }
     
     
      }
    
    
    /**
     * 
     * @param type $sellerId
     * @param type $payOutDate
     * @return type
     */
    public function checkTransId($sellerId ,$payOutDate) { 
        
         $transKey =  $this->payouthelper->checkSellerTransId();
         $sellerTransKey = $this->_objectManager->create(
                        'Webkul\Marketplace\Model\Sellertransaction'
                    )->getCollection()
                 ->addFieldToFilter("seller_id",['eq'=>$sellerId])
                 ->addFieldToFilter("created_at",['eq'=>$payOutDate])
                 ->addFieldToFilter("transaction_id",['eq'=>$transKey])
                 ->getFirstItem();
         if(!empty($sellerTransKey->getEntity())){
             
             $transKey = $sellerTransKey->getTransactionId();
         }
        
        return $transKey;
        
    }
    
   public function getSellerIdByOrderId($orderId)
    {
        $sellerId = "";
        $collection = $this->_objectManager->create(
            'Webkul\Marketplace\Model\Orders')
            ->getCollection()
            ->addFieldToFilter('order_id', ['eq' => $orderId]);
        foreach ($collection as $order) {
            $sellerId =$order->getSellerId();
        }

        return $sellerId;
    }


}
