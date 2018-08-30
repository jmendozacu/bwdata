<?php
/**
 * Bakeway
 *
 * @category  Bakeway
 * @package   Bakeway_PartnerCatalogRule
 * @author    Bakeway
 */

namespace Bakeway\PartnerCatalogRule\Block\Adminhtml\Customer;

class Edit extends \Magento\Backend\Block\Widget {

    /**
     * Core registry.
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    /**
     * @var null
     */
    protected $_objectManager = null;

    /**
     * @var \Magento\Directory\Model\Currency
     */
    protected $_currency;

    /**
     * @param \Magento\Backend\Block\Widget\Context     $context
     * @param \Magento\Framework\Registry               $registry
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param \Magento\Directory\Model\Currency         $currency
     * @param array                                     $data
     */
    public function __construct(
        \Magento\Backend\Block\Widget\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Directory\Model\Currency $currency,
        array $data = []
    ) {
        $this->_coreRegistry = $registry;
        $this->_objectManager = $objectManager;
        $this->_currency = $currency;
        parent::__construct($context, $data);
    }

    public function getSellerCatalogRuleCollection() {
        $customerId = $this->getRequest()->getParam('id');
        $data = [];
        if ($customerId != '') {
            $collection = $this->_objectManager->create(
                'Bakeway\PartnerCatalogRule\Model\PartnerCatalogRule'
            )->getCollection()
                ->addFieldToFilter('seller_id', $customerId);
            foreach ($collection as $record) {
                $data = $record->getData();
            }
            return $data;
        }
    }
}