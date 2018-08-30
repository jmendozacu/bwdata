<?php

/**
 * Bakeway
 *
 * @category  Bakeway
 * @package   Bakeway_CustomAttributes
 * @author    Bakeway
 */
namespace Bakeway\CustomAttributes\Setup;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

/**
 * @codeCoverageIgnore
 */
class UpgradeSchema
        implements UpgradeSchemaInterface
{

    /**
     * {@inheritdoc}
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function upgrade(SchemaSetupInterface $setup,
            ModuleContextInterface $context)
    {
        /**
         * Update tables 'quote','sales_order','sales_order_item' to add custom fields for logging
         */
        $quote = 'quote';
        $quoteItem = 'quote_item';
        $orderTable = 'sales_order';
        $orderItemTable = 'sales_order_item';
        $invoiceTable = 'sales_invoice';
        $invoiceItemTable = 'sales_invoice_item';
        
        if (version_compare($context->getVersion(), '1.0.1', '<')) {
            
            /**
             * quote table attributes 'delivery_type', 'delivery_time'
             */
            $setup->getConnection()->addColumn(
                    $setup->getTable($quote), 'delivery_type',
                    [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'length' => 255,
                'comment' => 'Delivery Type Pickup/Delivery'
                    ]
            );
            $setup->getConnection()->addColumn(
                    $setup->getTable($quote), 'delivery_time',
                    [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DATETIME,
                'comment' => 'Delivery Time'
                    ]
            );

            /**
             * sales_order table attributes 'delivery_type', 'delivery_time'
             */
            $setup->getConnection()->addColumn(
                    $setup->getTable($orderTable), 'delivery_type',
                    [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'length' => 255,
                'comment' => 'Delivery Type Pickup/Delivery'
                    ]
            );
            $setup->getConnection()->addColumn(
                    $setup->getTable($orderTable), 'delivery_time',
                    [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DATETIME,
                'comment' => 'Delivery Time'
                    ]
            );

            /**
             * sales_invoice table attributes 'delivery_type', 'delivery_time'
             */
            $setup->getConnection()->addColumn(
                    $setup->getTable($invoiceTable), 'delivery_type',
                    [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'length' => 255,
                'comment' => 'Delivery Type Pickup/Delivery'
                    ]
            );
            $setup->getConnection()->addColumn(
                    $setup->getTable($invoiceTable), 'delivery_time',
                    [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DATETIME,
                'comment' => 'Delivery Time'
                    ]
            );

            /**
             * quote_item table attribute 'custom_message'
             */
            $setup->getConnection()->addColumn(
                    $setup->getTable($quoteItem), 'custom_message',
                    [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'comment' => 'Item Custom Message'
                    ]
            );

            /**
             * sales_order_item table attribute 'custom_message'
             */
            $setup->getConnection()->addColumn(
                    $setup->getTable($orderItemTable), 'custom_message',
                    [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'comment' => 'Item Custom Message'
                    ]
            );

            /**
             * sales_invoice_item table attribute 'custom_message'
             */
            $setup->getConnection()->addColumn(
                    $setup->getTable($invoiceItemTable), 'custom_message',
                    [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'comment' => 'Item Custom Message'
                    ]
            );
        }

        if (version_compare($context->getVersion(), '1.0.3', '<')) {
            $quote = 'quote';
            $orderTable = 'sales_order';
            $invoiceTable = 'sales_invoice';

            /**
             * quote table attributes 'customer_notes'
             */
            $setup->getConnection()->addColumn(
                    $setup->getTable($quote), 'customer_notes',
                    [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'comment' => 'Bakeway Customer Notes'
                    ]
            );

            /**
             * sales_order table attributes 'customer_notes'
             */
            $setup->getConnection()->addColumn(
                    $setup->getTable($orderTable), 'customer_notes',
                    [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'comment' => 'Bakeway Customer Notes'
                    ]
            );

            /**
             * sales_invoice table attributes 'customer_notes'
             */
            $setup->getConnection()->addColumn(
                    $setup->getTable($invoiceTable), 'customer_notes',
                    [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'comment' => 'Bakeway Customer Notes'
                    ]
            );
        }

        if (version_compare($context->getVersion(), '1.0.4', '<')) {
            $orderTable = 'sales_order';
            $invoiceTable = 'sales_invoice';

            /**
             * sales_order table attributes 'order_tracking_token'
             */
            $setup->getConnection()->addColumn(
                    $setup->getTable($orderTable), 'order_tracking_token',
                    [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'length' => 255,
                'comment' => 'Order Tracking Token'
                    ]
            );

            /**
             * sales_invoice table attributes 'order_tracking_token'
             */
            $setup->getConnection()->addColumn(
                    $setup->getTable($invoiceTable), 'order_tracking_token',
                    [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'length' => 255,
                'comment' => 'Order Tracking Token'
                    ]
            );
        }


        if (version_compare($context->getVersion(), '1.0.5', '<')) {
            $orderItemTable = 'sales_order_item';

            /*
             * column item_image_url
             */
            $setup->getConnection()->addColumn(
                    $setup->getTable($orderItemTable), 'item_image_url',
                    [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'length' => 255,
                'comment' => 'Item Image Url'
                    ]
            );
            /*
             * column item_flavour
             */
            $setup->getConnection()->addColumn(
                    $setup->getTable($orderItemTable), 'item_flavour',
                    [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'length' => 255,
                'comment' => 'Item Flavour'
                    ]
            );
            /*
             * column item_weight
             */
            $setup->getConnection()->addColumn(
                    $setup->getTable($orderItemTable), 'item_weight',
                    [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'length' => 255,
                'comment' => 'Item Weight'
                    ]
            );
            /*
             * column item_ingredient
             */
            $setup->getConnection()->addColumn(
                    $setup->getTable($orderItemTable), 'item_ingredient',
                    [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'length' => 255,
                'comment' => 'Item Ingredient'
                    ]
            );
        }

        if (version_compare($context->getVersion(), '1.0.7', '<')) {
            $orderTable = 'sales_order';
            $invoiceTable = 'sales_invoice';

            /**
             * sales_order table attributes 'store_unique_name'
             */
            $setup->getConnection()->addColumn(
                    $setup->getTable($orderTable), 'store_unique_name',
                    [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'length' => 255,
                'comment' => 'Store Unique Name'
                    ]
            );

            /**
             * sales_invoice table attributes 'store_unique_name'
             */
            $setup->getConnection()->addColumn(
                    $setup->getTable($invoiceTable), 'store_unique_name',
                    [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'length' => 255,
                'comment' => 'Store Unique Name'
                    ]
            );
        }
        if (version_compare($context->getVersion(), '1.0.8', '<')) {
            $quote = 'quote';
            /**
             * quote table attributes 'store_unique_name'
             */
            $setup->getConnection()->addColumn(
                    $setup->getTable($quote), 'store_unique_name',
                    [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'length' => 255,
                'comment' => 'Store Unique Name'
                    ]
            );
        }

        //Custom attribute for Message on Card
        if (version_compare($context->getVersion(), '1.0.9', '<')) {
            $setup->getConnection()->addColumn(
                    $setup->getTable($quoteItem), 'message_on_card',
                    [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'comment' => 'Custom Message On Card'
                    ]
            );

            /**
             * sales_order_item table attribute 'custom_message'
             */
            $setup->getConnection()->addColumn(
                    $setup->getTable($orderItemTable), 'message_on_card',
                    [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'comment' => 'Custom Message On Card'
                    ]
            );

            /**
             * sales_invoice_item table attribute 'custom_message'
             */
            $setup->getConnection()->addColumn(
                    $setup->getTable($invoiceItemTable), 'message_on_card',
                    [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'comment' => 'Custom Message On Card'
                    ]
            );
        }
        
        if (version_compare($context->getVersion(), '1.0.10', '<')) {            
            $setup->getConnection()->addColumn(
                    $setup->getTable($quoteItem), 'vendor_product_code',
                    [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'comment' => 'Vendor Product Code',
                'default' => NULL
                    ]
            );

            /**
             * sales_order_item table attribute 'custom_message'
             */
            $setup->getConnection()->addColumn(
                    $setup->getTable($orderItemTable), 'vendor_product_code',
                    [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'comment' => 'Vendor Product Code',
                'default' => NULL
                    ]
            );

            /**
             * sales_invoice_item table attribute 'vendor_product_code'
             */
            $setup->getConnection()->addColumn(
                    $setup->getTable($invoiceItemTable), 'vendor_product_code',
                    [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'comment' => 'Vendor Product Code',
                'default' => NULL
                    ]
            );
        }


        if (version_compare($context->getVersion(), '1.0.11', '<')) {
            $orderTable = 'sales_order';
            /**
             * sales_order table attributes 'order_email_flag'
             */
            $setup->getConnection()->addColumn(
                $setup->getTable($orderTable), 'order_email_flag',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                    'default' => 0,
                    'comment' => 'Order Email Flag'
                ]
            );

            /**
             * sales_order table attributes 'order_sms_flag'
             */
            $setup->getConnection()->addColumn(
                $setup->getTable($orderTable), 'order_sms_flag',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                    'default' => 0,
                    'comment' => 'Order Sms Flag'
                ]
            );

            /**
             * sales_order table attributes 'order_push_notification__flag'
             */
            $setup->getConnection()->addColumn(
                $setup->getTable($orderTable), 'order_push_notification_flag',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                    'default' => 0,
                    'comment' => 'Order Push Notification Flag'
                ]
            );
        }

        if (version_compare($context->getVersion(), '1.0.12', '<')) {
            $table = $setup->getTable('sales_order');
            $sql = "update $table  set order_email_flag = 1,order_sms_flag = 1, order_push_notification_flag = 1";
            $setup->run($sql);

        }

        }

}
