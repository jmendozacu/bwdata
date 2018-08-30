<?php
/**
 * Bakeway
 *
 * @category  Bakeway
 * @package   Bakeway_Partnerlocations
 * @author    Bakeway
 */

namespace Bakeway\Partnerlocations\Model;

use Magento\Framework\Model\AbstractModel;
use Magento\Framework\DataObject\IdentityInterface;

/**
 * Bakeway Partner sub locations Model.

 */
class PartnerSubLocations extends AbstractModel implements IdentityInterface
{
    /**
     * Bakeway Partner sub locations cache tag.
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
     * @var string
     */
    protected $_eventObject = 'sublocation';

    /**
     * Initialize resource model.
     */
    protected function _construct()
    {
        $this->_init('Bakeway\Partnerlocations\Model\ResourceModel\PartnerSubLocations');
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
