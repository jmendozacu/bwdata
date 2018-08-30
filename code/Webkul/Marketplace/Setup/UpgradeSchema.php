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

namespace Webkul\Marketplace\Setup;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

/**
 * @codeCoverageIgnore
 */
class UpgradeSchema implements UpgradeSchemaInterface {

    protected $eavSetupFactory;

    public function __construct(\Magento\Eav\Setup\EavSetupFactory $eavSetupFactory) {
        $this->eavSetupFactory = $eavSetupFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context) {
        $setup->startSetup();
        /**
         * Update tables 'marketplace_saleperpartner'
         */
        $setup->getConnection()->changeColumn(
                $setup->getTable('marketplace_saleperpartner'), 'commission_rate', 'commission_rate', [
            'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
            'length' => '12,4',
            'comment' => 'Commission Rate'
                ]
        );

        /**
         * Update tables 'marketplace_saleslist'
         */
        $setup->getConnection()->addColumn(
                $setup->getTable('marketplace_saleslist'), 'is_shipping', [
            'type' => \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
            'unsigned' => true,
            'nullable' => false,
            'default' => '0',
            'comment' => 'Is Shipping Applied'
                ]
        );
        $setup->getConnection()->addColumn(
                $setup->getTable('marketplace_saleslist'), 'is_coupon', [
            'type' => \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
            'unsigned' => true,
            'nullable' => false,
            'default' => '0',
            'comment' => 'Is Coupon Applied'
                ]
        );
        $setup->getConnection()->addColumn(
                $setup->getTable('marketplace_saleslist'), 'is_paid', [
            'type' => \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
            'unsigned' => true,
            'nullable' => false,
            'default' => '0',
            'comment' => 'Is seller paid for current row'
                ]
        );
        $setup->getConnection()->addColumn(
                $setup->getTable('marketplace_saleslist'), 'commission_rate', [
            'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
            'length' => '12,4',
            'nullable' => false,
            'default' => '0',
            'comment' => 'Commission Rate applied at the time of order placed'
                ]
        );
        $setup->getConnection()->addColumn(
                $setup->getTable('marketplace_saleslist'), 'applied_coupon_amount', [
            'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
            'length' => '12,4',
            'nullable' => false,
            'default' => '0',
            'comment' => 'Applied coupon amount at the time of order placed'
                ]
        );
        /**
         * Update tables 'marketplace_orders'
         */
        $setup->getConnection()->addColumn(
                $setup->getTable('marketplace_orders'), 'tax_to_seller', [
            'type' => \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
            'unsigned' => true,
            'nullable' => false,
            'default' => '0',
            'comment' => 'Tax to seller account flag'
                ]
        );
        $setup->getConnection()->addColumn(
                $setup->getTable('marketplace_orders'), 'total_tax', [
            'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
            'length' => '12,4',
            'nullable' => false,
            'default' => '0',
            'comment' => 'Total Tax'
                ]
        );
        $setup->getConnection()->addColumn(
                $setup->getTable('marketplace_orders'), 'coupon_amount', [
            'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
            'length' => '12,4',
            'nullable' => false,
            'default' => '0',
            'comment' => 'Coupon Amount'
                ]
        );
        $setup->getConnection()->addColumn(
                $setup->getTable('marketplace_orders'), 'refunded_coupon_amount', [
            'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
            'length' => '12,4',
            'nullable' => false,
            'default' => '0',
            'comment' => 'Refunded Coupon Amount'
                ]
        );
        $setup->getConnection()->addColumn(
                $setup->getTable('marketplace_orders'), 'refunded_shipping_charges', [
            'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
            'length' => '12,4',
            'nullable' => false,
            'default' => '0',
            'comment' => 'Refunded Shipping Amount'
                ]
        );
        /**
         * Add notification column for orders
         */
        $setup->getConnection()->addColumn(
                $setup->getTable('marketplace_orders'), 'seller_pending_notification', [
            'type' => \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
            'unsigned' => true,
            'nullable' => false,
            'default' => '0',
            'comment' => 'Order Notification flag for sellers'
                ]
        );

        $this->addForeignKeys($setup);
        $this->dropForeignKeys($setup);

        /**
         * Update tables 'marketplace_product' to add notification columns
         */
        $setup->getConnection()->addColumn(
                $setup->getTable('marketplace_product'), 'seller_pending_notification', [
            'type' => \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
            'unsigned' => true,
            'nullable' => false,
            'default' => '0',
            'comment' => 'Product Notification flag for sellers'
                ]
        );
        $setup->getConnection()->addColumn(
                $setup->getTable('marketplace_product'), 'admin_pending_notification', [
            'type' => \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
            'unsigned' => true,
            'nullable' => false,
            'default' => '0',
            'comment' => 'Product Notification flag for admin'
                ]
        );

        /**
         * Update table 'marketplace_sellertransaction' to add notification column
         */
        $setup->getConnection()->addColumn(
                $setup->getTable('marketplace_sellertransaction'), 'seller_pending_notification', [
            'type' => \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
            'unsigned' => true,
            'nullable' => false,
            'default' => '0',
            'comment' => 'Notification flag for sellers'
                ]
        );

        /**
         * Update table 'marketplace_userdata' to add notification column
         */
        $setup->getConnection()->addColumn(
                $setup->getTable('marketplace_userdata'), 'admin_notification', [
            'type' => \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
            'unsigned' => true,
            'nullable' => false,
            'default' => '0',
            'comment' => 'Notification flag for admin'
                ]
        );

        /**
         * Update table 'marketplace_datafeedback' to add notification column
         */
        $setup->getConnection()->addColumn(
                $setup->getTable('marketplace_datafeedback'), 'admin_notification', [
            'type' => \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
            'unsigned' => true,
            'nullable' => false,
            'default' => '0',
            'comment' => 'Notification flag for admin'
                ]
        );
        /*
         * Create table 'marketplace_controller_list'
         */
        $table = $setup->getConnection()
                ->newTable($setup->getTable('marketplace_controller_list'))
                ->addColumn(
                        'entity_id', \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER, null, ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true], 'Entity ID'
                )
                ->addColumn(
                        'module_name', \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, null, ['nullable' => true, 'default' => null], 'Webkul Module Name'
                )
                ->addColumn(
                        'controller_path', \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, null, ['nullable' => true, 'default' => null], 'Controller Path'
                )
                ->addColumn(
                        'label', \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, null, ['nullable' => true, 'default' => null], 'Controller Label'
                )
                ->addColumn(
                        'is_child', \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT, null, ['unsigned' => false, 'nullable' => false, 'default' => '0'], 'Is controller have any child Option'
                )
                ->addColumn(
                        'parent_id', \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER, null, ['unsigned' => true, 'nullable' => false, 'default' => '0'], 'Status'
                )
                ->addColumn(
                        'created_at', \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP, null, [], 'Creation Time'
                )
                ->addColumn(
                        'updated_at', \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP, null, [], 'Update Time'
                )
                ->setComment('Marketplace Controller List Table');
        $setup->getConnection()->createTable($table);

        /*
         * Create table 'marketplace_order_pendingemails'
         */
        $table = $setup->getConnection()
                ->newTable($setup->getTable('marketplace_order_pendingemails'))
                ->addColumn(
                        'entity_id', \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER, null, ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true], 'Entity ID'
                )
                ->addColumn(
                        'seller_id', \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER, null, ['unsigned' => true, 'nullable' => false], 'Seller ID'
                )
                ->addColumn(
                        'order_id', \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER, null, ['unsigned' => true, 'nullable' => false], 'Order ID'
                )
                ->addColumn(
                        'myvar1', \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, null, ['nullable' => true, 'default' => null], 'myvar1'
                )
                ->addColumn(
                        'myvar2', \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, null, ['nullable' => true, 'default' => null], 'myvar2'
                )
                ->addColumn(
                        'myvar3', \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, null, ['nullable' => true, 'default' => null], 'myvar3'
                )
                ->addColumn(
                        'myvar4', \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, null, ['nullable' => true, 'default' => null], 'myvar4'
                )
                ->addColumn(
                        'myvar5', \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, null, ['nullable' => true, 'default' => null], 'myvar5'
                )
                ->addColumn(
                        'myvar6', \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, null, ['nullable' => true, 'default' => null], 'myvar6'
                )
                ->addColumn(
                        'myvar8', \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, null, ['nullable' => true, 'default' => null], 'myvar8'
                )
                ->addColumn(
                        'myvar9', \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, null, ['nullable' => true, 'default' => null], 'myvar9'
                )
                ->addColumn(
                        'isNotVirtual', \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, null, ['nullable' => true, 'default' => null], 'isNotVirtual'
                )
                ->addColumn(
                        'sender_name', \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, null, ['nullable' => true, 'default' => null], 'sender_name'
                )
                ->addColumn(
                        'sender_email', \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, null, ['nullable' => true, 'default' => null], 'sender_email'
                )
                ->addColumn(
                        'receiver_name', \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, null, ['nullable' => true, 'default' => null], 'receiver_name'
                )
                ->addColumn(
                        'receiver_email', \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, null, ['nullable' => true, 'default' => null], 'receiver_email'
                )
                ->addColumn(
                        'status', \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER, null, ['nullable' => false, 'default' => 0], 'status'
                )
                ->addColumn(
                        'created_at', \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP, null, [], 'Creation Time'
                )
                ->addColumn(
                        'updated_at', \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP, null, [], 'Update Time'
                )
                ->setComment('Marketplace Order Pending Email Table');
        $setup->getConnection()->createTable($table);

        /**
         * Update tables 'sales_order'
         */
        $setup->getConnection()->addColumn(
                $setup->getTable('sales_order'), 'order_approval_status', [
            'type' => \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
            'unsigned' => true,
            'nullable' => false,
            'default' => '0',
            'comment' => 'order_approval_status'
                ]
        );

        /**
         * Update tables 'sales_order_grid'
         */
        $setup->getConnection()->addColumn(
                $setup->getTable('sales_order_grid'), 'order_approval_status', [
            'type' => \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
            'unsigned' => true,
            'nullable' => false,
            'default' => '0',
            'comment' => 'order_approval_status'
                ]
        );

        /**
         * Update tables 'marketplace_userdata'
         */
        $setup->getConnection()->addColumn(
                $setup->getTable('marketplace_userdata'), 'allowed_categories', [
            'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            'unsigned' => true,
            'nullable' => false,
            'default' => '',
            'comment' => 'Allowed Categories Ids'
                ]
        );

        /**
         * Update tables 'marketplace_userdata' to add custom fields for seller
         */
        if (version_compare($context->getVersion(), '2.1.0', '<')) {
            /**
             * Update tables 'marketplace_userdata'
             */
            $setup->getConnection()->addColumn(
                    $setup->getTable('marketplace_userdata'), 'store_owner_name', [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'unsigned' => true,
                'nullable' => false,
                'default' => '',
                'length' => 255,
                'comment' => 'Store Owner Name'
                    ]
            );
            $setup->getConnection()->addColumn(
                    $setup->getTable('marketplace_userdata'), 'store_owner_mobile_no', [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'unsigned' => true,
                'nullable' => false,
                'default' => '',
                'length' => 255,
                'comment' => 'Store Owner Mobile Number'
                    ]
            );
            $setup->getConnection()->addColumn(
                    $setup->getTable('marketplace_userdata'), 'store_owner_email', [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'unsigned' => true,
                'nullable' => false,
                'default' => '',
                'length' => 255,
                'comment' => 'Store Owner Email ID'
                    ]
            );
            $setup->getConnection()->addColumn(
                    $setup->getTable('marketplace_userdata'), 'store_manager_name', [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'unsigned' => true,
                'nullable' => false,
                'default' => '',
                'length' => 255,
                'comment' => 'Store Manager Name'
                    ]
            );
            $setup->getConnection()->addColumn(
                    $setup->getTable('marketplace_userdata'), 'store_manager_mobile_no', [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'unsigned' => true,
                'nullable' => false,
                'default' => '',
                'length' => 255,
                'comment' => 'Store Manager Mobile Number'
                    ]
            );
            $setup->getConnection()->addColumn(
                    $setup->getTable('marketplace_userdata'), 'store_manager_email', [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'unsigned' => true,
                'nullable' => false,
                'default' => '',
                'length' => 255,
                'comment' => 'Store Manager Email ID'
                    ]
            );
            $setup->getConnection()->addColumn(
                    $setup->getTable('marketplace_userdata'), 'bakery_type', [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                'unsigned' => true,
                'nullable' => false,
                'default' => '0',
                'comment' => 'Bakery Type'
                    ]
            );
            $setup->getConnection()->addColumn(
                    $setup->getTable('marketplace_userdata'), 'store_city', [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'unsigned' => true,
                'nullable' => false,
                'default' => '',
                'length' => 255,
                'comment' => 'Store City'
                    ]
            );
            $setup->getConnection()->addColumn(
                    $setup->getTable('marketplace_userdata'), 'store_locality_area', [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'unsigned' => true,
                'nullable' => false,
                'default' => '',
                'length' => 255,
                'comment' => 'Store Locality Area'
                    ]
            );
            $setup->getConnection()->addColumn(
                    $setup->getTable('marketplace_userdata'), 'store_street_address', [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'unsigned' => true,
                'nullable' => false,
                'default' => '',
                'length' => 255,
                'comment' => 'Store Street Address'
                    ]
            );
            $setup->getConnection()->addColumn(
                    $setup->getTable('marketplace_userdata'), 'store_latitude', [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'unsigned' => true,
                'nullable' => false,
                'default' => '',
                'length' => 255,
                'comment' => 'Store Latitude'
                    ]
            );
            $setup->getConnection()->addColumn(
                    $setup->getTable('marketplace_userdata'), 'store_longitude', [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'unsigned' => true,
                'nullable' => false,
                'default' => '',
                'length' => 255,
                'comment' => 'Store Longitude'
                    ]
            );
            $setup->getConnection()->addColumn(
                    $setup->getTable('marketplace_userdata'), 'delivery_time', [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                'unsigned' => true,
                'nullable' => false,
                'default' => '0',
                'length' => 11,
                'comment' => 'Estimated Delivery Time'
                    ]
            );
            $setup->getConnection()->addColumn(
                    $setup->getTable('marketplace_userdata'), 'advanced_order_intimation_time', [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                'unsigned' => true,
                'nullable' => false,
                'default' => '0',
                'length' => 11,
                'comment' => 'Advanced Order Intimation Time'
                    ]
            );
        }

        /**
         * Update tables 'marketplace_userdata' to add custom fields for seller
         * Adding fields for saving the seller bank details
         */
        if (version_compare($context->getVersion(), '2.1.1', '<')) {
            /**
             * Update tables 'marketplace_userdata'
             */
            $setup->getConnection()->addColumn(
                    $setup->getTable('marketplace_userdata'), 'store_owner_bank_ifsc', [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'unsigned' => true,
                'nullable' => false,
                'default' => '',
                'length' => 255,
                'comment' => 'Store Owner Bank IFSC Code'
                    ]
            );
            $setup->getConnection()->addColumn(
                    $setup->getTable('marketplace_userdata'), 'store_owner_bank_micr', [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'unsigned' => true,
                'nullable' => false,
                'default' => '',
                'length' => 255,
                'comment' => 'Store Owner Bank MICR'
                    ]
            );
            $setup->getConnection()->addColumn(
                    $setup->getTable('marketplace_userdata'), 'store_owner_bank_account_number', [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                'unsigned' => true,
                'nullable' => true,
                'default' => null,
                'length' => 11,
                'comment' => 'Store Owner Bank Account Number'
                    ]
            );
            $setup->getConnection()->addColumn(
                    $setup->getTable('marketplace_userdata'), 'store_owner_bank_account_type', [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'unsigned' => true,
                'nullable' => false,
                'default' => '',
                'length' => 255,
                'comment' => 'Store Owner Bank Account Type'
                    ]
            );
            $setup->getConnection()->addColumn(
                    $setup->getTable('marketplace_userdata'), 'store_owner_bank_registered_name', [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'unsigned' => true,
                'nullable' => false,
                'default' => '',
                'length' => 255,
                'comment' => 'Store Owner Bank Registered Name'
                    ]
            );
        }

        if (version_compare($context->getVersion(), '2.1.2', '<')) {
            /**
             * Update tables 'marketplace_userdata'
             */
            $setup->getConnection()->addColumn(
                    $setup->getTable('marketplace_userdata'), 'contact_email', [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'unsigned' => true,
                'nullable' => true,
                'default' => null,
                'length' => 255,
                'comment' => 'Communication Email'
                    ]
            );
            $setup->getConnection()->addColumn(
                    $setup->getTable('marketplace_userdata'), 'known_for', [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'unsigned' => true,
                'nullable' => true,
                'default' => null,
                'comment' => 'Store Known For'
                    ]
            );
            $setup->getConnection()->addColumn(
                    $setup->getTable('marketplace_userdata'), 'store_highlights', [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'unsigned' => true,
                'nullable' => true,
                'default' => null,
                'comment' => 'Store Highlights'
                    ]
            );
            $setup->getConnection()->addColumn(
                    $setup->getTable('marketplace_userdata'), 'merchant_name', [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'unsigned' => true,
                'nullable' => true,
                'default' => null,
                'length' => 255,
                'comment' => 'Merchant Name'
                    ]
            );
            $setup->getConnection()->addColumn(
                    $setup->getTable('marketplace_userdata'), 'business_name', [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'unsigned' => true,
                'nullable' => true,
                'default' => null,
                'length' => 255,
                'comment' => 'Business Name'
                    ]
            );
            $setup->getConnection()->addColumn(
                    $setup->getTable('marketplace_userdata'), 'shop_open_timing', [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'unsigned' => true,
                'nullable' => true,
                'default' => null,
                'length' => 255,
                'comment' => 'Shop Open Timing'
                    ]
            );
            $setup->getConnection()->addColumn(
                    $setup->getTable('marketplace_userdata'), 'shop_close_timing', [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'unsigned' => true,
                'nullable' => true,
                'default' => null,
                'length' => 255,
                'comment' => 'Shop Close Timing'
                    ]
            );
            $setup->getConnection()->addColumn(
                    $setup->getTable('marketplace_userdata'), 'fssai', [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'unsigned' => true,
                'nullable' => true,
                'default' => null,
                'length' => 255,
                'comment' => 'FSSAI'
                    ]
            );
        }

        if (version_compare($context->getVersion(), '2.1.3', '<')) {
            $setup->getConnection()->addColumn(
                    $setup->getTable('marketplace_userdata'), 'delivery', [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'unsigned' => true,
                'nullable' => true,
                'default' => null,
                'length' => 255,
                'comment' => 'Delivery'
                    ]
            );
        }

        if (version_compare($context->getVersion(), '2.1.4', '<')) {
            $setup->getConnection()->addColumn(
                    $setup->getTable('marketplace_userdata'), 'delivery_time_mins', [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'unsigned' => true,
                'nullable' => true,
                'default' => null,
                'length' => 255,
                'comment' => 'Delivery Time(Mins)'
                    ]
            );
            $setup->getConnection()->addColumn(
                    $setup->getTable('marketplace_userdata'), 'shop_open_AMPM', [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                'unsigned' => true,
                'nullable' => true,
                'default' => null,
                'length' => 11,
                'comment' => 'Delivery Time(AM/PM)'
                    ]
            );
            $setup->getConnection()->addColumn(
                    $setup->getTable('marketplace_userdata'), 'shop_close_AMPM', [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                'unsigned' => true,
                'nullable' => true,
                'default' => null,
                'length' => 11,
                'comment' => 'Delivery Time(AM/PM)'
                    ]
            );
        }

        if (version_compare($context->getVersion(), '2.1.5', '<')) {

            $setup->getConnection()->addColumn(
                    $setup->getTable('marketplace_userdata'), 'userdata_bank_name', [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'unsigned' => true,
                'nullable' => true,
                'default' => null,
                'length' => 255,
                'comment' => 'Name of the bank'
                    ]
            );
            $setup->getConnection()->addColumn(
                    $setup->getTable('marketplace_userdata'), 'userdata_gstin_number', [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'unsigned' => true,
                'nullable' => true,
                'default' => null,
                'length' => 255,
                'comment' => 'GSTIN Number of seller'
                    ]
            );
            $setup->getConnection()->addColumn(
                    $setup->getTable('marketplace_userdata'), 'userdata_tin_number', [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'unsigned' => true,
                'nullable' => true,
                'default' => null,
                'length' => 255,
                'comment' => 'TIN Number of seller'
                    ]
            );
            $setup->getConnection()->addColumn(
                    $setup->getTable('marketplace_userdata'), 'userdata_tan_number', [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'unsigned' => true,
                'nullable' => true,
                'default' => null,
                'length' => 255,
                'comment' => 'TAN Number of seller'
                    ]
            );
            $setup->getConnection()->addColumn(
                    $setup->getTable('marketplace_userdata'), 'userdata_pan_number', [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'unsigned' => true,
                'nullable' => true,
                'default' => null,
                'length' => 255,
                'comment' => 'PAN Number of seller'
                    ]
            );

            $setup->getConnection()->addColumn(
                    $setup->getTable('marketplace_userdata'), 'userdata_cgst', [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                'nullable' => true,
                'default' => 0,
                'comment' => 'CGST detail'
                    ]
            );
            $setup->getConnection()->addColumn(
                    $setup->getTable('marketplace_userdata'), 'userdata_sgst', [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                'nullable' => false,
                'default' => 0,
                'comment' => 'SGST detail'
                    ]
            );
            $setup->getConnection()->addColumn(
                    $setup->getTable('marketplace_userdata'), 'userdata_igst', [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                'nullable' => false,
                'default' => 0,
                'comment' => 'IGST detail'
                    ]
            );
            $setup->getConnection()->addColumn(
                    $setup->getTable('marketplace_userdata'), 'userdata_cancelled_cheque', [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'nullable' => true,
                'default' => null,
                'length' => 255,
                'comment' => 'Cancelled Cheque Photo upload'
                    ]
            );

            $setup->getConnection()->addColumn(
                    $setup->getTable('marketplace_userdata'), 'userdata_agreement_document', [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'nullable' => true,
                'default' => null,
                'length' => 255,
                'comment' => 'Agreement Document Photo Upload'
                    ]
            );
            $setup->getConnection()->addColumn(
                    $setup->getTable('marketplace_userdata'), 'userdata_brand', [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'unsigned' => true,
                'nullable' => true,
                'default' => null,
                'length' => 255,
                'comment' => 'Brand name'
                    ]
            );
        }

        if (version_compare($context->getVersion(), '2.1.6', '<')) {
            $setup->getConnection()->addColumn(
                    $setup->getTable('marketplace_userdata'), 'store_zipcode', [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                'nullable' => true,
                'default' => null,
                'comment' => 'Store Zipcode',
                'after' => 'store_street_address'
                    ]
            );
        }
        if (version_compare($context->getVersion(), '2.1.7', '<')) {
            $setup->getConnection()->addColumn(
                    $setup->getTable('marketplace_userdata'), 'is_pickup', [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                'unsigned' => true,
                'nullable' => false,
                'comment' => 'Is Pick Up Delivery Option'
                    ]
            );
        }
        if (version_compare($context->getVersion(), '2.1.8', '<')) {
            $setup->getConnection()->addColumn(
                    $setup->getTable('marketplace_userdata'), 'userdata_operational_days', [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'unsigned' => true,
                'nullable' => false,
                'comment' => 'Capture Operational Days of week'
                    ]
            );
        }

        if (version_compare($context->getVersion(), '2.1.9', '<')) {
            $setup->getConnection()->addColumn(
                    $setup->getTable('marketplace_userdata'), 'bakeway_poc_id', [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                'unsigned' => true,
                'nullable' => true,
                'default' => null,
                'comment' => 'Bakeway Point Of Contact'
                    ]
            );
        }


        if (version_compare($context->getVersion(), '2.1.10', '<')) {
            $setup->getConnection()->addColumn(
                    $setup->getTable('marketplace_userdata'), 'userdata_shop_operatational_status', [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                'unsigned' => true,
                'nullable' => false,
                'comment' => 'Shop Opertational status'
                    ]
            );

            $setup->getConnection()->addColumn(
                    $setup->getTable('marketplace_userdata'), 'userdata_shop_temporarily_u_from', [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DATETIME,
                'unsigned' => true,
                'nullable' => false,
                'comment' => 'Shop Opertational status  Temporarily Unavailable From Date'
                    ]
            );

            $setup->getConnection()->addColumn(
                    $setup->getTable('marketplace_userdata'), 'userdata_shop_temporarily_u_to', [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DATETIME,
                'unsigned' => true,
                'nullable' => false,
                'comment' => 'Shop Opertational status  Temporarily Unavailable To Date/'
                    ]
            );
        }

        if (version_compare($context->getVersion(), '2.1.12', '<')) {
            $eavSetup = $this->eavSetupFactory->create();
            $eavSetup->removeAttribute('catalog_product', 'color');

            $_UserdataTable = $setup->getTable('marketplace_userdata');
            $sql = "ALTER TABLE `" . $_UserdataTable . "`  CHANGE `delivery` `delivery` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '0' COMMENT 'Delivery'";
            $setup->run($sql);
        }

        if (version_compare($context->getVersion(), '2.1.13', '<')) {
            $_UserdataTable = $setup->getTable('marketplace_userdata');
            $sql = "ALTER TABLE `" . $_UserdataTable . "`  CHANGE `store_owner_bank_account_number` `store_owner_bank_account_number` DOUBLE(16,0) NULL DEFAULT NULL COMMENT 'Store Owner Bank Account Number'";
            $setup->run($sql);
        }
       
        if (version_compare($context->getVersion(), '2.1.14', '<')) {
           $_UserdataTable = $setup->getTable('marketplace_userdata');
           $_sql =  "ALTER TABLE `" . $_UserdataTable . "` CHANGE `userdata_shop_temporarily_u_from` `userdata_shop_temporarily_u_from` DATETIME NULL DEFAULT NULL COMMENT 'Shop Opertational status Temporarily Unavailable From Date'";
           $_sql_second =  "ALTER TABLE `" . $_UserdataTable . "` CHANGE `userdata_shop_temporarily_u_to` `userdata_shop_temporarily_u_to` DATETIME NULL DEFAULT NULL COMMENT '	Shop Opertational status Temporarily Unavailable To Date'";
           $setup->run($_sql);
           $setup->run($_sql_second);
        }

        if (version_compare($context->getVersion(), '2.1.15', '<')) {
            $setup->getConnection()->addColumn(
                $setup->getTable('marketplace_userdata'), 'is_live_ready', [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                    'default' => 0,
                    'unsigned' => true,
                    'nullable' => false,
                    'comment' => 'Is Bakery Live Ready'
                ]
            );
        }

        if (version_compare($context->getVersion(), '2.1.16', '<')) {
            $tableName = $setup->getTable('marketplace_userdata');
            $sql = "ALTER TABLE ".$tableName." CHANGE  `country_pic`  `country_pic` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT  'IN' COMMENT  'Country Flag Image'";
            $setup->run($sql);
        }

        if (version_compare($context->getVersion(), '2.1.17', '<')) {
            $tableName = $setup->getTable('marketplace_userdata');
            $sql = "ALTER TABLE ".$tableName." CHANGE `store_owner_bank_account_number` `store_owner_bank_account_number` VARCHAR(255) NULL DEFAULT NULL COMMENT 'Store Owner Bank Account Number'";
            $setup->run($sql);
        }

        if (version_compare($context->getVersion(), '2.1.18', '<')) {
            $setup->getConnection()->addColumn(
                $setup->getTable('marketplace_userdata'), 'is_addon_available', [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                    'default' => 0,
                    'unsigned' => true,
                    'nullable' => false,
                    'comment' => 'Is Addon Available'
                ]
            );
        }

        if (version_compare($context->getVersion(), '2.1.19', '<')) {
            $setup->getConnection()->addColumn(
                $setup->getTable('marketplace_userdata'), 'order_alarm', [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                    'default' => 1,
                    'unsigned' => true,
                    'nullable' => false,
                    'comment' => 'Alarm Status'
                ]
            );
        }

        /* Adding extra column for avegare rating */
        if (version_compare($context->getVersion(), '2.1.20', '<')) {
            $setup->getConnection()->addColumn(
                $setup->getTable('marketplace_feedbackcount'), 'avg_rating', [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_FLOAT,
                    'default' => 0.0,
                    'unsigned' => true,
                    'nullable' => false,
                    'comment' => 'Average Rating'
                ]
            );
        }

        if (version_compare($context->getVersion(), '2.1.21', '<')) {
            $setup->getConnection()->addColumn(
                $setup->getTable('marketplace_feedbackcount'), 'user_count', [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    'default' => 100,
                    'unsigned' => true,
                    'nullable' => false,
                    'comment' => 'User Count'
                ]
            );
        }

        if (version_compare($context->getVersion(), '2.1.21', '<')) {
            $tableName = $setup->getTable('marketplace_userdata');
            $sql = "ALTER TABLE ".$tableName." CHANGE `average_ratings` `average_ratings` FLOAT NULL DEFAULT '0.00' COMMENT 'Store Average Ratings'";
            $setup->run($sql);
        }

        if (version_compare($context->getVersion(), '2.1.22', '<')) {
            $setup->getConnection()->addColumn(
                $setup->getTable('marketplace_userdata'), 'shop_delivery_open_time', [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    'unsigned' => true,
                    'nullable' => false,
                    'default' => '0',
                    'comment' => 'Shop Delivery Open Time'
                ]
            );

            $setup->getConnection()->addColumn(
                $setup->getTable('marketplace_userdata'), 'shop_delivery_close_time', [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    'unsigned' => true,
                    'nullable' => false,
                    'default' => '0',
                    'comment' => 'Shop Delivery Close Time'
                ]
            );
        }

        if (version_compare($context->getVersion(), '2.1.24', '<')) {
            $tableName = $setup->getTable('marketplace_userdata');
            $sql = "ALTER TABLE ".$tableName." CHANGE `shop_delivery_open_time` `shop_delivery_open_time` INT(10) UNSIGNED NULL DEFAULT '0' COMMENT 'Shop Delivery Open Time', CHANGE `shop_delivery_close_time` `shop_delivery_close_time` INT(10) UNSIGNED NULL DEFAULT '0' COMMENT 'Shop Delivery Close Time'";
            $setup->run($sql);
        }
        
        if (version_compare($context->getVersion(), '2.1.26', '<')) {
            $tableName = $setup->getTable('marketplace_datafeedback');
            $sql = "ALTER TABLE ".$tableName." CHANGE COLUMN `created_at` `created_at` DATETIME NULL DEFAULT NULL COMMENT 'Creation Time' AFTER `feed_review`, "
                    . "CHANGE COLUMN `updated_at` `updated_at` DATETIME NULL DEFAULT NULL COMMENT 'Update Time' AFTER `created_at`;";
            $setup->run($sql);
        }
        
        $setup->endSetup();
    }

    /**
     *
     * @param SchemaSetupInterface $setup
     * @return void
     */
    protected function addForeignKeys(SchemaSetupInterface $setup) {
        /**
         * Add foreign keys for Product ID
         */
        $setup->getConnection()->addForeignKey(
                $setup->getFkName(
                        'marketplace_product', 'mageproduct_id', 'catalog_product_entity', 'entity_id'
                ), $setup->getTable('marketplace_product'), 'mageproduct_id', $setup->getTable('catalog_product_entity'), 'entity_id', \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
        );

        /**
         * Add foreign keys for Seller ID
         */
        $setup->getConnection()->addForeignKey(
                $setup->getFkName(
                        'marketplace_userdata', 'seller_id', 'customer_entity', 'entity_id'
                ), $setup->getTable('marketplace_userdata'), 'seller_id', $setup->getTable('customer_entity'), 'entity_id', \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
        );

        $setup->getConnection()->addForeignKey(
                $setup->getFkName(
                        'marketplace_sellertransaction', 'seller_id', $setup->getTable('customer_entity'), 'entity_id'
                ), $setup->getTable('marketplace_sellertransaction'), 'seller_id', $setup->getTable('customer_entity'), 'entity_id', \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
        );

        $setup->getConnection()->addForeignKey(
                $setup->getFkName(
                        'marketplace_datafeedback', 'seller_id', $setup->getTable('customer_entity'), 'entity_id'
                ), $setup->getTable('marketplace_datafeedback'), 'seller_id', $setup->getTable('customer_entity'), 'entity_id', \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
        );

        $setup->getConnection()->addForeignKey(
                $setup->getFkName(
                        'marketplace_feedbackcount', 'seller_id', $setup->getTable('customer_entity'), 'entity_id'
                ), $setup->getTable('marketplace_feedbackcount'), 'seller_id', $setup->getTable('customer_entity'), 'entity_id', \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
        );

        /**
         * Add foreign keys for Order ID
         */
        $setup->getConnection()->addForeignKey(
                $setup->getFkName(
                        'marketplace_orders', 'order_id', 'sales_order', 'entity_id'
                ), $setup->getTable('marketplace_orders'), 'order_id', $setup->getTable('sales_order'), 'entity_id', \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
        );

        $setup->getConnection()->addForeignKey(
                $setup->getFkName(
                        'marketplace_saleslist', 'order_id', 'sales_order', 'entity_id'
                ), $setup->getTable('marketplace_saleslist'), 'order_id', $setup->getTable('sales_order'), 'entity_id', \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
        );
    }

    /**
     *
     * @param SchemaSetupInterface $setup
     * @return void
     */
    protected function dropForeignKeys(SchemaSetupInterface $setup) {
        /**
         * drop foreign keys for Seller ID
         */
        $setup->getConnection()->dropForeignKey(
                $setup->getTable('marketplace_orders'), $setup->getFkName(
                        'marketplace_orders', 'seller_id', 'customer_entity', 'entity_id'
                )
        );

        /**
         * drop foreign keys for Seller ID
         */
        $setup->getConnection()->dropForeignKey(
                $setup->getTable('marketplace_saleperpartner'), $setup->getFkName(
                        'marketplace_saleperpartner', 'seller_id', 'customer_entity', 'entity_id'
                )
        );
    }

}
