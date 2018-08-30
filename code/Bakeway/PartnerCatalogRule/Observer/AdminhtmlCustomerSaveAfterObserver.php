<?php
/**
 * Bakeway
 *
 * @category  Bakeway
 * @package   Bakeway_PartnerCatalogRule
 * @author    Bakeway
 */

namespace Bakeway\PartnerCatalogRule\Observer;

use Magento\Framework\Event\ObserverInterface;
use Bakeway\PartnerCatalogRule\Model\ResourceModel\PartnerCatalogRule\CollectionFactory as PartnerRuleCollection;
use Magento\Framework\Filesystem;

/**
 * Bakeway PartnerCatalogRule AdminhtmlCustomerSaveAfterObserver Observer.
 */
class AdminhtmlCustomerSaveAfterObserver implements ObserverInterface
{

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var PartnerRuleCollection
     */
    protected $partnerRuleCollection;

    /**
     * AdminhtmlCustomerSaveAfterObserver constructor.
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param PartnerRuleCollection $partnerRuleCollection
     */
    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager,
        PartnerRuleCollection $partnerRuleCollection
    )
    {
        $this->objectManager = $objectManager;
        $this->partnerRuleCollection = $partnerRuleCollection;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $postData = $observer->getRequest()->getPostValue();
        $customer = $observer->getCustomer();
        $sellerId = $customer->getId();

        if (isset($postData['bakeway_partner_catalog_rule_id'])) {
            $ruleCollection = $this->partnerRuleCollection->create()
                ->addFieldToFilter('seller_id', $sellerId);
            $ruleId = $postData['bakeway_partner_catalog_rule_id'];
            if ($ruleCollection->count() > 0) {
                foreach ($ruleCollection as $partnerCatalogRule) {
                    $partnerCatalogRule->setData('rule_id', $ruleId);
                    $partnerCatalogRule->save();
                }
            } else {
                $partnerCatalogRule = $this->objectManager
                                ->create('Bakeway\PartnerCatalogRule\Model\PartnerCatalogRule');
                $partnerCatalogRule->setData('seller_id', $sellerId);
                $partnerCatalogRule->setData('rule_id', $ruleId);
                $partnerCatalogRule->save();
            }
        }
    }
}