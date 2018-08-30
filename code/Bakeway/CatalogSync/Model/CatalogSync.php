<?php
/**
 * Bakeway
 *
 * @category  Bakeway
 * @package   Bakeway_CatalogSync
 * @author    Bakeway
 */

namespace Bakeway\CatalogSync\Model;

use Magento\Framework\Model\AbstractModel;
use Magento\Framework\DataObject\IdentityInterface;

/**
 * Bakeway CatalogSync Model.

 */
class CatalogSync extends AbstractModel implements IdentityInterface
{
    /**
     * Bakeway CatalogSync cache tag.
     */
    const CACHE_TAG = 'bakeway_catalog_sync';

    /**
     * @var string
     */
    protected $_cacheTag = 'bakeway_catalog_sync';

    /**
     * Prefix of model events names.
     *
     * @var string
     */
    protected $_eventPrefix = 'bakeway_catalog_sync';

    /**
     * Initialize resource model.
     */
    protected function _construct()
    {
        $this->_init('Bakeway\CatalogSync\Model\ResourceModel\CatalogSync');
    }

    /**
     * Load object data.
     *
     * @param int|null $id
     * @param string   $field
     *
     * @return $this
     */
    public function load($id, $field = null)
    {
        if ($id === null) {
            return $this->noRouteSeller();
        }

        return parent::load($id, $field);
    }

    /**
     * Get identities.
     *
     * @return array
     */
    public function getIdentities()
    {
        return [self::CACHE_TAG.'_'.$this->getId()];
    }
}
