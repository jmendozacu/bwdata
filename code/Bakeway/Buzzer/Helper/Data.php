<?php
/**
 * Copyright © 2015 Bakeway . All rights reserved.
 */
namespace Bakeway\Buzzer\Helper;
class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
	CONST STATUS_FIELD = 'buzzer/seller_bell/status';
	CONST TIME_FIELD = 'buzzer/seller_bell/timing';

	/**
     * @param \Magento\Framework\App\Helper\Context $context
     */
	public function __construct(\Magento\Framework\App\Helper\Context $context
	) {
		parent::__construct($context);
	}

	/**
	 * return alarm status
	 * @return mixed
	 */
	public function getAlarmStatus() {
		return $this->scopeConfig->getValue(
			self::STATUS_FIELD, \Magento\Store\Model\ScopeInterface::SCOPE_STORE
		);
	}

	/**
	 * return time
	 * @return mixed
	 */
	public function getAlarmTime(){
		return $this->scopeConfig->getValue(
			self::TIME_FIELD, \Magento\Store\Model\ScopeInterface::SCOPE_STORE
		);
	}

}