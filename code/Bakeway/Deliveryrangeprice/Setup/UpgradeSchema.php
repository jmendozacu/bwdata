<?php

namespace Bakeway\Deliveryrangeprice\Setup;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

/**
 * @codeCoverageIgnore
 */
class UpgradeSchema implements UpgradeSchemaInterface {

    /**
     * {@inheritdoc}
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context) {

        /*
         *adding columns deleted and seller log
        */

        if (version_compare($context->getVersion(), '1.0.1', '<')) {

            $setup->getConnection()->addColumn(
                $setup->getTable('bakeway_delivery_rangeprice'),
                'delivery_deleted',
                [
                    'type'      => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    'length'    => 10,
                    'comment'   => 'Delivery deleted flag'
                ]
            );

            $setup->getConnection()->addColumn(
                $setup->getTable('bakeway_delivery_rangeprice'),
                'seller_log',
                [
                    'type'      => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'length'    => 255,
                    'comment'   => 'Deleting log details'
                ]
            );

            
        }

        if (version_compare($context->getVersion(), '1.0.2', '<')) {
            $setup->getConnection()->addColumn(
                $setup->getTable('marketplace_userdata'), 'max_price', [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                    'default' => '0.0000',
                    'unsigned' => true,
                    'nullable' => false,
                    'comment' => 'Max Inout Price For Free Delivery'
                ]
            );

        }

        if (version_compare($context->getVersion(), '1.0.2', '<')) {
            $setup->getConnection()->addColumn(
                $setup->getTable('marketplace_userdata'), 'max_price', [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                    'default' => '0.0000',
                    'unsigned' => true,
                    'nullable' => false,
                    'comment' => 'Max Inout Price For Free Delivery'
                ]
            );

        }

        if (version_compare($context->getVersion(), '1.0.3', '<')) {

            $table = $setup->getTable('marketplace_userdata');
            $sql = "ALTER TABLE $table CHANGE `max_price` `is_deivery_max_price` DECIMAL(10,0) NOT NULL DEFAULT '0' COMMENT 'Max Inout Price For Free Delivery' ";
            $setup->run($sql);

        }

        if (version_compare($context->getVersion(), '1.0.4', '<')) {

            $setup->getConnection()->addColumn(
                $setup->getTable('marketplace_userdata'), 'is_free_delivery', [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                    'unsigned' => true,
                    'nullable' => false,
                    'comment' => 'Seller Free Delivery Status'
                ]
            );

        }
    }
}