<?php


namespace Bakeway\ProductApi\Plugin;

class PriceCurrency {

    public function aroundRound(\Magento\Directory\Model\PriceCurrency $subject ,
        \Closure $proceed,
        $price)
    {

    $price = round($price, 0);
    $result = $proceed($price);
    return $result;
    }

}
?>
