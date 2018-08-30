<?php

namespace Bakeway\GrabIntigration\Model;

use Bakeway\GrabIntigration\Api\CallbackDeliveryRepositoryInterface;
use Magento\Sales\Api\OrderRepositoryInterface as OrderRepositoryInterface;
use Symfony\Component\Config\Definition\Exception\Exception;
use Bakeway\GrabIntigration\Helper\Data as GrabIntigrationHelper;

class CallbackDeliveryRepository implements CallbackDeliveryRepositoryInterface {

     CONST ORDER_DELIVERED_CODE = 6;

    /**
     * @param OrderRepositoryInterface
     */
    protected $orderRepositoryInterface;

    /**
     * @param \Webkul\Marketplace\Model\SellerFactory $sellerFactory
     */
    protected $sellerFactory;

    /**
     * @var GrabIntigrationHelper
     */
    protected $grabIntigrationHelper;

    /**
     * VendorInformationRepository constructor.
     * @param SellerHelper $sellerHelper
     */
    public function __construct(
        OrderRepositoryInterface $orderRepositoryInterface,
        \Webkul\Marketplace\Model\SellerFactory $sellerFactory,
        GrabIntigrationHelper $grabIntigrationHelper
    ) {
        $this->orderRepositoryInterface = $orderRepositoryInterface;
        $this->sellerFactory = $sellerFactory;
        $this->grabIntigrationHelper = $grabIntigrationHelper;
    }

    /**
     * Update Customer Delivery status.
     *
     * @api
     * @param int $orderStatus
     * @param int $grabOrderId
     * @param int $clientOrderId
     * @param int $merchantBillNo
     * @param int $merchantId
     * @param string $riderName
     * @param int $riderPhone
     * @param string $riderLatitude
     * @param string $riderLongitude
     * @param int $expectedDeliveryTime
     * @param string $dttm
     * @return array
     * @throws \Magento\Framework\Exception\NotFoundException
     */
    public function updateDeliveryStatus($orderStatus, $grabOrderId , $clientOrderId, $merchantBillNo, $merchantId, $riderName,
                                         $riderPhone, $riderLatitude, $riderLongitude, $expectedDeliveryTime,$dttm)
    {
        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/grabAPICallbackOrderStatus.log');
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);

        $status = [];
         if($orderStatus === self::ORDER_DELIVERED_CODE){
          if(isset($clientOrderId))
          {
              $logger->info("grab order status ".$grabOrderId);

              $orderObject = $this->orderRepositoryInterface->get($clientOrderId);


              $orderObject->setState(\Magento\Sales\Model\Order::STATE_COMPLETE);
              $orderObject->setStatus(\Magento\Sales\Model\Order::STATE_COMPLETE);
              try{
                  $orderObject->save();
                  $status['success'] = true;
              }catch(Exception $e){
                  echo $e->getMessage();
              }

          }


        }else{
             $statusList = $this->grabIntigrationHelper->getGrabOrderStatus();
             $orderStatusDetails = $statusList[$orderStatus];
             $logger->info("grab order status ".$orderStatusDetails);
             $status['error'] = $orderStatusDetails;

        }



        return json_decode(json_encode($status),false);
    }



}
