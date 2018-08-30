<?php
/**
 * Webkul Software.
 *
 * @category  Webkul
 * @package   Webkul_Marketplace
 * @author    Webkul
 * @copyright Copyright (c) 2010-2017 Webkul Software Private Limited (https://webkul.com)
 * @license   https://store.webkul.com/license.html
 */

namespace Webkul\Marketplace\Model\ResourceModel\Saleslist;

use \Webkul\Marketplace\Model\ResourceModel\AbstractCollection;

/**
 * Webkul Marketplace ResourceModel Saleslist collection
 */
class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'entity_id';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            'Webkul\Marketplace\Model\Saleslist',
            'Webkul\Marketplace\Model\ResourceModel\Saleslist'
        );
        $this->_map['fields']['entity_id'] = 'main_table.entity_id';
        $this->_map['fields']['created_at'] = 'main_table.created_at';
    }

    
    /**
     * Add filter by store
     *
     * @param int|array|\Magento\Store\Model\Store $store
     * @param bool $withAdmin
     * @return $this
     */
    public function addStoreFilter($store, $withAdmin = true)
    {
        if (!$this->getFlag('store_filter_added')) {
            $this->performAddStoreFilter($store, $withAdmin);
        }
        return $this;
    }

    /**
     * Retrieve clear select
     *
     * @return \Magento\Framework\DB\Select
     */
    protected function _getClearSelect()
    {
        return $this->_buildClearSelect();
    }

    /**
     * Build clear select
     *
     * @param \Magento\Framework\DB\Select $select
     * @return \Magento\Framework\DB\Select
     */
    protected function _buildClearSelect($select = null)
    {
        if (null === $select) {
            $select = clone $this->getSelect();
        }
        $select->reset(\Magento\Framework\DB\Select::ORDER);
        $select->reset(\Magento\Framework\DB\Select::LIMIT_COUNT);
        $select->reset(\Magento\Framework\DB\Select::LIMIT_OFFSET);
        $select->reset(\Magento\Framework\DB\Select::COLUMNS);

        return $select;
    }

    /**
     * Retrieve all mageproduct_id for collection
     *
     * @param int|string $limit
     * @param int|string $offset
     * @return array
     */
    public function getAllOrderIds($limit = null, $offset = null)
    {
        $idsSelect = $this->_getClearSelect();
        $idsSelect->columns('order_id');
        $idsSelect->distinct('order_id');
        $idsSelect->limit($limit, $offset);
        $idsSelect->resetJoinLeft();

        return $this->getConnection()->fetchCol($idsSelect, $this->_bindParams);
    }

    /**
     * Retrieve all mageproduct_id for collection
     *
     * @param int|string $limit
     * @param int|string $offset
     * @return array
     */
    public function getAllOrderProducts()
    {
        $this->getSelect()
        ->columns('SUM(magequantity) AS qty')
        ->group('mageproduct_id')
        ->order('qty desc')
        ->limit(5);
        return $this;
    }

    /**
     * @return this
     */
    public function getPricebyorderData()
    {
        $this->getSelect()
        ->columns('SUM(actual_seller_amount) AS total')
        ->group('order_id');
        return $this;
    }

    /**
     * Set seller order data for given condition
     *
     * @param array $condition
     * @param array $attributeData
     * @return void
     */
    public function setSalesListData($id, $attributeData)
    {
        $where = ['entity_id=?' => (int)$id];
        return $this->getConnection()->update(
            $this->getTable('marketplace_saleslist'),
            $attributeData,
            $where
        );
    }

    /**
     * Join store relation table if there is store filter
     *
     * @return void
     */
    public function getSellerOrderCollection()
    {
        $salesOrder = $this->getTable('sales_order');
        $salesOrderItem = $this->getTable('sales_order_item');

        $this->getSelect()->join(
            $salesOrder.' as so',
            'main_table.order_id = so.entity_id',
            ['status' => 'status']
        );

        $this->getSelect()->join(
            $salesOrderItem.' as soi',
            'main_table.order_item_id = soi.item_id AND main_table.order_id = soi.order_id',
            [
                'item_id' => 'item_id',
                'qty_canceled' => 'qty_canceled',
                'qty_invoiced' => 'qty_invoiced',
                'qty_ordered' => 'qty_ordered',
                'qty_refunded' => 'qty_refunded',
                'qty_shipped' => 'qty_shipped',
                'product_options' => 'product_options',
                'mage_parent_item_id' => 'parent_item_id'
            ]
        );
        return $this;
    }
}
