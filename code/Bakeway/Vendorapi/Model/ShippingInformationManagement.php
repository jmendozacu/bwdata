<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace Bakeway\Vendorapi\Model;

use Magento\Framework\Exception\InputException;

/**
 * Description of ShippingInformationManagement
 *
 * @author Admin
 */
class ShippingInformationManagement
        extends \Magento\Checkout\Model\ShippingInformationManagement
{

    /**
     * Save Address Information
     * @param int $cartId
     * @param \Magento\Checkout\Api\Data\ShippingInformationInterface $addressInformation
     */
    public function saveAddressInformation($cartId,
            \Magento\Checkout\Api\Data\ShippingInformationInterface $addressInformation)
    {
        $email = $addressInformation->getBillingAddress()->getEmail();
        if (!$this->validateEmail($email)) {
            throw new InputException(__('Please enter valid email address.'));
        }
        parent::saveAddressInformation($cartId, $addressInformation);
    }

    /**
     * 
     * @param string $email
     * @return boolean
     */
    private function validateEmail($email)
    {
        $nemail = explode('@', $email);
        $domain = array_pop($nemail);
        if (checkdnsrr($domain, "MX")) {
            return true;
        } else {
            return false;
        }
    }

}
