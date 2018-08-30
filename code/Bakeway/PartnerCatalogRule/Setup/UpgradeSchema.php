<?php
/**
 * Bakeway
 *
 * @category  Bakeway
 * @package   Bakeway_PartnerCatalogRule
 * @author    Bakeway
 */
namespace Bakeway\PartnerCatalogRule\Setup;

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
            $salesRuleTable = 'salesrule';
            //Setup max discount amount for salesrule
            $setup->getConnection()
                ->addColumn(
                    $setup->getTable($salesRuleTable),
                    'max_discount_amount',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                        'length' => '12,4',
                        'default' => 0.0000,
                        'nullable' => true,
                        'comment' => 'Max Discount Amount'
                    ]
                );
        }
        if (version_compare($context->getVersion(), '1.0.2', '<')) {
            $catalogRuleTable = 'catalogrule';
            //Setup fixed order delivery date on which discount will get applied.
            $setup->getConnection()
                ->addColumn(
                    $setup->getTable($catalogRuleTable),
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
                    $setup->getTable($catalogRuleTable),
                    'fixed_discount_end_date',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DATE,
                        'default' => Null,
                        'nullable' => true,
                        'comment' => 'Fixed Discount End Date'
                    ]
                );
            $catalogRuleProductTable = 'catalogrule_product';
            //Setup fixed order delivery time on which discount will get applied.
            $setup->getConnection()
                ->addColumn(
                    $setup->getTable($catalogRuleProductTable),
                    'fixed_discount_start_time',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                        'length' => 11,
                        'default' => 0,
                        'nullable' => false,
                        'comment' => 'Fixed Discount Start Time'
                    ]
                );
            $setup->getConnection()
                ->addColumn(
                    $setup->getTable($catalogRuleProductTable),
                    'fixed_discount_end_time',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                        'length' => 11,
                        'default' => 0,
                        'nullable' => false,
                        'comment' => 'Fixed Discount End Time'
                    ]
                );
            $catalogRulePriceTable = 'catalogrule_product_price';
            //Setup fixed order delivery date on which discount will get applied.
            $setup->getConnection()
                ->addColumn(
                    $setup->getTable($catalogRulePriceTable),
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
                    $setup->getTable($catalogRulePriceTable),
                    'fixed_discount_end_date',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DATE,
                        'default' => Null,
                        'nullable' => true,
                        'comment' => 'Fixed Discount End Date'
                    ]
                );
        }
        $setup->endSetup();
    }
}