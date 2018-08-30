<?php

/**
 * Copyright © 2015 Bakeway . All rights reserved.
 */

namespace Bakeway\Buzzer\Block\Buzzer;

class Index extends \Magento\Framework\View\Element\Template {
    /*
     * \Bakeway\CommissionLog\Model\CommissionLogFactory
     */

    protected $commissionLogCollection;

    /*
     * \Magento\Customer\Model\Session
     */
    protected $customerSesssion;

    /*
     * \Magento\Framework\ObjectManagerInterface $objectManager
     */
    protected $objectManager;

    /*
     * \Webkul\Marketplace\Model\Orders
     */
    protected $ordersFactory;

    /*
   * \Bakeway\Buzzer\Helper\Data $buzzerHelper
   */
    protected $buzzerHelper;

    /*
     * \Webkul\Marketplace\Helper\Data $marketPlaceHelper
     */
    protected $marketPlaceHelper;

    /**
     * Index constructor.
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Customer\Model\Session $customerSesssion
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param \Webkul\Marketplace\Model\OrdersFactory $ordersFactory
     * @param \Bakeway\Buzzer\Helper\Data $buzzerHelper
     * @param \Webkul\Marketplace\Helper\Data $marketPlaceHelper
     */
    public function __construct(
    \Magento\Framework\View\Element\Template\Context $context,
    \Magento\Customer\Model\Session $customerSesssion,
    \Magento\Framework\ObjectManagerInterface $objectManager,
    \Webkul\Marketplace\Model\OrdersFactory $ordersFactory,
    \Bakeway\Buzzer\Helper\Data $buzzerHelper,
    \Webkul\Marketplace\Helper\Data $marketPlaceHelper,
    array $data = []
    ) {
        $this->_customerSession = $customerSesssion;
        $this->_objectManager = $objectManager;
        $this->ordersFactory = $ordersFactory;
        $this->buzzerHelper = $buzzerHelper;
        $this->marketPlaceHelper = $marketPlaceHelper;
        parent::__construct($context, $data);
    }


    /**
     * @return vednor details
     */
    public function getVendorData() {
        $model = $this->_objectManager->create(
                                'Webkul\Marketplace\Model\Seller'
                        )
                        ->getCollection()
                        ->addFieldToSelect('seller_id')
                        ->addFieldToFilter(
                                'seller_id', $this->_customerSession->getCustomerId()
                        )->getLastItem();
        $sellerId = $model->getData('seller_id');
        $marketPlaceOrder = $this->_objectManager->create(
             'Webkul\Marketplace\Model\Orders')
             ->getCollection()
             ->addFieldToSelect('order_id')
             ->addFieldToFilter('seller_id',$sellerId)
             ->addFieldToFilter('status','pending');
        $salesorderTable =  $marketPlaceOrder->getConnection()->getTableName('sales_order');
        $marketPlaceOrder->getSelect()->join( array('ot'=>$salesorderTable),  'main_table.order_id = ot.entity_id', array('ot.entity_id'));
        $count = $marketPlaceOrder->count();
        $marketPlaceOrderId = [];
        if($count > 0)
        {

            foreach($marketPlaceOrder as $marketPlaceOrderData)
            {

                $marketPlaceOrderId[] = $marketPlaceOrderData['entity_id'];
            }
        }
        return $marketPlaceOrderId;
        exit;

    }

    public function getCustomerId() {
        return $this->_customerSession->getCustomerId();
    }

    /**
     * return alarm status
     * @return mixed
     */
    public function getAlarmStatusVal() {
        return $this->buzzerHelper->getAlarmStatus();
    }

    /**
     * return time
     * @return mixed
     */
    public function getAlarmTimeVal(){
        return $this->buzzerHelper->getAlarmTime();
    }

    /**
     * return seller alram status
     */

    public function getSellerAlaramStatus()
    {
        $sellerAlarmDetail = $this->marketPlaceHelper->getSellerEntityId($this->getCustomerId());
        $sellerAlarmVal = $sellerAlarmDetail['order_alarm'];
        if(!empty($sellerAlarmVal))
        {
            return $sellerAlarmVal;
        }else{
            return;
        }

    }

}
