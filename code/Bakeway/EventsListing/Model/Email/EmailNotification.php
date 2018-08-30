<?php

/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Bakeway\EventsListing\Model\Email;

use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Customer\Model\CustomerRegistry;
use Magento\Framework\Reflection\DataObjectProcessor;
use Magento\Customer\Helper\View as CustomerViewHelper;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Customer\Model\EmailNotification as CoreEmailNotification;

class EmailNotification extends CoreEmailNotification{

    /**
     * @var CustomerRegistry
     */
    private $customerRegistry;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var DataObjectProcessor
     */
    protected $dataProcessor;

    /**
     * @var CustomerViewHelper
     */
    protected $customerViewHelper;

    /**
     * @var TransportBuilder
     */
    private $transportBuilder;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    public function __construct(
        CustomerRegistry $customerRegistry,
        StoreManagerInterface $storeManager,
        DataObjectProcessor $dataProcessor,
        CustomerViewHelper $customerViewHelper,
        TransportBuilder $transportBuilder,
        ScopeConfigInterface $scopeConfig
    ) {

        $this->storeManager = $storeManager;
        $this->customerRegistry = $customerRegistry;
        $this->dataProcessor = $dataProcessor;
        $this->transportBuilder = $transportBuilder;
        $this->scopeConfig = $scopeConfig;
        $this->customerViewHelper = $customerViewHelper;
        parent::__construct(
            $customerRegistry,
            $storeManager,
            $transportBuilder,
            $customerViewHelper,
            $dataProcessor,
            $scopeConfig
        );
    }

    public function passwordResetConfirmation(CustomerInterface $customer
    ) {
        $storeId = $this->storeManager->getStore()->getId();
        if (!$storeId) {
            $storeId = $this->getWebsiteStoreId($customer);
        }

        $customerEmailData = $this->getFullCustomerObject($customer);

        /* custom code start to check customer is seller or not */
        $sellerStatus = 0;
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $model = $objectManager->create(
                        'Webkul\Marketplace\Model\Seller'
                )
                ->getCollection()
                ->addFieldToFilter(
                'seller_id', $customerEmailData->getId()
        );
        foreach ($model as $value) {
            $sellerStatus = $value->getIsSeller();
        }

        if (!empty($sellerStatus)) {
            $sellerStatus = $sellerStatus;
        } else {
            $sellerStatus = "";
        }

        /* custom code end */

        $this->sendEmailTemplate(
                $customer, \Magento\Customer\Model\EmailNotification::XML_PATH_FORGOT_EMAIL_TEMPLATE, \Magento\Customer\Model\EmailNotification::XML_PATH_FORGOT_EMAIL_IDENTITY, ['customer' => $customerEmailData, 'store' => $this->storeManager->getStore($storeId),
            'sellerstatus' => $sellerStatus], $storeId
        );
    }

    /**
     * Create an object with data merged from Customer and CustomerSecure
     *
     * @param CustomerInterface $customer
     * @return \Magento\Customer\Model\Data\CustomerSecure
     */
    private function getFullCustomerObject($customer) {
        // No need to flatten the custom attributes or nested objects since the only usage is for email templates and
        // object passed for events
        $mergedCustomerData = $this->customerRegistry->retrieveSecureData($customer->getId());
        $customerData = $this->dataProcessor
                ->buildOutputDataArray($customer, \Magento\Customer\Api\Data\CustomerInterface::class);
        $mergedCustomerData->addData($customerData);
        $mergedCustomerData->setData('name', $this->customerViewHelper->getCustomerName($customer));
        return $mergedCustomerData;
    }

    /**
     * Get either first store ID from a set website or the provided as default
     *
     * @param CustomerInterface $customer
     * @param int|string|null $defaultStoreId
     * @return int
     */
    private function getWebsiteStoreId($customer, $defaultStoreId = null) {
        if ($customer->getWebsiteId() != 0 && empty($defaultStoreId)) {
            $storeIds = $this->storeManager->getWebsite($customer->getWebsiteId())->getStoreIds();
            reset($storeIds);
            $defaultStoreId = current($storeIds);
        }
        return $defaultStoreId;
    }

    /**
     * Send corresponding email template
     *
     * @param CustomerInterface $customer
     * @param string $template configuration path of email template
     * @param string $sender configuration path of email identity
     * @param array $templateParams
     * @param int|null $storeId
     * @param string $email
     * @return void
     */
    private function sendEmailTemplate(
    $customer, $template, $sender, $templateParams = [], $storeId = null, $email = null
    ) {
        $templateId = $this->scopeConfig->getValue($template, 'store', $storeId);
        if ($email === null) {
            $email = $customer->getEmail();
        }

        $transport = $this->transportBuilder->setTemplateIdentifier($templateId)
                ->setTemplateOptions(['area' => 'frontend', 'store' => $storeId])
                ->setTemplateVars($templateParams)
                ->setFrom($this->scopeConfig->getValue($sender, 'store', $storeId))
                ->addTo($email, $this->customerViewHelper->getCustomerName($customer))
                ->getTransport();

        $transport->sendMessage();
    }

        /**
     * Send notification to customer when email or/and password changed
     *
     * @param CustomerInterface $savedCustomer
     * @param string $origCustomerEmail
     * @param bool $isPasswordChanged
     * @return void
     */
    public function credentialsChanged(
        CustomerInterface $savedCustomer,
        $origCustomerEmail,
        $isPasswordChanged = false
    ) {
        if ($origCustomerEmail != $savedCustomer->getEmail()) {
            if ($isPasswordChanged) {
                $this->emailAndPasswordChanged($savedCustomer, $origCustomerEmail);
                $this->emailAndPasswordChanged($savedCustomer, $savedCustomer->getEmail());
                return;
            }

            $this->emailChanged($savedCustomer, $origCustomerEmail);
            $this->emailChanged($savedCustomer, $savedCustomer->getEmail());
            return;
        }

        if ($isPasswordChanged) {
            $this->passwordReset($savedCustomer);
        }
    }

    /**
     * Send email to customer when his email and password is changed
     *
     * @param CustomerInterface $customer
     * @param string $email
     * @return void
     */
    private function emailAndPasswordChanged(CustomerInterface $customer, $email)
    {
        $storeId = $customer->getStoreId();
        if (!$storeId) {
            $storeId = $this->getWebsiteStoreId($customer);
        }

        $customerEmailData = $this->getFullCustomerObject($customer);

        $this->sendEmailTemplate(
            $customer,
            self::XML_PATH_CHANGE_EMAIL_AND_PASSWORD_TEMPLATE,
            self::XML_PATH_FORGOT_EMAIL_IDENTITY,
            ['customer' => $customerEmailData, 'store' => $this->storeManager->getStore($storeId)],
            $storeId,
            $email
        );
    }

    /**
     * Send email to customer when his email is changed
     *
     * @param CustomerInterface $customer
     * @param string $email
     * @return void
     */
    private function emailChanged(CustomerInterface $customer, $email)
    {
        $storeId = $customer->getStoreId();
        if (!$storeId) {
            $storeId = $this->getWebsiteStoreId($customer);
        }

        $customerEmailData = $this->getFullCustomerObject($customer);

        $this->sendEmailTemplate(
            $customer,
            self::XML_PATH_CHANGE_EMAIL_TEMPLATE,
            self::XML_PATH_FORGOT_EMAIL_IDENTITY,
            ['customer' => $customerEmailData, 'store' => $this->storeManager->getStore($storeId)],
            $storeId,
            $email
        );
    }

    /**
     * Send email to customer when his password is reset
     *
     * @param CustomerInterface $customer
     * @return void
     */
    private function passwordReset(CustomerInterface $customer)
    {
        $storeId = $customer->getStoreId();
        if (!$storeId) {
            $storeId = $this->getWebsiteStoreId($customer);
        }

        $customerEmailData = $this->getFullCustomerObject($customer);

        $this->sendEmailTemplate(
            $customer,
            self::XML_PATH_RESET_PASSWORD_TEMPLATE,
            self::XML_PATH_FORGOT_EMAIL_IDENTITY,
            ['customer' => $customerEmailData, 'store' => $this->storeManager->getStore($storeId)],
            $storeId
        );
    }
    
}
