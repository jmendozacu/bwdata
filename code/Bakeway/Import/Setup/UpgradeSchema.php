<?php

namespace Bakeway\Import\Setup;

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
        $installer = $setup;
        $installer->startSetup();

        /**Start table  bakeway catalog feed table**/
        $table = $installer->getConnection()->newTable(
            $installer->getTable('bakeway_catalog_feed')
        )->addColumn(
            'entity_id',
            Table::TYPE_INTEGER,
            10,
            array('identity' => true, 'nullable' => false, 'primary' => true),
            'Entity id'
        )->addColumn(
            'usage_count',
            Table::TYPE_INTEGER,
            11,
            array('nullable' => false),
            'Per Day Usage Count'
        )->addColumn(
            'date',
            Table::TYPE_DATE,
            255,
            array('nullable' => true),
            'File Downloading Date'
        );
        $installer->getConnection()->createTable($table);

        /**End table  bakeway catalog feed table*/

        $installer->endSetup();

    }
}