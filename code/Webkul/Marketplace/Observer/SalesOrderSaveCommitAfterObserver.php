<?php
/**
 * Webkul Software.
 *
 * @category  Webkul
 * @package   Webkul_Marketplace
 * @author    Webkul
 * @copyright Copyright (c) 2010-2017 Webkul Software Private Limited (https://webkul.com)
 * @license   https://store.webkul.com/license.html
 */

namespace Webkul\Marketplace\Observer;

use Magento\Framework\Event\ObserverInterface;
use Bakeway\PayoutsCalculation\Helper\Data as PayoutsHelper;

/**
 * Webkul Marketplace SalesOrderSaveCommitAfterObserver Observer Model.
 */
class SalesOrderSaveCommitAfterObserver implements ObserverInterface
{
    /**
     * @var eventManager
     */
    protected $_eventManager;

    /**
     * @var ObjectManagerInterface
     */
    protected $_objectManager;

    /**
     * @var Session
     */
    protected $_customerSession;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $_date;

    protected $payoutsHelper;

    /**
     * @param \Magento\Framework\Event\Manager            $eventManager
     * @param \Magento\Framework\ObjectManagerInterface   $objectManager
     * @param \Magento\Customer\Model\Session             $customerSession
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $date
     * @param PayoutsHelper                               $payoutsHelper
     */
    public function __construct(
        \Magento\Framework\Event\Manager $eventManager,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        PayoutsHelper $payoutsHelper
    ) {
        $this->_eventManager = $eventManager;
        $this->_objectManager = $objectManager;
        $this->_customerSession = $customerSession;
        $this->_date = $date;
        $this->payoutsHelper = $payoutsHelper;
    }

    /**
     * Sales order save commmit after on order complete state event handler.
     *
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var $orderInstance Order */
        $order = $observer->getOrder();
        $lastOrderId = $observer->getOrder()->getId();
        $helper = $this->_objectManager->get('Webkul\Marketplace\Helper\Data');
        if ($order->getState() == 'complete') {
            /*
            * Calculate cod and shipping charges if applied
            */
            $paymentCode = '';
            if ($order->getPayment()) {
                $paymentCode = $order->getPayment()->getMethod();
            }
            $ordercollection = $this->_objectManager->create(
                'Webkul\Marketplace\Model\Saleslist'
            )
            ->getCollection()
            ->addFieldToFilter('order_id', $lastOrderId)
            ->addFieldToFilter(
                'cpprostatus',
                \Webkul\Marketplace\Model\Saleslist::PAID_STATUS_PENDING
            );
            $taxPaidByBakeway = 0;
            foreach ($ordercollection as $item) {
                $sellerId = $item->getSellerId();
                $taxAmount = $item['total_tax'];

                $taxToSeller = $helper->getConfigTaxManage();
                $marketplaceOrders = $this->_objectManager->create(
                    'Webkul\Marketplace\Model\Orders'
                )
                ->getCollection()
                ->addFieldToFilter('order_id', $lastOrderId)
                ->addFieldToFilter('seller_id', $item['seller_id']);
                foreach ($marketplaceOrders as $tracking) {
                    $taxToSeller = $tracking['tax_to_seller'];
                    /**
                     * checking if tax paid by bakeway
                     */
                    $taxPaidByBakeway = $tracking['tax_paid_by_bakeway'];
                }
                if (!$taxToSeller) {
                    $taxAmount = 0;
                }

                $shippingCharges = 0;
                /**** Bakeway Fields ****/
                $tcsFee = 0;
                $paymentGatewayFee = 0;
                $codCharges = $item->getCodCharges();
                /*
                 * Calculate cod and shipping charges if applied
                 */
                if ($item->getIsShipping() == 1) {
                    $marketplaceOrders = $this->_objectManager->create(
                        'Webkul\Marketplace\Model\Orders'
                    )
                    ->getCollection()
                    ->addFieldToFilter('order_id', $lastOrderId)
                    ->addFieldToFilter('seller_id', $item['seller_id']);
                    foreach ($marketplaceOrders as $tracking) {
                        $shippingamount = $tracking->getShippingCharges();
                        $refundedShippingAmount = $tracking->getRefundedShippingCharges();
                        $shippingCharges = $shippingamount - $refundedShippingAmount;
                        /**
                         * checking if tax paid by bakeway
                         */
                        $taxPaidByBakeway = $tracking['tax_paid_by_bakeway'];
                        $tcsFee = $tracking->getTcsAmount();
                        $paymentGatewayFee = $tracking->getPaymentGatewayFee();
                    }
                }
                if ($taxPaidByBakeway == 1) {
                    $taxAmount = 0;
                    $shippingCharges = $this->payoutsHelper->getDeliveryFeeExclTax($shippingCharges);
                }
                $totalTaxShipping = $taxAmount + $codCharges + $shippingCharges - $item['applied_coupon_amount'];
                $actparterprocost = $item->getActualSellerAmount() + $totalTaxShipping -$tcsFee - $paymentGatewayFee;
                $totalamount = $item->getTotalAmount() + $totalTaxShipping;
                $codCharges = 0;

                $collectionverifyread = $this->_objectManager->create(
                    'Webkul\Marketplace\Model\Saleperpartner'
                )->getCollection();
                $collectionverifyread->addFieldToFilter(
                    'seller_id',
                    $sellerId
                );
                if ($collectionverifyread->getSize() >= 1) {
                    foreach ($collectionverifyread as $verifyrow) {
                        $totalsale = $verifyrow->getTotalSale() + $totalamount;
                        $totalremain = $verifyrow->getAmountRemain() + $actparterprocost;
                        $verifyrow->setTotalSale($totalsale);
                        $verifyrow->setAmountRemain($totalremain);
                        //$verifyrow->setCommissionRate($item->getCommissionRate());
                        $totalcommission = $verifyrow->getTotalCommission() +
                        ($totalamount - $actparterprocost);
                        $verifyrow->setTotalCommission($totalcommission);
                        $verifyrow->setUpdatedAt($this->_date->gmtDate());
                        $verifyrow->save();
                    }
                } else {
                    $collectionf = $this->_objectManager->create(
                        'Webkul\Marketplace\Model\Saleperpartner'
                    );
                    $collectionf->setSellerId($sellerId);
                    $collectionf->setTotalSale($totalamount);
                    $collectionf->setAmountRemain($actparterprocost);
                    //$collectionf->setCommissionRate($item->getCommissionRate());
                    $totalcommission = $totalamount - $actparterprocost;
                    $collectionf->setTotalCommission($totalcommission);
                    $collectionf->setCreatedAt($this->_date->gmtDate());
                    $collectionf->setUpdatedAt($this->_date->gmtDate());
                    $collectionf->save();
                }
                if ($sellerId) {
                    $ordercount = 0;
                    $feedbackcount = 0;
                    $feedcountid = 0;
                    $collectionfeed = $this->_objectManager->create(
                        'Webkul\Marketplace\Model\Feedbackcount'
                    )->getCollection()
                    ->addFieldToFilter(
                        'seller_id',
                        $sellerId
                    );
                    foreach ($collectionfeed as $value) {
                        $feedcountid = $value->getEntityId();
                        $ordercount = $value->getOrderCount();
                        $feedbackcount = $value->getFeedbackCount();
                    }
                    $collectionfeed = $this->_objectManager->create(
                        'Webkul\Marketplace\Model\Feedbackcount'
                    )->load($feedcountid);
                    $collectionfeed->setBuyerId($order->getCustomerId());
                    $collectionfeed->setSellerId($sellerId);
                    $collectionfeed->setOrderCount($ordercount + 1);
                    $collectionfeed->setFeedbackCount($feedbackcount);
                    $collectionfeed->setCreatedAt($this->_date->gmtDate());
                    $collectionfeed->setUpdatedAt($this->_date->gmtDate());
                    $collectionfeed->save();
                }
                $item->setUpdatedAt($this->_date->gmtDate());
                $item->setCpprostatus(
                    \Webkul\Marketplace\Model\Saleslist::PAID_STATUS_COMPLETE
                )->save();
            }
        }
    }
}
