<?php
/**
 * Bakeway
 *
 * @category  Bakeway
 * @package   Bakeway_Crons
 * @author    Bakeway
 */

namespace Bakeway\Crons\Model;

use Magento\Sales\Model\OrderFactory as OrderFactory;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Symfony\Component\Config\Definition\Exception\Exception;
use Magento\Sales\Model\Order\Email\Sender\OrderSender as OrderSender;
use Bakeway\VendorNotification\Helper\Data as VendornotificationHelper;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface as TimezoneInterface;

class SetOrderEmailSmsPushNotificationTrigger {

    CONST SLEEP_PARAMETER = 10;
    CONST MAX_TIME_INPUT = 45;

    /**
     * @var OrderFactory
     */
    protected $orderFactory;
    /**
     * @var OrderCollectionFactory
     */
    protected $orderCollectionFactory;

    /**
     * @var OrderSender
     */
    protected $orderSender;

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $_objectManager;

    /**
     * @var VendornotificationHelper
     */
    protected $vendornotificationHelper;

    public function __construct(
        OrderFactory $orderFactory,
        OrderCollectionFactory $orderCollectionFactory,
        OrderSender $orderSender,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        VendornotificationHelper $vendornotificationHelper,
        TimezoneInterface $timezoneInterface
    )
    {
        $this->orderFactory = $orderFactory;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->orderSender = $orderSender;
        $this->_objectManager = $objectManager;
        $this->vendornotificationHelper = $vendornotificationHelper;
        $this->timezoneInterface = $timezoneInterface;
    }

    /**
     * fetch all orders which have sms , order ,email flags are 0
     * return void
     */
    public function Trigger()
    {

        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/ordernotifier_log.log');
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);
        $logger->info("-------------------------------------------------------------");
        $logger->info("----------------start cron of order notifier--------");
        /************************************************************************/
        /**************************order colleciton******************************/


        for ($i = 0; $i<=1; $i++) {

            $procsstartTime = $this->timezoneInterface->date()->format('Y-m-d H:i:s');
            $logger->info("process start ".$procsstartTime);

            $orderCollection = $this->orderCollectionFactory->create()
            ->addFieldToSelect(["status", "increment_id", "order_email_flag", "order_sms_flag", "order_push_notification_flag"])
            ->addFieldToFilter('status', array('in' => array('pending')))
            ->addFieldToFilter('order_email_flag', 0)
            ->addFieldToFilter('order_sms_flag', 0)
            ->addFieldToFilter('order_push_notification_flag', 0);
        //->addFieldToFilter('entity_id',["in"=>['6076','6073']]);
            $orderCollection->getSelect()->join(array('mo' => 'marketplace_orders'), 'main_table.entity_id = mo.order_id', array('mo.order_id'));

           if (!empty($orderCollection->count())) {
            foreach ($orderCollection as $order) {
                $orderId = $order->getIncrementId(); //get order id
                if (isset($orderId)) {
                    $orderObj = $this->orderFactory->create()->loadByIncrementId($orderId);
                    $orderObj->setOrderEmailFlag(true);
                    $orderObj->setOrderSmsFlag(true);
                        $orderObj->setOrderPushNotificationFlag(true);
                    try {
                        $this->orderSender->send($orderObj);
                        $logger->info("email has sent for order id " . $orderId);
                        $this->pushNotificationAndSms($orderObj->getEntityId());
                        /**
                         * update order after email,sms and push notification done
                         */
                        $orderObj->save();
                    } catch (Exception $e) {
                        $logger->info("error to status change for order id " . $orderId . " and error is " . $e->getMessage());

                    }
                }

             }

           }

            /**
             * const varriable for assigning sleep time in second
             */
            sleep(self::SLEEP_PARAMETER);

            $processendTime = $this->timezoneInterface->date()->format('Y-m-d H:i:s');
            $logger->info("process end ".$processendTime);

            $fromTime = new \DateTime($procsstartTime);
            $endTime = new \DateTime($processendTime);
            $interval = $fromTime->diff($endTime);
            $processtimeInterval = $interval->s;
            $logger->info("process taken time ".$processtimeInterval);
            if($processtimeInterval >= self::MAX_TIME_INPUT){
                $logger->info("loop is exit and taken time is ".$processtimeInterval+ self::SLEEP_PARAMETER);
                exit;
            }

    }
        $logger->info("----------------end cron of order notifier--------");

    }

    /**
     * @param $orderId
     */
    public function  pushNotificationAndSms($orderId){

        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/ordernotifier_log.log');
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);

        $sellerId = 0;
        $endpoints = array();
        $sellerOrder = $this->_objectManager->create(
            'Webkul\Marketplace\Model\Orders'
        )
            ->getCollection()
            ->addFieldToFilter('order_id', $orderId)
            ->addFieldToFilter('seller_id', ['neq' => 0]);

        $sellorderCount = $sellerOrder->count();
        if ($sellorderCount > 0) {
            $sellerId = $sellerOrder->getFirstItem()->getSellerId();
        }

        $logger->info("seller id => ".$sellerId." and  order id => ".$orderId);

        $sellerDevices = $this->_objectManager->create(
            'Bakeway\VendorNotification\Model\Sellerdevicedata'
        )
            ->getCollection()
            ->addFieldToFilter('seller_id', $sellerId)
            ->addFieldToFilter('is_dnd', '0')
            ->addFieldToFilter('platform_endpoint', ['neq' => Null])
            ->addFieldToSelect(['platform_endpoint', 'platform']);

        $deviceCount = $sellerDevices->count();
        if ($deviceCount > 0) {
            $endpoints = $sellerDevices;
        }

        /**
         * push notification script when order status update
         * awaiting confirmation/state(new) / status code(pending)
         */
        $_order = $this->_objectManager->create('\Magento\Sales\Model\Order')
            ->load($orderId);

        /** get order status* */
        $getOrderStatus = $_order->getStatus();
        switch ($getOrderStatus):
            case "pending_payment":
                break;
            case "pending":
                $logger->info("push notification and sms has sent for order id ".$orderId);
                $messageText = "Bakeway new order received of Rs." . $_order->getGrandTotal();
                $this->vendornotificationHelper->sendPushNotification($endpoints, $messageText, $orderId);
                $this->vendornotificationHelper->sendNewOrderSms($sellerId, $orderId);
                break;
        endswitch;
        return;
    }
}
