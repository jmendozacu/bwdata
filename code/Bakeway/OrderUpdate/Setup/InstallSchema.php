<?php
namespace Bakeway\OrderUpdate\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Ddl\Table;

class InstallSchema implements InstallSchemaInterface
{
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();

        /**
         *  bakeway order update table
         */
        $table = $installer->getConnection()->newTable(
            $installer->getTable('bakeway_order_update')
        )->addColumn(
                'entity_id',
                Table::TYPE_INTEGER,
                10,
                array('identity' => true, 'nullable' => false, 'primary' => true),
                'Entity Id'
        )->addColumn(
                'order_id',
                Table::TYPE_TEXT,
                255,
                array('nullable' => false,'default' => null),
                'Order Id'
        )->addColumn(
                'created_at',
                Table::TYPE_TIMESTAMP,
                255,
                array('nullable' => false, Table::TIMESTAMP_INIT),
                'Created At'
        )->addColumn(
                'created_by',
                Table::TYPE_TEXT,
                255,
                ['nullable' => true, 'default' => null],
                'Created By'
        )->addIndex(
                $installer->getIdxName('bakeway_order_update', ['order_id']),
                ['order_id']
        )
        ->addIndex(
                $installer->getIdxName('bakeway_order_update', ['created_by']),
                ['created_by']
        );

        $installer->getConnection()->createTable($table);

        $installer->endSetup();
    }
}