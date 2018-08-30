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
use Magento\CatalogRule\Model\Rule as CatalogRule;
use Symfony\Component\Config\Definition\Exception\Exception;

/**
 * Bakeway PartnerCatalogRule CatalogRuleSaveAfter Observer.
 */
class CatalogRuleSaveAfter implements ObserverInterface
{

    /**
     * @var PartnerRuleCollection
     */
    protected $partnerRuleCollection;

    /**
     * @var Catalogrule
     */
    protected $catalogrule;

    /**
     * @var \Magento\Framework\App\RequestInterface $request
     */
    protected $request;

    /**
     * CatalogRuleSaveAfter constructor.
     * @param PartnerRuleCollection $partnerRuleCollection
     * @param CatalogRule $catalogRule
     * @param \Magento\Framework\App\RequestInterface $request
     */
    public function __construct(
        PartnerRuleCollection $partnerRuleCollection,
        CatalogRule $catalogRule,
        \Magento\Framework\App\RequestInterface $request
    )
    {
        $this->partnerRuleCollection = $partnerRuleCollection;
        $this->catalogrule = $catalogRule;
        $this->request = $request;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $ruleEntity = $observer->getEntity();
        $preOrderOfferStartDate = $this->request->getParam('fixed_discount_start_date');
        if (isset($preOrderOfferStartDate) 
                && !empty($preOrderOfferStartDate)) {
            $preOrderOfferStartDate = date("Y-m-d", strtotime($preOrderOfferStartDate));
        } else {
            $preOrderOfferStartDate = NULL;
        }
        
        $preOrderOfferEndDate = $this->request->getParam('fixed_discount_end_date');
        if(isset($preOrderOfferEndDate) 
                && !empty($preOrderOfferEndDate)) {
            $preOrderOfferEndDate = date("Y-m-d", strtotime($preOrderOfferEndDate));
        } else {
            $preOrderOfferEndDate = NULL;
        }
        
        $ruleId = $ruleEntity->getData('rule_id');
        $catalogRuleObj = $this->catalogrule;
        if (isset($ruleId)) {
            $catalogRuleInfo = $catalogRuleObj->load($ruleId);
            $existingPreOrderOfferStartDate = $catalogRuleInfo->getData('fixed_discount_start_date');
            $existingPreOrderOfferEndDate = $catalogRuleInfo->getData('fixed_discount_end_date');
            $catalogRuleInfo->setData('fixed_discount_start_date', $preOrderOfferStartDate);
            $catalogRuleInfo->setData('fixed_discount_end_date', $preOrderOfferEndDate);
            try{
                if (($existingPreOrderOfferStartDate != $preOrderOfferStartDate) ||
                    ($existingPreOrderOfferEndDate != $preOrderOfferEndDate)) {
                    $catalogRuleInfo->save();
                }
            }catch (Exception $e){
                echo $e->getMessage();
            }
        }
    }
}