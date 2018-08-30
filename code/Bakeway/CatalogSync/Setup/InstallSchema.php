<?php
/**
 * Bakeway
 *
 * @category  Bakeway
 * @package   Bakeway_CatalogSync
 * @author    Bakeway
 */

namespace Bakeway\CatalogSync\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

/**
 * @codeCoverageIgnore
 */
class InstallSchema implements InstallSchemaInterface
{
    /**
     * {@inheritdoc}
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;

        $installer->startSetup();

        /**
         * Create table 'bakeway_catalog_sync'
         */
        $table = $installer->getConnection()
            ->newTable($installer->getTable('bakeway_catalog_sync'))
            ->addColumn(
                'id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                'ID'
            )
            ->addColumn(
                'product_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false],
                'Product Entity Id'
            )
            ->addColumn(
                'seller_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false,'default' => '0'],
                'Seller ID'
            )
            ->addColumn(
                'is_configurable',
                \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => true,'default' => 0],
                'Is Configurable'
            )
            ->addColumn(
                'special_price',
                \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                [12,4],
                ['nullable' => false, 'default' => 00],
                'Special Price'
            )
            ->addColumn(
                'price_incl_tax',
                \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                [12,4],
                ['nullable' => false, 'default' => 00],
                'Price Incl Tax'
            )
            ->addColumn(
                'price_excl_tax',
                \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                [12,4],
                ['nullable' => false, 'default' => 00],
                'Price Excl Tax'
            )
            ->addColumn(
                'catalog_discount_price',
                \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                [12,4],
                ['nullable' => false, 'default' => 00],
                'Catalog Rule Discount Price'
            )
            ->addColumn(
                'catalog_discount_price_incl_tax',
                \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                [12,4],
                ['nullable' => false, 'default' => 00],
                'Catalog Rule Discount Price Incl Tax'
            )
            ->addColumn(
                'catalog_rule_name',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                255,
                ['nullable' => true, 'default' => null],
                'Catalog Rule Name'
            )
            ->addColumn(
                'cake_flavour',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                255,
                ['nullable' => true, 'default' => null],
                'Flavour Attribute Text'
            )
            ->addColumn(
                'cake_ingredients',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                255,
                ['nullable' => true, 'default' => null],
                'Ingredient Attribute Text'
            )
            ->addColumn(
                'cake_weight',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                255,
                ['nullable' => true, 'default' => null],
                'Weight Attribute Text'
            )
            ->addColumn(
                'advance_order_intimation',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                255,
                ['nullable' => true, 'default' => "00"],
                'Advance Order Intimation Time'
            )
            ->addColumn(
                'categories_json',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                null,
                ['nullable' => true, 'default' => null],
                'Serialized Categories Json'
            )
            ->setComment('Synced catalog product table');
        $installer->getConnection()->createTable($table);

        $installer->getConnection()->addForeignKey(
            $installer->getFkName(
                'bakeway_catalog_sync',
                'product_id',
                'catalog_product_entity',
                'entity_id'
            ),
            $installer->getTable('bakeway_catalog_sync'),
            'product_id',
            $installer->getTable('catalog_product_entity'),
            'entity_id',
            \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
        );
    }
}