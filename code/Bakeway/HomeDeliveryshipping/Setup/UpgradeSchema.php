<?php

namespace Bakeway\HomeDeliveryshipping\Setup;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Ddl\Table;

/**
 * @codeCoverageIgnore
 */
class UpgradeSchema implements UpgradeSchemaInterface
{

    /**
     * {@inheritdoc}
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {

        /*
         *adding free delivery max input price log table
        */

        if (version_compare($context->getVersion(), '1.0.1', '<')) {
            $installer = $setup;
            $installer->startSetup();

            /*
             *Start bakeway free shipping Log table
            */
            $table = $installer->getConnection()->newTable(
                $installer->getTable('bakeway_freeshipping_log')
            )->addColumn(
                'id',
                Table::TYPE_INTEGER,
                null,
                array('identity' => true, 'nullable' => false, 'primary' => true,'unsigned' => true),
                'Id'
            )->addColumn(
                'seller_id',
                Table::TYPE_INTEGER,
                null,
                array('nullable' => false,'unsigned' => true),
                'Seller Id'
            )->addColumn(
                'max_free_shipping_price',
                Table::TYPE_DECIMAL,
                '12,4',
                ['unsigned' => true, 'nullable' => false, 'default' => '0.0000'],
                'Maximum free shipping Price'
            )->addColumn(
                'created_at',
                Table::TYPE_TIMESTAMP,
                255,
                array('nullable' => false, Table::TIMESTAMP_INIT),
                'Created At'
            )->addColumn(
                'updated_at',
                Table::TYPE_TIMESTAMP,
                255,
                array('nullable' => false, Table::TIMESTAMP_INIT_UPDATE),
                'Updated At'
            )->addColumn(
                'created_by',
                 Table::TYPE_TEXT,
                 255,
                 ['nullable' => true, 'default' => null],
                 'Created By'
            );
            $installer->getConnection()->createTable($table);
            /*
             *End bakeway free shipping Log table
            */
            $installer->endSetup();

        }
    }
}