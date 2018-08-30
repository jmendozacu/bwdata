<?php

namespace Bakeway\GrabIntigration\Setup;

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

        if (version_compare($context->getVersion(), '1.0.0', '<')) {

            $setup->getConnection()->addColumn(
                $setup->getTable('marketplace_userdata'),
                'is_grab_active',
                [
                    'type'      => \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                    'default'   => '0',
                    'comment'   => 'Grab Status Enable Or Disable'
                ]
            );

            $setup->getConnection()->addColumn(
                $setup->getTable('sales_order'),
                'grab_delivery_flag',
                [
                    'type'      => \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                    'default'   => '0',
                    'comment'   => 'Grab Delivery Flag Status'
                ]
            );

            $setup->getConnection()->addColumn(
                $setup->getTable('quote'),
                'grab_delivery_flag',
                [
                    'type'      => \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                    'default'   => '0',
                    'comment'   => 'Grab Delivery Flag Status'
                ]
            );

            $setup->getConnection()->addColumn(
                $setup->getTable('marketplace_orders'),
                'grab_delivery_flag',
                [
                    'type'      => \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                    'default'   => '0',
                    'comment'   => 'Grab Delivery Flag Status'
                ]
            );

            
        }


        if (version_compare($context->getVersion(), '1.0.1', '<')) {

            $setup->getConnection()->dropColumn($setup->getTable('marketplace_userdata'), 'is_grab_active', $schemaName = null);

            $setup->getConnection()->addColumn(
                $setup->getTable('bakeway_partner_locations'),
                'is_grab_active',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                    'default' => '0',
                    'comment' => 'Grab Status Enable Or Disable'
                ]
            );
        }
    }
}
