<?php
namespace Bakeway\PartnerCatalogRule\Model\Rule\Action\Discount;

class ByPercent extends \Magento\SalesRule\Model\Rule\Action\Discount\ByPercent
{
    /**
     * @param \Magento\SalesRule\Model\Rule $rule
     * @param \Magento\Quote\Model\Quote\Item\AbstractItem $item
     * @param float $qty
     * @param float $rulePercent
     * @return Data
     */
    protected function _calculate($rule, $item, $qty, $rulePercent)
    {
        $totalDiscountAmount = 0;
        $quote = $item->getQuote();
        foreach ($quote->getAllItems() as $innerItem) {
            $totalDiscountAmount = $totalDiscountAmount + $innerItem->getDiscountAmount();
        }
        /** @var \Magento\SalesRule\Model\Rule\Action\Discount\Data $discountData */
        $discountData = $this->discountFactory->create();
        $maxDiscountAmount = $rule->getData('max_discount_amount');
        if ($totalDiscountAmount >= $maxDiscountAmount) {
            return $discountData;
        }

        $itemPrice = $this->validator->getItemPrice($item);
        $baseItemPrice = $this->validator->getItemBasePrice($item);
        $itemOriginalPrice = $this->validator->getItemOriginalPrice($item);
        $baseItemOriginalPrice = $this->validator->getItemBaseOriginalPrice($item);

        $_rulePct = $rulePercent / 100;
        $amount = ($qty * $itemPrice - $item->getDiscountAmount()) * $_rulePct;
        $baseAmount = ($qty * $baseItemPrice - $item->getBaseDiscountAmount()) * $_rulePct;
        $originalAmount = ($qty * $itemOriginalPrice - $item->getDiscountAmount()) * $_rulePct;
        $baseOriginalAmount = ($qty * $baseItemOriginalPrice - $item->getBaseDiscountAmount()) * $_rulePct;

        if ($amount > $maxDiscountAmount) {
            $amount = $maxDiscountAmount;
        }
        if ($baseAmount > $maxDiscountAmount) {
            $baseAmount = $maxDiscountAmount;
        }
        if ($originalAmount > $maxDiscountAmount) {
            $originalAmount = $maxDiscountAmount;
        }
        if ($baseOriginalAmount > $maxDiscountAmount) {
            $baseOriginalAmount = $maxDiscountAmount;
        }

        $discountData->setAmount($amount);
        $discountData->setBaseAmount($baseAmount);
        $discountData->setOriginalAmount($originalAmount);
        $discountData->setBaseOriginalAmount($baseOriginalAmount);

        if (!$rule->getDiscountQty() || $rule->getDiscountQty() > $qty) {
            $discountPercent = min(100, $item->getDiscountPercent() + $rulePercent);
            $item->setDiscountPercent($discountPercent);
        }

        return $discountData;
    }
}