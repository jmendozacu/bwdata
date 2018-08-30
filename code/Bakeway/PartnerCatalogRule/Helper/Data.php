<?php
/**
 * Bakeway
 *
 * @category  Bakeway
 * @package   Bakeway_PartnerCatalogRule
 * @author    Bakeway
 */

namespace Bakeway\PartnerCatalogRule\Helper;

use Magento\CatalogRule\Model\ResourceModel\Rule\CollectionFactory as RulesCollection;
use Magento\CatalogRule\Model\Rule as CatalogRule;

/**
 * Bakeway VendorNotifcation Helper Data.
 */
class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var RulesCollection
     */
    protected $rulesCollection;

    /**
     * @var CatalogRule
     */
    protected $catalogRule;

    /**
     * @param \Magento\Framework\App\Helper\Context $context
     * @param RulesCollection $rulesCollection
     * @param CatalogRule $catalogRule
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        RulesCollection $rulesCollection,
        CatalogRule $catalogRule
    ) {
        $this->rulesCollection = $rulesCollection;
        $this->catalogRule = $catalogRule;
        parent::__construct($context);
    }

    public function getCatalogRulesOptionArray() {
        $result = [];
        $result[] = ['label' => __('---Please Select---'), 'value' => ''];
        $collection = $this->rulesCollection->create()
                        ->addFieldToFilter('is_active',1);
        foreach ($collection as $rule) {
            $result[] = ['label'=>$rule['name'], 'value'=>$rule['rule_id']];
        }
        return $result;
    }

    public function getRuleDataById($ruleId = null) {
        $result = [];
        if (isset($ruleId) && $ruleId !== null) {
            $catalogRuleInfo = $this->catalogRule->load($ruleId);
            $result = $catalogRuleInfo->getData();
        }
        return $result;
    }
}