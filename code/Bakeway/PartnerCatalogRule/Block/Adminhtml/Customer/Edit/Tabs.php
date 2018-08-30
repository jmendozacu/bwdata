<?php
/**
 * Bakeway
 *
 * @category  Bakeway
 * @package   Bakeway_PartnerCatalogRule
 * @author    Bakeway
 */

namespace Bakeway\PartnerCatalogRule\Block\Adminhtml\Customer\Edit;

use Magento\Customer\Controller\RegistryConstants;
use Magento\Ui\Component\Layout\Tabs\TabInterface;
use Magento\Backend\Block\Widget\Form;
use Magento\Backend\Block\Widget\Form\Generic;

/**
 * Customer Seller form block.
 */
class Tabs extends Generic implements TabInterface {

    /**
     * @var \Magento\Store\Model\System\Store
     */
    protected $_systemStore;

    /**
     * @var ObjectManagerInterface
     */
    protected $_objectManager;

    /**
     * Core registry.
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry;

    /**
     * @var \Bakeway\PartnerCatalogRule\Helper\Data
     */
    protected $partnerRulesHelper;

    /**
     * Tabs constructor.
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param \Magento\Store\Model\System\Store $systemStore
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param \Bakeway\PartnerCatalogRule\Helper\Data $partnerRulesHelper
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context, 
        \Magento\Framework\Registry $registry, 
        \Magento\Framework\Data\FormFactory $formFactory, 
        \Magento\Store\Model\System\Store $systemStore,
        \Magento\Framework\ObjectManagerInterface $objectManager, 
        \Bakeway\PartnerCatalogRule\Helper\Data $partnerRulesHelper,
        array $data = []
    ) {
        $this->_coreRegistry = $registry;
        $this->_systemStore = $systemStore;
        $this->_objectManager = $objectManager;
        $this->partnerRulesHelper = $partnerRulesHelper;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    /**
     * @return string|null
     */
    public function getCustomerId() {
        return $this->_coreRegistry->registry(
            RegistryConstants::CURRENT_CUSTOMER_ID
        );
    }

    /**
     * @return \Magento\Framework\Phrase
     */
    public function getTabLabel() {
        return __('Seller Catalog Rule');
    }

    /**
     * @return \Magento\Framework\Phrase
     */
    public function getTabTitle() {
        return __('Seller Catalog Rule');
    }

    /**
     * @return bool
     */
    public function canShowTab() {
        $coll = $this->_objectManager->create(
            'Webkul\Marketplace\Block\Adminhtml\Customer\Edit'
        )->getMarketplaceUserCollection();
        $isSeller = false;
        foreach ($coll as $row) {
            $isSeller = $row->getIsSeller();
        }
        if ($this->getCustomerId() && $isSeller) {
            return true;
        }

        return false;
    }

    /**
     * @return bool
     */
    public function isHidden() {
        $coll = $this->_objectManager->create(
            'Webkul\Marketplace\Block\Adminhtml\Customer\Edit'
        )->getMarketplaceUserCollection();
        $isSeller = false;
        foreach ($coll as $row) {
            $isSeller = $row->getIsSeller();
        }
        if ($this->getCustomerId() && $isSeller) {
            return false;
        }

        return true;
    }

    /**
     * Tab class getter.
     *
     * @return string
     */
    public function getTabClass() {
        return '';
    }

    /**
     * Return URL link to Tab content.
     *
     * @return string
     */
    public function getTabUrl() {
        return '';
    }

    /**
     * Tab should be loaded trough Ajax call.
     *
     * @return bool
     */
    public function isAjaxLoaded() {
        return false;
    }

    public function initForm() {
        if (!$this->canShowTab()) {
            return $this;
        }
        /*         * @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create();
        $form->setHtmlIdPrefix('marketplace_');
        $customerId = $this->_coreRegistry->registry(
            RegistryConstants::CURRENT_CUSTOMER_ID
        );
        $storeid = $this->_storeManager->getStore()->getId();

        $fieldset = $form->addFieldset(
            'base_fieldset', ['legend' => __('Seller Catalog Rule')]
        );
        $customer = $this->_objectManager->create(
            'Magento\Customer\Model\Customer'
        )->load($customerId);
        $partner = $this->_objectManager->create(
            'Bakeway\PartnerCatalogRule\Block\Adminhtml\Customer\Edit'
        )->getSellerCatalogRuleCollection();

        if (isset($partner['rule_id']) && $partner['rule_id'] != '') {
            $ruleId = $partner['rule_id'];
        } else {
            $ruleId = "";
        }

        $fieldset->addField(
            'bakeway_partner_catalog_rule_id', 'select', [
                'name' => 'bakeway_partner_catalog_rule_id',
                'data-form-part' => $this->getData('target_form'),
                'label' => __('Catalog Rule'),
                'title' => __('Catalog Rule'),
                'value' => $ruleId,
                'values' => $this->partnerRulesHelper->getCatalogRulesOptionArray(),
            ]
        );
        $form->setUseContainer(true);

        $this->setForm($form);

        return parent::_prepareForm();
    }

    /**
     * @return string
     */
    protected function _toHtml() {
        if ($this->canShowTab()) {
            $this->initForm();

            return parent::_toHtml();
        } else {
            return '';
        }
    }

    /**
     * Prepare the layout.
     *
     * @return $this
     */
    public function getFormHtml() {
        $html = parent::getFormHtml();
        $html .= $this->getLayout()->createBlock(
            'Webkul\Marketplace\Block\Adminhtml\Customer\Edit\Js'
        )->toHtml();

        return $html;
    }

}
