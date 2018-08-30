<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace Bakeway\Reports\Block\Adminhtml\Orders\Renderer;

use Magento\Framework\DataObject;

/**
 * Description of TotalSellerAmount
 *
 * @author Admin
 */
class TotalSellerAmount extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer
{

    /** @var \Webkul\Marketplace\Block\Order\View */
    protected $block;

    /** @var \Magento\Sales\Model\Order */
    protected $order;

    /** @var \Webkul\Marketplace\Helper\Data */
    protected $helper;

    /** @var \Bakeway\PayoutsCalculation\Helper\Data */
    protected $payoutHelper;

    /** @var \Webkul\Marketplace\Model\Orders */
    protected $marketplaceOrder;

    /** @var \Webkul\Marketplace\Model\ResourceModel\Orders\Collection */
    protected $marketplaceOrderCollection;

    /** @var \Webkul\Marketplace\Model\Saleslist */
    protected $saleslist;

    /**
     * Construct
     * @param \Magento\Backend\Block\Context $context
     * @param array $data
     */
    public function __construct(\Magento\Backend\Block\Context $context,
            \Webkul\Marketplace\Block\Order\View $block,
            \Magento\Sales\Model\Order $order,
            \Webkul\Marketplace\Helper\Data $helper,
            \Bakeway\PayoutsCalculation\Helper\Data $payoutHelper,
            \Webkul\Marketplace\Model\Orders $marketplaceOrder,
            \Webkul\Marketplace\Model\ResourceModel\Orders\Collection $marketplaceOrderCollection,
            \Webkul\Marketplace\Model\Saleslist $saleslist,
            array $data = array())
    {
        parent::__construct($context, $data);
        $this->block = $block;
        $this->order = $order;
        $this->helper = $helper;
        $this->payoutHelper = $payoutHelper;
        $this->marketplaceOrder = $marketplaceOrder;
        $this->marketplaceOrderCollection = $marketplaceOrderCollection;
        $this->saleslist = $saleslist;
    }

    /**
     * Renderer
     * @param DataObject $row
     * @return type
     */
    public function render(DataObject $row)
    {
        $actualSellerAmount = 0;
        $order = $this->order->loadByIncrementId($row->getIncrementId());
        $mageOrderId = $order->getEntityId();
        $items = $order->getItemsCollection();
        $vendorSubtotal = $codchargesTotal = $totaltax = $couponamount = $vendortotaltax = $refundedShippingAmount = $tcsAmount = $paymentGatewayAmount = $shippingamount = 0;
        $paymentCode = $order->getPayment()->getMethod();
        $deliveryFlag = $order->getData('grab_delivery_flag');
        foreach ($items as $item) {
            $sellerOrderslist = $this->getSellerOrdersList(
                    $mageOrderId, $item->getProductId(), $item->getItemId()
            );
            $sellerItemCost = $codchargesPeritem = $totaltaxPeritem = $couponcharges = 0;
            foreach ($sellerOrderslist as $sellerItem) {
                $sellerItemCost = $sellerItem->getActualSellerAmount();
                if ($paymentCode == 'mpcashondelivery') {
                    $codchargesPeritem = $sellerItem->getCodCharges();
                }
                $totaltaxPeritem = $sellerItem->getTotalTax();
                $couponcharges = $sellerItem->getAppliedCouponAmount();
            }

            $vendorSubtotal += $sellerItemCost;
            $codchargesTotal += $codchargesPeritem;
            $totaltax += $totaltaxPeritem;
            $couponamount += $couponcharges;
        }

        $taxToSeller = $this->helper->getConfigTaxManage();
        $marketplaceOrders = $this->getSellerOrderInfo($mageOrderId);
        foreach ($marketplaceOrders as $order) {
            $refundedShippingAmount = $order->getRefundedShippingCharges();
            $taxToSeller = $order['tax_to_seller'];
            $tcsAmount = $order->getTcsAmount();
            $paymentGatewayAmount = $order->getPaymentGatewayFee();
            $shippingamount = $order->getShippingCharges();
        }

        if ($taxToSeller) {
            $vendortotaltax = $totaltax;
        }

        $tracking = $this->getOrderinfo($mageOrderId);
        if (isset($tracking) && !empty($tracking)) {
            $taxPaidByBakeway = $tracking->getData('tax_paid_by_bakeway');
            if ($taxPaidByBakeway == 1) {
                $vendortotaltax = 0;
                $shippingamount = $this->payoutHelper->getDeliveryFeeExclTax($shippingamount);
            }
        }

        if (!empty($deliveryFlag)) {
            $baseOne = $vendorSubtotal + $codchargesTotal + $vendortotaltax;
        } else {
            $baseOne = $vendorSubtotal + $shippingamount + $codchargesTotal + $vendortotaltax;
        }

        $baseTwo = $refundedShippingAmount + $couponamount + $tcsAmount + $paymentGatewayAmount;

        $base = $baseOne - $baseTwo;
        $actualSellerAmount = $this->block->getOrderedPricebyorder($order, $base);

        return $actualSellerAmount;
    }

    /**
     * Get Order info
     * @param type $orderId
     * @return array
     */
    private function getOrderinfo($orderId)
    {
        $data = [];
        $model = $this->marketplaceOrder
                ->getCollection()
                ->addFieldToFilter(
                'order_id', $orderId
        );

        $salesOrder = $this->marketplaceOrderCollection->getTable('sales_order');

        $model->getSelect()->join(
                $salesOrder . ' as so', 'main_table.order_id = so.entity_id',
                ["order_approval_status" => "order_approval_status", "status" => "status"]
        )->where("so.order_approval_status=1");
        foreach ($model as $tracking) {
            $data = $tracking;
        }

        return $data;
    }

    /**
     * Get seller order list
     * @param type $orderId
     * @param type $proId
     * @param type $itemId
     * @return array
     */
    private function getSellerOrdersList($orderId,
            $proId,
            $itemId)
    {
        $collection = $this->saleslist
                ->getCollection()
                ->addFieldToFilter(
                        'order_id', ['eq' => $orderId]
                )
                ->addFieldToFilter(
                        'mageproduct_id', ['eq' => $proId]
                )
                ->addFieldToFilter(
                        'order_item_id', ['eq' => $itemId]
                )
                ->setOrder('order_id', 'DESC');
        return $collection;
    }

    /**
     * Seller order information
     * @param type $orderId
     * @return array
     */
    private function getSellerOrderInfo($orderId)
    {
        $collection = $this->marketplaceOrder->getCollection()
                ->addFieldToFilter(
                'order_id', ['eq' => $orderId]
        );
        return $collection;
    }

}
