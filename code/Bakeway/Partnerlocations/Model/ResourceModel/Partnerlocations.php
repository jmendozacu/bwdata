<?php
/**
 * Bakeway
 *
 * @category  Bakeway
 * @package   Bakeway_Partnerlocations
 * @author    Bakeway
 */

namespace Bakeway\Partnerlocations\Model\ResourceModel;

class Partnerlocations extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $_date;

    /**
     * Construct
     *
     * @param \Magento\Framework\Model\ResourceModel\Db\Context $context
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $date
     * @param string $connectionName
     */
    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        $connectionName = null
    ) {
        parent::__construct($context, $connectionName);
        $this->_date = $date;
    }

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('bakeway_partner_locations', 'id');
    }

    /**
     * Load an object using 'identifier' field if there's no field specified and value is not numeric
     *
     * @param \Magento\Framework\Model\AbstractModel $object
     * @param mixed $value
     * @param string $field
     * @return $this
     */
    public function load(\Magento\Framework\Model\AbstractModel $object, $value, $field = null)
    {
        if (!is_numeric($value) && is_null($field)) {
            $field = 'identifier';
        }

        return parent::load($object, $value, $field);
    }

    /**
     * Process device data before saving
     *
     * @param \Magento\Framework\Model\AbstractModel $object
     * @return $this
     */
    protected function _beforeSave(\Magento\Framework\Model\AbstractModel $object)
    {
        return parent::_beforeSave($object);
    }

    /**
     * @return string
     */
    public function getLastSyncedOrderId() {
        $connection = $this->getConnection();
        $sql = "SELECT order_entity_id FROM `bakeway_order_sync_tracking`";
        $result = $connection->fetchOne($sql);
        return $result;
    }

    public function setLastSyncedOrderId($orderId) {
        $connection = $this->getConnection();
        $sql = "SELECT `id`, `order_entity_id` FROM `bakeway_order_sync_tracking`";
        $result = $connection->fetchRow($sql);
        if (isset($result['id']) &&
            $result['id'] > 0) {
            $sql = "UPDATE `bakeway_order_sync_tracking` SET `order_entity_id` = $orderId, `updated_at` = now()";
        } else {
            $sql = "INSERT INTO `bakeway_order_sync_tracking` (`order_entity_id`, `updated_at`) VALUES ($orderId, now())";
        }
        $connection->query($sql);
        return;
    }
}
