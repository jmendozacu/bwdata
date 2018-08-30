<?php
/**
 * Bakeway
 *
 * @category  Bakeway
 * @package   Bakeway_PartnerCatalogRule
 * @author    Bakeway
 */

namespace Bakeway\PartnerCatalogRule\Model\ResourceModel\PartnerCatalogRule;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection as DbAbstractCollection;

/**
 * ResourceModel PartnerCatalogRule data collection
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
        $this->_init(
            'Bakeway\PartnerCatalogRule\Model\PartnerCatalogRule',
            'Bakeway\PartnerCatalogRule\Model\ResourceModel\PartnerCatalogRule');
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
