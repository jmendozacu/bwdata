<?php
/**
 * Bakeway
 *
 * @category  Bakeway
 * @package   Bakeway_Catalogsync
 * @author    Bakeway
 */
namespace Bakeway\Catalogsync\Setup;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;

class UpgradeSchema implements UpgradeSchemaInterface
{
    /**
     * Upgrades DB schema for a module
     *
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     * @return void
     */
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        if (version_compare($context->getVersion(), '1.0.1', '<')) {
            $catalogSyncTable = 'bakeway_catalog_sync';
            //Setup fixed order delivery date on which discount will get applied.
            $setup->getConnection()
                ->addColumn(
                    $setup->getTable($catalogSyncTable),
                    'fixed_discount_start_date',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DATE,
                        'default' => Null,
                        'nullable' => true,
                        'comment' => 'Fixed Discount Start Date'
                    ]
                );
            $setup->getConnection()
                ->addColumn(
                    $setup->getTable($catalogSyncTable),
                    'fixed_discount_end_date',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DATE,
                        'default' => Null,
                        'nullable' => true,
                        'comment' => 'Fixed Discount End Date'
                    ]
                );
        }

        if (version_compare($context->getVersion(), '1.0.2', '<')) {
            $catalogSyncTable = 'bakeway_catalog_sync';
            $setup->getConnection()
                ->addColumn(
                    $setup->getTable($catalogSyncTable),
                    'advanced_order_intimation_unit',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                         255,
                        'nullable' => true,
                        'comment' => 'Advanced Order Intimation Unit'
                    ]
                );


        }
        $setup->endSetup();
    }
}