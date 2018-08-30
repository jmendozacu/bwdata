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

namespace Webkul\Marketplace\Ui\Component\Listing\Columns;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Webkul\Marketplace\Helper\Data as HelperData;
use Bakeway\PayoutsCalculation\Helper\Data as PayoutHelper;
use Webkul\Marketplace\Model\ResourceModel\Orders\CollectionFactory;

/**
 * Class Orderprodetails.
 */
class Orderprodetails extends Column
{
    /**
     * @var HelperData
     */
    protected $_helper;

    /**
     * @var CollectionFactory
     */
    protected $_collectionFactory;

    /**
     * @var PayoutHelper
     */
    protected $payoutHelper;

    /**
     * Constructor.
     *
     * @param ContextInterface   $context
     * @param UiComponentFactory $uiComponentFactory
     * @param HelperData         $helper
     * @param CollectionFactory  $collectionFactory
     * @param PayoutHelper       $payoutHelper
     * @param array              $components
     * @param array              $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        HelperData $helper,
        CollectionFactory $collectionFactory,
        PayoutHelper $payoutHelper,
        array $components = [],
        array $data = []
    ) {
        $this->_helper = $helper;
        $this->_collectionFactory = $collectionFactory;
        $this->payoutHelper = $payoutHelper;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * Prepare Data Source.
     *
     * @param array $dataSource
     *
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        $taxToSeller = $this->_helper->getConfigTaxManage();
        if (isset($dataSource['data']['items'])) {
            $fieldName = $this->getData('name');
            foreach ($dataSource['data']['items'] as &$item) {
                $item['total_shipping'] = 0;
                $totalshipping = 0;
                $appliedCouponAmount = $item['applied_coupon_amount'];

                $marketplaceOrders = $this->_collectionFactory->create()
                ->addFieldToFilter('order_id', $item['order_id'])
                ->addFieldToFilter('seller_id', $item['seller_id']);

                $resultData = $this->getOrderItemTaxShipping(
                    $marketplaceOrders,
                    $item,
                    $taxToSeller,
                    $totalshipping
                );

                $taxToSeller = $resultData['taxToSeller'];
                $totalshipping = $resultData['totalshipping'];
                $item = $resultData['item'];

                $bakewayFees = $this->getBakewayFeeDetails($marketplaceOrders, $item);
                $tcs = $bakewayFees['tcs_amount'];
                $pgfee = $bakewayFees['pg_fee'];
                $item = $bakewayFees['item'];
                $taxPaidByBakeway = $bakewayFees['tax_paid_by_bakeway'];

                $taxAmount = $item['total_tax'];
                $vendorTaxAmount = 0;
                $adminTaxAmount = 0;
                if ($taxToSeller && $taxPaidByBakeway == 0) {
                    $vendorTaxAmount = $taxAmount;
                } else {
                    $totalshipping = $this->payoutHelper->getDeliveryFeeExclTax($totalshipping);
                    $adminTaxAmount = $taxAmount;
                }

                if ($item['actual_seller_amount'] * 1) {
                    $taxShippingTotal = $vendorTaxAmount + $totalshipping - $appliedCouponAmount;
                    /**
                     * Deducting the TCS and PG fee
                     */
                    $taxShippingTotal = $taxShippingTotal - ($tcs + $pgfee);
                    $item['actual_seller_amount'] = $item['actual_seller_amount'] + $taxShippingTotal;
                } else {
                    if ($totalshipping * 1) {
                        $item['actual_seller_amount'] = $totalshipping - $appliedCouponAmount;
                    }
                }
                $item['actual_seller_amount_to_pay'] = 0.00;
                if (($item['paid_status'] == 0) && ($item['cpprostatus'] == 1)) {
                    $item['actual_seller_amount_to_pay'] = $item['actual_seller_amount'];
                }

                if ($item['total_commission'] * 1) {
                    $item['total_commission'] = $item['total_commission'] + $adminTaxAmount;
                }

                // Updated product name
                $productName = $item[$fieldName];
                $result = [];
                if ($options = unserialize($item['product_options'])) {
                    if (isset($options['options'])) {
                        $result = array_merge($result, $options['options']);
                    }
                    if (isset($options['additional_options'])) {
                        $result = array_merge($result, $options['additional_options']);
                    }
                    if (isset($options['attributes_info'])) {
                        $result = array_merge($result, $options['attributes_info']);
                    }
                }
                $productName = $this->getProductNameHtml($result, $productName);
                /*prepare product quantity status*/
                $isForItemPay = 0;
                if ($item['qty_ordered'] > 0) {
                    $productName = $productName.__('Ordered').
                    ': <strong>'.($item['qty_ordered'] * 1).'</strong><br />';
                }
                if ($item['qty_invoiced'] > 0) {
                    ++$isForItemPay;
                    $productName = $productName.
                    __('Invoiced').
                    ': <strong>'.
                    ($item['qty_invoiced'] * 1).
                    '</strong><br />';
                }
                if ($item['qty_shipped'] > 0) {
                    ++$isForItemPay;
                    $productName = $productName.__('Shipped').
                    ': <strong>'.($item['qty_shipped'] * 1).'</strong><br />';
                }
                if ($item['qty_canceled'] > 0) {
                    $isForItemPay = 4;
                    $productName = $productName.
                    __('Canceled').
                    ': <strong>'.
                    ($item['qty_canceled'] * 1).
                    '</strong><br />';
                }
                if ($item['qty_refunded'] > 0) {
                    $isForItemPay = 3;
                    $productName = $productName.
                    __('Refunded').
                    ': <strong>'.
                    ($item['qty_refunded'] * 1).
                    '</strong><br />';
                }

                $item[$fieldName] = $productName;
            }
        }

        return $dataSource;
    }

    public function getOrderItemTaxShipping($marketplaceOrders, $item, $taxToSeller, $totalshipping)
    {
        $marketplaceOrdersData = [];
        foreach ($marketplaceOrders as $tracking) {
            $marketplaceOrdersData = $tracking->getData();
            $taxToSeller = $tracking['tax_to_seller'];
        }
        if ($item['is_shipping'] == 1) {
            foreach ($marketplaceOrders as $tracking) {
                $shippingamount = $marketplaceOrdersData['shipping_charges'];
                $refundedShippingAmount = $marketplaceOrdersData['refunded_shipping_charges'];
                $totalshipping = $shippingamount - $refundedShippingAmount;
                if ($totalshipping * 1) {
                    $item['total_shipping'] = $totalshipping;
                }
            }
        }
        return [
            'taxToSeller' => $taxToSeller,
            'totalshipping' => $totalshipping,
            'item' => $item
        ];
    }

    /**
     * Get Order Product Name Html Data Method.
     *
     * @param array  $result
     * @param string $productName
     *
     * @return string
     */
    public function getProductNameHtml($result, $productName)
    {
        if ($_options = $result) {
            $proOptionData = '<dl class="item-options">';
            foreach ($_options as $_option) {
                $proOptionData .= '<dt>'.$_option['label'].'</dt>';
                if (!$this->getPrintStatus()) {
                    $_formatedOptionValue = $_option;
                    $class = '';
                    if (isset($_formatedOptionValue['full_view'])) {
                        $class = 'truncated';
                    }
                    $proOptionData .= '<dd class="'.$class.'">'.$_option['value'];
                    if (isset($_formatedOptionValue['full_view'])) {
                        $proOptionData .= '<div class="truncated_full_value">
                        <dl class="item-options"><dt>'.
                        $_option['label'].
                        '</dt><dd>'.
                        $_formatedOptionValue['full_view'].
                        '</dd></dl></div>';
                    }
                    $proOptionData .= '</dd>';
                } else {
                    $printValue = $_option['print_value'];
                    $proOptionData .= '<dd>'.
                    nl2br((isset($printValue) ? $printValue : $_option['value'])).
                    '</dd>';
                }
            }
            $proOptionData .= '</dl>';
            $productName = $productName.'<br/>'.$proOptionData;
        } else {
            $productName = $productName.'<br/>';
        }

        return $productName;
    }

    /**
     * Get the TCS and PG amount for order
     * @param $marketplaceOrders
     * @param $item
     * @return array
     */
    public function getBakewayFeeDetails($marketplaceOrders, $item)
    {
        $tcs_amount = 0;
        $pg_fee = 0;
        $taxPaidByBakeway = 0;
        foreach ($marketplaceOrders as $tracking) {
            if ($item['is_shipping'] == 1) {
                $tcs_amount = $tracking->getData('tcs_amount');
                $pg_fee = $tracking->getData('payment_gateway_fee');
                $item['tcs_amount'] = $tcs_amount;
                $item['payment_gateway_fee'] = $pg_fee;
                $taxPaidByBakeway = $tracking->getData('tax_paid_by_bakeway');
            }
        }
        return [
            'tcs_amount'=>$tcs_amount,
            'pg_fee'=>$pg_fee,
            'item' => $item,
            'tax_paid_by_bakeway' =>$taxPaidByBakeway
        ];
    }
}
