<?php
/**
 * Copyright © 2015 Bakeway . All rights reserved.
 */
namespace Bakeway\Partnerlocations\Helper;
use Bakeway\Partnerlocations\Model\ResourceModel\Partnerlocations\Collection as LocationsCollection;
use Bakeway\SubLocations\Model\ResourceModel\SubLocations\Collection as SublocationsCollection;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{

	/*
	 * @var LocationsCollection
	 */
	protected $locationsCollection;

	/*
	 * @var SublocationsCollection
	 */

	protected $sublocationsCollection;
	/**
     * @param \Magento\Framework\App\Helper\Context $context
     */
	public function __construct(\Magento\Framework\App\Helper\Context $context,
								LocationsCollection $locationsCollection,
								SublocationsCollection $sublocationsCollection

	) {
		parent::__construct($context);
		$this->locationsCollection = $locationsCollection;
		$this->sublocationsCollection = $sublocationsCollection;
	}



	/*
	*return store address count
	*@var seller_id
	* @return booelan
	*/
	public function getSellerStoreAddressCount($sellerid)
	{
		$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
		$collection = $objectManager->create('\Bakeway\Partnerlocations\Model\Partnerlocations')->getCollection()
			->addFieldToFilter('seller_id',$sellerid)
			->count();
        if(!empty($collection)):
		   return $collection;
		else:
			return;
		endif;
	}

	/*
	 *return seller_id
	 *@var $id
	 * @return booelan
     */
	public function getSellerId($id)
	{
		$collection = $this->locationsCollection
		              ->addFieldToFilter('id',$id)
			          ->addFieldToSelect('seller_id')
		              ->getLastItem();
        return $collection->getData('seller_id');
	}


   /*
    * return store locality area
    * @var $sellerid
    * @return int
    */

	public function getSingleLocality($sellerid)
	{
		$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
		$collectionLocation = $objectManager->create('\Bakeway\Partnerlocations\Model\ResourceModel\Partnerlocations\Collection');
		$collection = $collectionLocation
			->addFieldToFilter('seller_id',$sellerid)
			->addFieldToSelect('store_locality_area')
			->addFieldToFilter('is_active',1)
			->getLastItem();
		return $collection->getData('store_locality_area');
	}

 /*
  * Return if dupliate store name
  * @var $postUniqueName
  * @var $seller_id
  */
   public function checkStoreUniqueName($postUniqueName,$seller_id)
   {
	   $totCount= "";
	   $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
	   $collectionLoca = $objectManager->create('\Bakeway\Partnerlocations\Model\ResourceModel\Partnerlocations\Collection');
	   $collection =  $collectionLoca
		   ->addFieldToFilter('seller_id',$seller_id)
		   ->addFieldToFilter("store_locality_area",array("eq"=>$postUniqueName))
		   ->addFieldToSelect(array('store_locality_area','store_unique_name'));

	   $postUniqueName = str_replace(" ","-",$postUniqueName);
       $postUniqueName  =  preg_replace('#[^0-9a-z]+#i', '-', strtolower($postUniqueName));
	   $postUniqueNameGlobal  =  preg_replace('#[^0-9a-z]+#i', '-', strtolower($postUniqueName));
	   $collectionPartnerLocation  = count($collection);
	   $totCount = $this->checkStorecount($seller_id,$postUniqueName);
	   //echo $totCount;die;
	   $counter = $totCount;
	   if($totCount == 1){
		     $counter = 0;
		     $num = ++$counter;// Increment $usercnt by 1
		     $postUniqueName = $postUniqueName . "-" . $num;  // Add number to username
		     $duplicateStorecheck =  $this->checkDuplicateStore($seller_id,$postUniqueName);
			   if(!empty($duplicateStorecheck)){
				   $num = ++$num;// Increment $usercnt by 1
				   $postUniqueName = $postUniqueNameGlobal . "-" . $num;  // Add number to username
			   }
		/*   $checkSingleStore = $this->checkSingleStoreduplicate($seller_id,$postUniqueNameGlobal);
		   if(!empty($checkSingleStore)){
			   $postUniqueName =  $postUniqueNameGlobal;
		   }*/
	   } else {

		   if ($totCount > 1) {
			  // $counter = $counter-1;
			   $num = ++$counter;// Increment $usercnt by 1
			   $postUniqueName = $postUniqueName . "-" . $num;  // Add number to username
			   $duplicateStorecheck =  $this->checkDuplicateStore($seller_id,$postUniqueName);
			   if(!empty($duplicateStorecheck)){
				   $num = ++$num;// Increment $usercnt by 1
				   $postUniqueName = $postUniqueNameGlobal . "-" . $num;  // Add number to username
			   }

			/* $checkSingleStore = $this->checkSingleStoreduplicate($seller_id,$postUniqueNameGlobal);
			   if(!empty($checkSingleStore)){
				   $postUniqueName =  $postUniqueNameGlobal;
			   }*/
		   }
	   }

	   $postUniqueName = str_replace(" ","-",$postUniqueName);


	   $postUniqueName  =  preg_replace('#[^0-9a-z]+#i', '-', strtolower($postUniqueName));

	   return $postUniqueName;
   }

	/**
	 * @param $storeName
	 */

	public function checkSingleStoreduplicate($sellerid , $postUniqueName){

		$storeCollection =   $this->locationsCollection
			->addFieldToFilter('seller_id',$sellerid)
			->addFieldToFilter("store_unique_name",array("eq"=>$postUniqueName))
			->getFirstItem();
		$storeName = $storeCollection['store_unique_name'];

	}
	/**
	 * @param $sellerId
	 * @param $postUniqueName
	 */
	public function checkStorecount($sellerId , $postUniqueName)
	{

		$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
		$collectionLocation = $objectManager->create('\Bakeway\Partnerlocations\Model\ResourceModel\Partnerlocations\Collection');
		$collection =  $collectionLocation
			->addFieldToFilter('seller_id',$sellerId)
			->addFieldToFilter("store_unique_name",array("like"=>$postUniqueName."%"));
		$total = count($collection);
		return $total;

	}

	/**
	 * @param $seller_id
	 * @param $postUniqueName
	 * @return mixed
	 */
	public function checkDuplicateStore($seller_id,$postUniqueName)
	{
        $storeCollection =   $this->locationsCollection
		                  ->addFieldToFilter('seller_id',$seller_id)
		                  ->addFieldToFilter("store_unique_name",array("eq"=>$postUniqueName))
		                  ->getFirstItem();
		return $storeCollection['store_unique_name'];
	}
	/*
   * return store locality area
   * @var $sellerid
   * @return int
   */

	public function getSinglelocalitycity($sellerid)
	{
		$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
		$collectionLocation = $objectManager->create('\Bakeway\Partnerlocations\Model\ResourceModel\Partnerlocations\Collection');
		$collection = $collectionLocation
			->addFieldToFilter('seller_id',$sellerid)
			->addFieldToSelect('city_id')
			->addFieldToFilter('is_active',1)
			->getLastItem();

		return $collection->getData('city_id');
	}

	/*
	 * @return all sub_location_id
	 */
	public function getSubLocOptions()
	{
		$suburbCollection = $this->sublocationsCollection;
		$options = [];

		foreach ($suburbCollection as $suburb) {

			$options[$suburb->getId()] = $suburb->getAreaName();
		}

		return $options;
	}

	/*
	 * @param int $cityId
	 * return suburb list for form
	 */
	public function getFormSuburblist($cityId)
	{
		$suburbCollection = $this->sublocationsCollection
			                ->addFieldToFilter('city_id',$cityId);
		$options = [];
		$options[] = ['label' => __('---Please Select---'), 'value' => ''];

		foreach ($suburbCollection as $suburb) {
			$options[] = ['label' => $suburb->getAreaName(), 'value' => $suburb->getId()];
		}
		return $options;

	}


	/**
	 * @return mixed
	 */
	public function getUrlUpdateALertTriggerStatus(){
		return $this->scopeConfig->getValue(
			"url/alert/status", \Magento\Store\Model\ScopeInterface::SCOPE_STORE
		);
	}

	/**
	 * @return mixed
	 */
	public function getUrlUpdateALertTriggerReceivers(){
		return $this->scopeConfig->getValue(
			"url/alert/receiver_email", \Magento\Store\Model\ScopeInterface::SCOPE_STORE
		);
	}

}