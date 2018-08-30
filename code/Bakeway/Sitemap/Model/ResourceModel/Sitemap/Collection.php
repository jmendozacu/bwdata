<?php
/**
 * Bakeway
 *
 * @category  Bakeway
 * @package   Bakeway_Sitemap
 * @author    Bakeway
 */

namespace Bakeway\Sitemap\Model\ResourceModel\Sitemap;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection as DbAbstractCollection;

/**
 * ResourceModel CatalogSync data collection
 */
class Collection extends DbAbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'id';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Bakeway\Sitemap\Model\Sitemap', 'Bakeway\Sitemap\Model\ResourceModel\Sitemap');
    }


    /**
     * Add field filter to collection.
     *
     * @param array|string          $field
     * @param string|int|array|null $condition
     *
     * @return $this
     */
    public function addFieldToFilter($field, $condition = null)
    {
        return parent::addFieldToFilter($field, $condition);
    }
}
