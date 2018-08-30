<?php

/**
 * Webkul Software.
 *
 * @category  Webkul
 * @package   Webkul_Marketplace
 * @author    Webkul
 * @copyright Copyright (c) 2010-2017 Webkul Software Private Limited (https://webkul.com)
 * @license   https://store.webkul.com/license.html
 */

namespace Bakeway\OrderstatusEmail\Helper;

use Magento\Customer\Model\Session;
use Magento\Framework\Exception\MailException;

/**
 * Webkul Marketplace Helper Email.
 */
class Email extends \Magento\Framework\App\Helper\AbstractHelper {

    const XML_PATH_EMAIL_ORDER_STATUS_ACCPET = 'order/status/order_accpet_template';
    const XML_PATH_EMAIL_ORDER_STATUS_REJECT = 'order/status/order_reject_template';
    const XML_PATH_EMAIL_ORDER_STATUS_OUT_FOR_DELIVERY = 'order/status/out_for_delivery_email_template';
    const XML_PATH_EMAIL_ORDER_STATUS_READY_FOR_PICKUP = 'order/status/ready_for_pickup_email_template';
    const XML_PATH_EMAIL_ORDER_STATUS_COMPLETE_EMAIL = 'order/status/complete_email_template';
    const XML_PATH_EMAIL_ORDER_NOTIFICATION_TO_SELLER_EMAIL = 'order/status/notification_seller_email_template';
    const XML_PATH_EMAIL_URL_EDIT_ALERT_EMAIL = 'url/alert/url_changes_email_alert_template';
    /**
     * @var ObjectManagerInterface
     */
    protected $_objectManager;

    /**
     * @var \Magento\Framework\Translate\Inline\StateInterface
     */
    protected $_inlineTranslation;

    /**
     * @var \Magento\Framework\Mail\Template\TransportBuilder
     */
    protected $_transportBuilder;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $_customerSession;
    protected $_template;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;
    protected $_messageManager;

    /**
     * @param Magento\Framework\App\Helper\Context              $context
     * @param Magento\Framework\ObjectManagerInterface          $objectManager
     * @param Magento\Framework\Translate\Inline\StateInterface $inlineTranslation
     * @param Magento\Framework\Mail\Template\TransportBuilder  $transportBuilder
     * @param Magento\Store\Model\StoreManagerInterface         $storeManager
     * @param Session                                           $customerSession
     */
    public function __construct(
    \Magento\Framework\App\Helper\Context $context, \Magento\Framework\ObjectManagerInterface $objectManager, \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation, \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder, \Magento\Framework\Message\ManagerInterface $messageManager, \Magento\Store\Model\StoreManagerInterface $storeManager, Session $customerSession
    ) {
        $this->_objectManager = $objectManager;
        parent::__construct($context);
        $this->_inlineTranslation = $inlineTranslation;
        $this->_transportBuilder = $transportBuilder;
        $this->_customerSession = $customerSession;
        $this->_storeManager = $storeManager;
        $this->_messageManager = $messageManager;
    }

    /**
     * Return store configuration value.
     *
     * @param string $path
     * @param int    $storeId
     *
     * @return mixed
     */
    protected function getConfigValue($path, $storeId) {
        return $this->scopeConfig->getValue(
                        $path, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storeId
        );
    }

    /**
     * Return store.
     *
     * @return Store
     */
    public function getStore() {
        return $this->_storeManager->getStore();
    }

    /**
     * Return template id.
     *
     * @return mixed
     */
    public function getTemplateId($xmlPath) {
        return $this->getConfigValue($xmlPath, $this->getStore()->getStoreId());
    }

    /**
     * [generateTemplate description].
     *
     * @param Mixed $emailTemplateVariables
     * @param Mixed $senderInfo
     * @param Mixed $receiverInfo
     */
    public function generateTemplate($emailTemplateVariables, $senderInfo, $receiverInfo) {
        $template = $this->_transportBuilder->setTemplateIdentifier($this->_template)
                ->setTemplateOptions(
                        [
                            'area' => \Magento\Framework\App\Area::AREA_FRONTEND,
                            'store' => $this->_storeManager->getStore()->getId(),
                        ]
                )
                ->setTemplateVars($emailTemplateVariables)
                ->setFrom($senderInfo)
                ->addTo($receiverInfo['email'], $receiverInfo['name']);

        return $this;
    }

    /**
     * [sendPlacedOrderEmail description].
     *
     * @param Mixed $emailTemplateVariables
     * @param Mixed $senderInfo
     * @param Mixed $receiverInfo
     */
    public function sendOrderStatusEmailToUser($emailTemplateVariables, $senderInfo, $receiverInfo) {
        $this->_template = $this->getTemplateId(self::XML_PATH_EMAIL_ORDER_STATUS_ACCPET);
        $this->_inlineTranslation->suspend();
        $this->generateTemplate($emailTemplateVariables, $senderInfo, $receiverInfo);
        try {
            $transport = $this->_transportBuilder->getTransport();
            $transport->sendMessage();
        } catch (\Exception $e) {
            $this->_messageManager->addError($e->getMessage());
        }
        $this->_inlineTranslation->resume();
    }

    /**
     * [sendPlacedOrderEmail description].
     *
     * @param Mixed $emailTemplateVariables
     * @param Mixed $senderInfo
     * @param Mixed $receiverInfo
     */
    public function sendOrderRejectStatusEmailToUser($emailTemplateVariables, $senderInfo, $receiverInfo) {
        $this->_template = $this->getTemplateId(self::XML_PATH_EMAIL_ORDER_STATUS_REJECT);
        $this->_inlineTranslation->suspend();
        $this->generateTemplate($emailTemplateVariables, $senderInfo, $receiverInfo);
        try {
            $transport = $this->_transportBuilder->getTransport();
            $transport->sendMessage();
        } catch (\Exception $e) {
            $this->_messageManager->addError($e->getMessage());
        }
        $this->_inlineTranslation->resume();
    }

    /**
     * [sendPlacedOrderEmail description].
     *
     * @param Mixed $emailTemplateVariables
     * @param Mixed $senderInfo
     * @param Mixed $receiverInfo
     */
    public function sendOrderReadyforpickupStatusEmailToUser($emailTemplateVariables, $senderInfo, $receiverInfo) {
        $this->_template = $this->getTemplateId(self::XML_PATH_EMAIL_ORDER_STATUS_READY_FOR_PICKUP);
        $this->_inlineTranslation->suspend();
        $this->generateTemplate($emailTemplateVariables, $senderInfo, $receiverInfo);
        try {
            $transport = $this->_transportBuilder->getTransport();
            $transport->sendMessage();
        } catch (\Exception $e) {
            $this->_messageManager->addError($e->getMessage());
        }
        $this->_inlineTranslation->resume();
    }

    /**
     * [sendPlacedOrderEmail description].
     *
     * @param Mixed $emailTemplateVariables
     * @param Mixed $senderInfo
     * @param Mixed $receiverInfo
     */
    public function sendOrderOutForDeliveryStatusEmailToUser($emailTemplateVariables, $senderInfo, $receiverInfo) {
        $this->_template = $this->getTemplateId(self::XML_PATH_EMAIL_ORDER_STATUS_OUT_FOR_DELIVERY);
        $this->_inlineTranslation->suspend();
        $this->generateTemplate($emailTemplateVariables, $senderInfo, $receiverInfo);
        try {
            $transport = $this->_transportBuilder->getTransport();
            $transport->sendMessage();
        } catch (\Exception $e) {
            $this->_messageManager->addError($e->getMessage());
        }
        $this->_inlineTranslation->resume();
    }

    /**
     * [sendPlacedOrderEmail description].
     *
     * @param Mixed $emailTemplateVariables
     * @param Mixed $senderInfo
     * @param Mixed $receiverInfo
     */
    public function sendOrderCompleteStatusEmailToUser($emailTemplateVariables, $senderInfo, $receiverInfo) {
        $this->_template = $this->getTemplateId(self::XML_PATH_EMAIL_ORDER_STATUS_COMPLETE_EMAIL);
        $this->_inlineTranslation->suspend();
        $this->generateTemplate($emailTemplateVariables, $senderInfo, $receiverInfo);
        try {
            $transport = $this->_transportBuilder->getTransport();
            $transport->sendMessage();
        } catch (\Exception $e) {
            $this->_messageManager->addError($e->getMessage());
        }
        $this->_inlineTranslation->resume();
    }

    /**
     * [sendPlacedOrderEmail description].
     *
     * @param Mixed $emailTemplateVariables
     * @param Mixed $senderInfo
     * @param Mixed $receiverInfo
     */
    public function sendOrderNotificationEmailToSeller($emailTemplateVariables, $senderInfo, $receiverInfo) {
        $this->_template = $this->getTemplateId(self::XML_PATH_EMAIL_ORDER_NOTIFICATION_TO_SELLER_EMAIL);

        $this->_inlineTranslation->suspend();
        $this->generateTemplate($emailTemplateVariables, $senderInfo, $receiverInfo);
        try {
            $transport = $this->_transportBuilder->getTransport();
            $transport->sendMessage();
        } catch (\Exception $e) {
            $this->_messageManager->addError($e->getMessage());
        }
        $this->_inlineTranslation->resume();
    }

    /**
     * [sendPlacedOrderEmail description].
     *
     * @param Mixed $emailTemplateVariables
     * @param Mixed $senderInfo
     * @param Mixed $receiverInfo
     */
    public function sendEailEditAlertEmailtoSalesteam($emailTemplateVariables, $senderInfo, $receiverInfo) {
        $this->_template = $this->getTemplateId(self::XML_PATH_EMAIL_URL_EDIT_ALERT_EMAIL);

        $this->_inlineTranslation->suspend();
        $this->generateTemplate($emailTemplateVariables, $senderInfo, $receiverInfo);
        try {
            $transport = $this->_transportBuilder->getTransport();
            $transport->sendMessage();
        } catch (\Exception $e) {
            $this->_messageManager->addError($e->getMessage());
        }
        $this->_inlineTranslation->resume();
    }



}
