<?php

namespace Bakeway\Partnerlocations\Setup;

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
                $setup->getTable('bakeway_partner_locations'),
                'sub_loc_id',
                [
                    'type'      => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    'comment'   => 'Suburb Location id'
                ]
            );

        }

        if (version_compare($context->getVersion(), '1.0.2', '<')) {

            $setup->getConnection()->addForeignKey(
                $setup->getFkName(
                    'bakeway_partner_locations',
                    'sub_loc_id',
                    'bakeway_sub_locations',
                    'id'
                ),
                $setup->getTable('bakeway_partner_locations'),
                'sub_loc_id',
                $setup->getTable('bakeway_sub_locations'),
                'id',
                \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
            );
        }

        if (version_compare($context->getVersion(), '1.0.3', '<')) {

            $setup->getConnection()->addColumn(
                $setup->getTable('bakeway_partner_locations'),
                'store_locality_meta_description',
                [
                    'type'      => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'comment'   => 'Store location meta description'
                ]
            );

        }
    }
}