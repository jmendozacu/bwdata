<?php
/**
 * Copyright © 2015 Bakeway . All rights reserved.
 */

namespace Bakeway\Logs\Helper;

use Magento\Customer\Model\Session as  CustomerSession;
use Magento\Framework\ObjectManagerInterface as ObjectManager;
use Bakeway\HomeDeliveryshipping\Model\Freeshipping as Freeshiiping;
use Symfony\Component\Config\Definition\Exception\Exception;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
	/**
	 * @var CustomerSession
	 */
	protected $customerSession;
	/**
	 * @var ObjectManager
	 */
	protected $objectManager;
	/**
	 * @var Freeshiiping
	 */
	protected $freeshiiping;


	/**
	 * Data constructor.
	 * @param \Magento\Framework\App\Helper\Context $context
	 * @param CustomerSession $customerSession
	 * @param ObjectManager $objectManager
	 * @param Freeshiiping $freeshiiping
	 * @param Data $logsHelper
	 */
	public function __construct(
		\Magento\Framework\App\Helper\Context $context,
		CustomerSession $customerSession,
		ObjectManager $objectManager,
		Freeshiiping $freeshiiping

	) {
		parent::__construct($context);
		$this->customerSession = $customerSession;
		$this->objectManager = $objectManager;
		$this->freeshiiping = $freeshiiping;
	}

	/**
	 * @param $sellerId
	 * @param $maxPrice
	 * @param null $adminUserEmail
	 */
	public function getFreeshippingLogParam($sellerId,$maxPrice , $adminUserEmail = null)
	{
		 $date = date('Y-m-d H:i:s');
		 $loggedCustomerName = $this->customerSession->getCustomer()->getName();
		 $freeShippingLogModel = $this->freeshiiping;
		 $freeShippingLogModel->setSellerId($sellerId);
		 $freeShippingLogModel->setMaxFreeShippingPrice($maxPrice);
		 $freeShippingLogModel->setCreatedAt($date);

		if(isset($adminUserEmail)){
			$freeShippingLogModel->setCreatedBy($adminUserEmail);
		}else{
			$freeShippingLogModel->setCreatedBy($loggedCustomerName);
		}

		try{
			$freeShippingLogModel->save();
		}catch (Exception $e){
			echo $e->getMessage();
		}



	}

	/**
	 * @param $sellerId
	 * @return mixed
	 */
	public function getFreeShippingLogsCollection($sellerId)
	{
		$collection = $this->freeshiiping->getCollection()
			       ->addFieldToFilter('seller_id',array('eq'=>$sellerId))
			       ->setOrder('created_at','DESC');

		return $collection;

	}


}