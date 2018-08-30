<?php

namespace Bakeway\ReviewRating\Setup;

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
                $setup->getTable('sales_order'),
                'order_review_token',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'length' => 255,
                    'comment' => 'Order Review Token'
                ]
            );
        }

        /**
        *adding columns to rating table
        */

        if (version_compare($context->getVersion(), '1.0.3', '<')) {

            $setup->getConnection()->addColumn(
                $setup->getTable('rating'),
                'rating_type',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                    'comment' => 'Rating Type'
                ]
            );
        }

        /**
         *adding columns to rating table
         */

        if (version_compare($context->getVersion(), '1.0.4', '<')) {

            $setup->getConnection()->addColumn(
                $setup->getTable('rating'),
                'q_type',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'comment' => 'Question Type'
                ]
            );
        }


        /**
         *adding columns to review table
         */

        if (version_compare($context->getVersion(), '1.0.7', '<')) {

            $setup->getConnection()->addColumn(
                $setup->getTable('review'),
                'order_id',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'comment' => 'Order Id'
                ]
            );

            $setup->getConnection()->addColumn(
                $setup->getTable('review'),
                'order_review_status',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                    'comment' => 'Review Status for Order',
                    'default' => 0
                ]
            );
        }

        if (version_compare($context->getVersion(), '1.0.9', '<')) {
            $table = $setup->getTable('review');
            $sql = "ALTER TABLE `rating` CHANGE `rating_code` `rating_code` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT 'Rating Code' ";
            $setup->run($sql);

        }

        if (version_compare($context->getVersion(), '1.0.10', '<')) {
            $setup->getConnection()->addColumn(
                $setup->getTable('review'),
                'seller_id',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    'comment' => 'Seller Id',
                    'length' => 11
                ]
            );
        }
    }
}


