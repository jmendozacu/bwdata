<?php
/**
 * Bakeway
 *
 * @category  Bakeway
 * @package   Bakeway_SubLocations
 * @author    Bakeway
 */

namespace Bakeway\SubLocations\Model;

use Magento\Framework\Model\AbstractModel;
use Bakeway\VendorNotification\Api\Data\SellerdevicedataInterface;
use Magento\Framework\DataObject\IdentityInterface;

/**
 * Bakeway SubLocations Model.

 */
class SubLocations extends AbstractModel implements IdentityInterface
{
    /**
     * Bakeway SubLocations cache tag.
     */
    const CACHE_TAG = 'bakeway_sub_locations';

    /**
     * @var string
     */
    protected $_cacheTag = 'bakeway_sub_locations';

    /**
     * Prefix of model events names.
     *
     * @var string
     */
    protected $_eventPrefix = 'bakeway_sub_locations';

    /**
     * Initialize resource model.
     */
    protected function _construct()
    {
        $this->_init('Bakeway\SubLocations\Model\ResourceModel\SubLocations');
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
