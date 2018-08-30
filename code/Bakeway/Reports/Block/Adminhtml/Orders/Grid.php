<?php

namespace Bakeway\Reports\Block\Adminhtml\Orders;

use Bakeway\Reports\Helper\Data as Reportshelper;

class Grid extends \Magento\Backend\Block\Widget\Grid\Extended
{

    /**
     * @var \Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\CollectionFactory]
     */
    protected $_setsFactory;

    /**
     * @var \Magento\Catalog\Model\ProductFactory
     */
    protected $_productFactory;

    /**
     * @var \Magento\Catalog\Model\Product\Type
     */
    protected $_type;

    /**
     * @var \Magento\Catalog\Model\Product\Attribute\Source\Status
     */
    protected $_status;

    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\CollectionFactory
     */
    protected $_collectionFactory;

    /**
     * @var \Magento\Catalog\Model\Product\Visibility
     */
    protected $_visibility;

    /**
     * @var \Magento\Store\Model\WebsiteFactory
     */
    protected $_websiteFactory;

    /**
     * @var \magento\Sales\Model\OrderFactory
     */
    protected $orderFactory;

    /**
     * @var Reportshelper
     */
    protected $reportshelper;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * Grid constructor.
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Backend\Helper\Data $backendHelper
     * @param \Magento\Store\Model\WebsiteFactory $websiteFactory
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     * @param \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $collectionFactory
     * @param Reportshelper $reportshelper
     * @param \Magento\Framework\Registry $registry
     * @param array $data
     */
    public function __construct(
    \Magento\Backend\Block\Template\Context $context,
            \Magento\Backend\Helper\Data $backendHelper,
            \Magento\Store\Model\WebsiteFactory $websiteFactory,
            \Magento\Sales\Model\OrderFactory $orderFactory,
            \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $collectionFactory,
            Reportshelper $reportshelper,
            \Magento\Framework\Registry $registry,
            array $data = []
    )
    {

        $this->_collectionFactory = $collectionFactory;
        $this->orderFactory = $orderFactory;
        $this->_websiteFactory = $websiteFactory;
        $this->reportshelper = $reportshelper;
        $this->registry = $registry;
        // $this->_removeButton('add');
        parent::__construct($context, $backendHelper, $data);
    }

    /**
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('productGrid');
        $this->setDefaultSort('id');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(false);
    }

    /**
     * @return Store
     */
    protected function _getStore()
    {
        $storeId = (int) $this->getRequest()->getParam('store', 0);
        return $this->_storeManager->getStore($storeId);
    }

    /**
     * @return $this
     */
    protected function _prepareCollection()
    {
        try {
            $collection = $this->_collectionFactory->create();
            $collection->addFieldToSelect(array('increment_id', 'delivery_type', 'created_at', 'updated_at', 'delivery_time', 'customer_notes', 'base_grand_total', 'base_shipping_amount', 'coupon_rule_name', 'fee'));
            $collection->getSelect()->joinLeft(array('mo' => 'marketplace_orders'),
                    'main_table.entity_id = mo.order_id',
                    array('mo.seller_id', 'mo.payment_gateway_fee', 'mo.coupon_amount'));

            $collection->getSelect()->joinLeft(array('mu' => 'marketplace_userdata'),
                    'mo.seller_id = mu.seller_id',
                    array('mu.business_name', 'mu.store_owner_mobile_no', 'mu.store_manager_mobile_no', 'mu.store_street_address', 'mu.userdata_gstin_number'));
            $collection->getSelect()->joinLeft(array('sog' => 'sales_order_grid'),
                    'main_table.entity_id = sog.entity_id',
                    array('sog.billing_name', 'sog.customer_email', 'sog.billing_address', 'sog.shipping_name', 'sog.shipping_address', 'sog.payment_method'));
            $collection->getSelect()->joinLeft(array('sosh' => 'sales_order_status_history'),
                    'main_table.entity_id = sosh.parent_id',
                    array('sosh.comment'));
            $collection->getSelect()->joinLeft(array('bpl' => 'bakeway_partner_locations'),
                    'bpl.seller_id = mo.seller_id', array());
            $collection->getSelect()->joinLeft(array('bc' => 'bakeway_cities'),
                    'bc.id = bpl.city_id', array('bc.name'));
            $collection->getSelect()->joinLeft(array('sos' => 'sales_order_status'),
                    'main_table.status = sos.status', array('sos.label'));
            $collection->getSelect()->joinInner(array('mksl' => 'marketplace_saleslist'),
                    'main_table.increment_id = mksl.magerealorder_id',
                    array('mksl.actual_seller_amount', 'mksl.paid_status'));
            $collection->getSelect()->columns([
                "commission_incl_tax" => new \Zend_Db_Expr("(SELECT SUM(commission_incl_tax) FROM marketplace_saleslist WHERE order_id = main_table.entity_id GROUP BY order_id)")
            ]);
            $collection->getSelect()->where('main_table.status NOT IN ("bakeway_payment_pending", "canceled")');
            $collection->getSelect()->group("main_table.entity_id");

            $collection->addFilterToMap('created_at', 'main_table.created_at');
            $collection->addFilterToMap('customer_email', 'sog.customer_email');
            $collection->addFilterToMap('seller_id', 'mo.seller_id');
            $collection->addFilterToMap('increment_id',
                    'main_table.increment_id');
            $collection->addFilterToMap('commission_incl_tax',
                    'commission_incl_tax');
            $collection->addFilterToMap('userdata_gstin_number',
                    'mu.userdata_gstin_number');
            $collection->addFilterToMap('payment_gateway_fee',
                    'mo.payment_gateway_fee');
            $collection->addFilterToMap('coupon_amount', 'mo.coupon_amount');
            $collection->addFilterToMap('store_owner_mobile_no',
                    'mu.store_owner_mobile_no');
            $collection->addFilterToMap('store_manager_mobile_no',
                    'mu.store_manager_mobile_no');
            $collection->addFilterToMap('comment', 'sosh.comment');
            $collection->addFilterToMap('business_name', 'mu.business_name');
            $collection->addFilterToMap('payment_method', 'sog.payment_method');
            $collection->addFilterToMap('billing_name', 'sog.billing_name');
            $collection->addFilterToMap('billing_address', 'sog.billing_address');
            $collection->addFilterToMap('delivery_time',
                    'main_table.delivery_time');
            $collection->addFilterToMap('shipping_name', 'sog.shipping_name');
            $collection->addFilterToMap('shipping_address',
                    'sog.shipping_address');
            $collection->addFilterToMap('base_grand_total',
                    'main_table.base_grand_total');
            $collection->addFilterToMap('label', 'sos.label');
            $collection->addFilterToMap('name', 'bc.name');            
            $collection->addFieldToFilter('created_at', ['gteq' => date('Y-m-d H:i:s', strtotime('-30 days'))]);
            $collection->addFieldToFilter('created_at', ['lteq' => date('Y-m-d H:i:s')]);
            $collection->setOrder('main_table.entity_id', 'DESC');
            $this->setCollection($collection);
            parent::_prepareCollection();

            return $this;
        } catch (Exception $e) {
            echo $e->getMessage();
            die;
        }
    }

    public function setCustomVariable()
    {
        $this->registry->register('custom_var', 'Added Value');
    }

    /**
     * @param \Magento\Backend\Block\Widget\Grid\Column $column
     * @return $this
     */
    protected function _addColumnFilterToCollection($column)
    {
        if ($this->getCollection()) {
            if ($column->getId() == 'websites') {
                $this->getCollection()->joinField(
                        'websites', 'catalog_product_website', 'website_id',
                        'product_id=entity_id', null, 'left'
                );
            }
        }
        return parent::_addColumnFilterToCollection($column);
    }

    /**
     * @return $this
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function _prepareColumns()
    {

        $this->addColumn(
                'increment_id',
                [
            'header' => __('Order ID'),
            'type' => 'text',
            'index' => 'increment_id',
            'header_css_class' => 'col-id',
            'column_css_class' => 'col-id'
                ]
        );

        $this->addColumn(
                'delivery_type',
                [
            'header' => __('Order Type'),
            'type' => 'text',
            'index' => 'delivery_type',
            'header_css_class' => 'col-id',
            'column_css_class' => 'col-id'
                ]
        );

        $this->addColumn(
                'created_at',
                [
            'header' => __('Order Date & Time'),
            'index' => 'created_at',
            'class' => 'state',
            'type' => 'datetime'
                ]
        );



        $this->addColumn(
                'seller_id',
                [
            'header' => __('Seller Id'),
            'type' => 'text',
            'index' => 'seller_id',
            'header_css_class' => 'col-id',
            'column_css_class' => 'col-id'
                ]
        );

        $this->addColumn(
                'business_name',
                [
            'header' => __('Bakery Name'),
            'type' => 'text',
            'index' => 'business_name',
            'header_css_class' => 'col-id',
            'column_css_class' => 'col-id'
                ]
        );

        $this->addColumn(
                'accpetrejectinterval',
                [
            'header' => __('Time to accept/ Reject'),
            'type' => 'text',
            'index' => 'entity_id',
            'header_css_class' => 'col-id',
            'column_css_class' => 'col-id',
            'width' => '50%',
            'renderer' => 'Bakeway\Reports\Block\Adminhtml\Orders\Renderer\OrderAccRejectTime'
                ]
        );

        $this->addColumn(
                'complete_order_time',
                [
            'header' => __('Delay to Mark as Completed'),
            'type' => 'text',
            'index' => 'entity_id',
            'header_css_class' => 'col-id',
            'column_css_class' => 'col-id',
            'width' => '50%',
            'renderer' => 'Bakeway\Reports\Block\Adminhtml\Orders\Renderer\OrderCompleted'
                ]
        );


        $this->addColumn(
                'comment',
                [
            'header' => __('Reject Reason'),
            'type' => 'text',
            'index' => 'comment',
            'header_css_class' => 'col-id',
            'column_css_class' => 'col-id',
            'renderer' => 'Bakeway\Reports\Block\Adminhtml\Orders\Renderer\RejectorderStatus'
                ]
        );

        $this->addColumn(
                'store_owner_mobile_no',
                [
            'header' => __('Store Owner Mobile No.'),
            'type' => 'text',
            'index' => 'store_owner_mobile_no',
            'header_css_class' => 'col-id',
            'column_css_class' => 'col-id'
                ]
        );

        $this->addColumn(
                'store_manager_mobile_no',
                [
            'header' => __('Store Manager MObile No.'),
            'type' => 'text',
            'index' => 'store_manager_mobile_no',
            'header_css_class' => 'col-id',
            'column_css_class' => 'col-id'
                ]
        );


        $this->addColumn(
                'customer_notes',
                [
            'header' => __('Note to bakery'),
            'type' => 'text',
            'index' => 'customer_notes',
            'header_css_class' => 'col-id',
            'column_css_class' => 'col-id'
                ]
        );

        $this->addColumn(
                'billing_name',
                [
            'header' => __('Sender Name'),
            'type' => 'text',
            'index' => 'billing_name',
            'header_css_class' => 'col-id',
            'column_css_class' => 'col-id'
                ]
        );

        $this->addColumn(
                'customer_email',
                [
            'header' => __('Sender Email'),
            'type' => 'text',
            'index' => 'customer_email',
            'header_css_class' => 'col-id',
            'column_css_class' => 'col-id'
                ]
        );


        $this->addColumn(
                'billing_address',
                [
            'header' => __('Sender Address'),
            'type' => 'text',
            'index' => 'billing_address',
            'header_css_class' => 'col-id',
            'column_css_class' => 'col-id'
                ]
        );

        $this->addColumn(
                'delivery_time',
                [
            'header' => __('Delivery Date & Time'),
            'type' => 'datetime',
            'index' => 'delivery_time',
            'header_css_class' => 'col-id',
            'column_css_class' => 'col-id',
            'renderer' => 'Bakeway\Reports\Block\Adminhtml\Orders\Renderer\Orderdeliverytime'
                ]
        );

        $this->addColumn(
                'shipping_name',
                [
            'header' => __('Delivery Person Name'),
            'type' => 'text',
            'index' => 'shipping_name',
            'header_css_class' => 'col-id',
            'column_css_class' => 'col-id'
                ]
        );

        $this->addColumn(
                'shipping_address',
                [
            'header' => __('Delivery Address'),
            'type' => 'text',
            'index' => 'shipping_address',
            'header_css_class' => 'col-id',
            'column_css_class' => 'col-id'
                ]
        );




        $this->addColumn(
                'base_grand_total',
                [
            'header' => __('Order Value'),
            'type' => 'number',
            'index' => 'base_grand_total',
            'header_css_class' => 'col-id',
            'column_css_class' => 'col-id'
                ]
        );

        $this->addColumn(
                'base_shipping_amount',
                [
            'header' => __('Shipping & Handling Charges'),
            'type' => 'number',
            'index' => 'base_shipping_amount',
            'header_css_class' => 'col-id',
            'column_css_class' => 'col-id'
                ]
        );

        $this->addColumn(
                'payment_method',
                [
            'header' => __('Payment Mode'),
            'type' => 'text',
            'index' => 'payment_method',
            'header_css_class' => 'col-id',
            'column_css_class' => 'col-id'
                ]
        );


        $this->addColumn(
                'coupon_rule_name',
                [
            'header' => __('Offer Applied Coupon'),
            'type' => 'text',
            'index' => 'coupon_rule_name',
            'header_css_class' => 'col-id',
            'column_css_class' => 'col-id'
                ]
        );

        $this->addColumn(
                'commission_incl_tax',
                [
            'header' => __('Commission Amount '),
            'type' => 'text',
            'filter' => false,
            'index' => 'commission_incl_tax',
            'header_css_class' => 'col-id',
            'column_css_class' => 'col-id',
            'filter' => false,
                ]
        );

        $this->addColumn(
                'fee',
                [
            'header' => __('Convenience Fee'),
            'type' => 'text',
            'index' => 'fee',
            'header_css_class' => 'col-id',
            'column_css_class' => 'col-id'
                ]
        );

        $this->addColumn(
                'payment_gateway_fee',
                [
            'header' => __('Payment gateway charges'),
            'type' => 'text',
            'index' => 'payment_gateway_fee',
            'header_css_class' => 'col-id',
            'column_css_class' => 'col-id'
                ]
        );

        $this->addColumn(
                'coupon_amount',
                [
            'header' => __('Discount amount'),
            'type' => 'text',
            'index' => 'coupon_amount',
            'header_css_class' => 'col-id',
            'column_css_class' => 'col-id'
                ]
        );


        $this->addColumn(
                'gst_amount',
                [
            'header' => __('GST'),
            'type' => 'text',
            'index' => 'gst_amount',
            'header_css_class' => 'col-id',
            'column_css_class' => 'col-id',
            'width' => '50%',
            'renderer' => 'Bakeway\Reports\Block\Adminhtml\Orders\Renderer\GST',
            'filter' => false,
                ]
        );

        $this->addColumn(
                'name',
                [
            'header' => __('City name'),
            'type' => 'text',
            'index' => 'name',
            'header_css_class' => 'col-id',
            'column_css_class' => 'col-id'
                ]
        );

        $this->addColumn(
                'label',
                [
            'header' => __('Order Status'),
            'type' => 'text',
            'index' => 'label',
            'header_css_class' => 'col-id',
            'column_css_class' => 'col-id'
                ]
        );

        $this->addColumn(
                'actual_seller_amount',
                [
            'header' => __('Total Seller Amount'),
            'type' => 'text',
            'index' => 'actual_seller_amount',
            'header_css_class' => 'col-id',
            'column_css_class' => 'col-id',
            'renderer' => 'Bakeway\Reports\Block\Adminhtml\Orders\Renderer\TotalSellerAmount',
            'filter' => false,
                ]
        );
        
        $this->addColumn(
                'paid_status',
                [
            'header' => __('Paid Status'),
            'type' => 'text',
            'index' => 'paid_status',
            'header_css_class' => 'col-id',
            'column_css_class' => 'col-id',
            'renderer' => 'Bakeway\Reports\Block\Adminhtml\Orders\Renderer\PaidStatus',
            'filter' => false,
                ]
        );
        
        $this->addColumn(
                'updated_at',
                [
            'header' => __('Completed At'),
            'type' => 'text',
            'index' => 'updated_at',
            'header_css_class' => 'col-id',
            'column_css_class' => 'col-id',
            'renderer' => 'Bakeway\Reports\Block\Adminhtml\Orders\Renderer\Status',
            'filter' => false,
                ]
        );
        
        $block = $this->getLayout()->getBlock('grid.bottom.links');
        if ($block) {
            $this->setChild('grid.bottom.links', $block);
        }


        $this->addExportType('*/*/exportCsv', __('CSV'));

        return parent::_prepareColumns();
    }

    /**
     * @return string
     */
    public function getGridUrl()
    {
        return $this->getUrl('customreports/customer/orders',
                        ['_current' => true]);
    }

    /**
     * @param \Magento\Catalog\Model\Product|\Magento\Framework\Object $row
     * @return string
     */
    public function getRowUrl($row)
    {
        return $this->getUrl(
                        'customreports/*/ordersview',
                        ['store' => $this->getRequest()->getParam('store'), 'id' => $row->getId()]
        );
    }

}
