<?php
/**
 * Bakeway
 *
 * @category  Bakeway
 * @package   Bakeway_SubLocations
 * @author    Bakeway
 */

namespace Bakeway\SubLocations\Model\ResourceModel\SubLocations;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection as DbAbstractCollection;

/**
 * ResourceModel SubLocations data collection
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
        $this->_init('Bakeway\SubLocations\Model\SubLocations',
            'Bakeway\SubLocations\Model\ResourceModel\SubLocations');
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
