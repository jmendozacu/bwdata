<?php
/**
 * Bakeway
 *
 * @category  Bakeway
 * @package   Bakeway_Paytm
 * @author    Bakeway
 */

namespace Bakeway\Paytm\Setup;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

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
         *adding columns to table sales_order_payment
        */

        if (version_compare($context->getVersion(), '1.0.0', '<')) {

            $setup->getConnection()->addColumn(
                $setup->getTable('sales_order_payment'),
                'paytm_txn_id',
                [
                    'type'      => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'length'    => 255,
                    'comment'   => 'Paytm Transaction Id',
                    'default'   => Null
                ]
            );

            $setup->getConnection()->addColumn(
                $setup->getTable('sales_order_payment'),
                'paytm_checksumhash',
                [
                    'type'      => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'length'    => 255,
                    'comment'   => 'Paytm Checksum Hash',
                    'default'   => Null
                ]
            );

            $setup->getConnection()->addColumn(
                $setup->getTable('sales_payment_transaction'),
                'paytm_txn_id',
                [
                    'type'      => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'length'    => 255,
                    'comment'   => 'Paytm Transaction Id',
                    'default'   => Null
                ]
            );

            $setup->getConnection()->addColumn(
                $setup->getTable('sales_payment_transaction'),
                'paytm_checksumhash',
                [
                    'type'      => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'length'    => 255,
                    'comment'   => 'Paytm Checksum Hash',
                    'default'   => Null
                ]
            );
        }

        if (version_compare($context->getVersion(), '1.0.1', '<')) {
            $setup->getConnection()->addColumn(
                $setup->getTable('quote'),
                'paytm_txn_id',
                [
                    'type'      => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'length'    => 255,
                    'comment'   => 'Paytm Transaction Id',
                    'default'   => Null
                ]
            );

            $setup->getConnection()->addColumn(
                $setup->getTable('quote'),
                'paytm_checksumhash',
                [
                    'type'      => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'length'    => 255,
                    'comment'   => 'Paytm Checksum Hash',
                    'default'   => Null
                ]
            );

        }

        if (version_compare($context->getVersion(), '1.0.2', '<')) {
            $setup->getConnection()->dropColumn(
                $setup->getTable('sales_order_payment'),
                'paytm_checksumhash'
            );
            $setup->getConnection()->dropColumn(
                $setup->getTable('sales_payment_transaction'),
                'paytm_checksumhash'
            );
            $setup->getConnection()->dropColumn(
                $setup->getTable('quote'),
                'paytm_checksumhash'
            );
        }

        if (version_compare($context->getVersion(), '1.0.3', '<')) {
            $setup->getConnection()->addColumn(
                $setup->getTable('quote'),
                'paytm_order_id',
                [
                    'type'      => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'length'    => 255,
                    'comment'   => 'Paytm Order Id',
                    'default'   => Null
                ]
            );
        }
    }
}