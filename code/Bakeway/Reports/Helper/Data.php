<?php
/**
 * Copyright © 2015 Bakeway . All rights reserved.
 */
namespace Bakeway\Reports\Helper;

use  Bakeway\OrderstatusEmail\Block\Order\Email\Items as Itemsblock;
class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
	/**
	 * @var Itemsblock
	 */
	protected $itemsblock;

	/**
     * @param \Magento\Framework\App\Helper\Context $context
     */
	public function __construct(\Magento\Framework\App\Helper\Context $context,
								Itemsblock $itemsblock
	) {
		$this->itemsblock = $itemsblock;
		parent::__construct($context);
	}

	/**
	 * @param $collection
	 * @return mixed
	 */
	public function getFilterCollection($collection)
	{
		return $collection;
	}

	/**
	 * @return mixed
	 */
	public function getFilterCollectionRow(){
        $collection = [];
		return $this->getFilterCollection($collection);
	}

	/**
	 * @param $productId
	 * @param $order
	 * @return mixed
	 */
	public function getBakeryDetails($productId, $order)
	{
		$addresss = $this->itemsblock->getSellerInfo($productId,$order);
		return $addresss;
	}
	
}