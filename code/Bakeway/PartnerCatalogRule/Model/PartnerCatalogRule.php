<?php
/**
 * Bakeway
 *
 * @category  Bakeway
 * @package   Bakeway_PartnerCatalogRule
 * @author    Bakeway
 */

namespace Bakeway\PartnerCatalogRule\Model;

use Magento\Framework\Model\AbstractModel;
use Magento\Framework\DataObject\IdentityInterface;

/**
 * Bakeway PartnerCatalogRule Model.

 */
class PartnerCatalogRule extends AbstractModel implements IdentityInterface
{
    /**
     * Bakeway PartnerCatalogRule cache tag.
     */
    const CACHE_TAG = 'bakeway_partnercatalogrule';

    /**
     * @var string
     */
    protected $_cacheTag = 'bakeway_partnercatalogrule';

    /**
     * Prefix of model events names.
     *
     * @var string
     */
    protected $_eventPrefix = 'bakeway_partnerlocations';

    /**
     * Initialize resource model.
     */
    protected function _construct()
    {
        $this->_init('Bakeway\PartnerCatalogRule\Model\ResourceModel\PartnerCatalogRule');
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
