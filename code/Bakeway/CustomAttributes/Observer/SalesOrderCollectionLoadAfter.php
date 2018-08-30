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
use Bakeway\OrderstatusEmail\Block\Order\Email\Items as OrderItemEmail;

class SalesOrderCollectionLoadAfter implements ObserverInterface
{
    /**
     * @var OrderExtensionFactory
     */
    protected $extensionFactory;

    /**
     * @var OrderItemEmail
     */
    protected $orderItemEmail;

    /**
     * @param OrderExtensionFactory $extensionFactory
     * @param OrderItemEmail $orderItemEmail
     */
    public function __construct(
        OrderExtensionFactory $extensionFactory,
        OrderItemEmail $orderItemEmail
    ) {
        $this->extensionFactory = $extensionFactory;
        $this->orderItemEmail = $orderItemEmail;
    }

    /**
     * sales order get after event handler
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $orders = $observer->getOrderCollection();

        foreach ($orders as $order){
            $extensionAttributes = $order->getExtensionAttributes();
            if($extensionAttributes === null) {
                $extensionAttributes = $this->extensionFactory->create();
            }
            $deliveryType = $order->getData('delivery_type');
            $deliveryTime = $order->getData('delivery_time');
            $customerNotes = $order->getData('customer_notes');
            $conviencefees = $order->getData('fee');
            $extensionAttributes->setDeliveryType($deliveryType);
            $extensionAttributes->setDeliveryTime($deliveryTime);
            $extensionAttributes->setCustomerNotes($customerNotes);
            $sellerAddress = $this->orderItemEmail->getSellerAddressDetails(null, $order);
            if (!isset($sellerAddress['street_address'])) {
                $sellerAddress['street_address'] = null;
            }
            $extensionAttributes->setSellerInformation([$sellerAddress]);
            $conviencefeesArray = ["fee"=>$conviencefees];
            $extensionAttributes->setAdditionalCharges([$conviencefeesArray]);
            $order->setExtensionAttributes($extensionAttributes);
        }
        return;
    }
}
