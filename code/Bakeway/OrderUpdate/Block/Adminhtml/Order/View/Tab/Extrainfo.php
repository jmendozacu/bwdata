<?php
namespace Bakeway\OrderUpdate\Block\Adminhtml\Order\View\Tab;

use Bakeway\OrderstatusEmail\Block\Order\Email\Items as Emailitems;;

class Extrainfo extends \Magento\Backend\Block\Template implements
    \Magento\Backend\Block\Widget\Tab\TabInterface
{
    /**
     * Template
     *
     * @var string
     */
    protected $_template = 'tab/extrainformation.phtml';

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * @var \Magento\Sales\Helper\Admin
     */
    protected $adminHelper;

    /**
     * @var \Magento\Sales\Model\Order
     */
    protected $orderobj;

    /**
     * @var Emailitems
     */
    protected $emailitems;

    /**
     * Extrainfo constructor.
     * @param Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param array $data
     */
    public function  __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Sales\Helper\Admin $adminHelper,
        \Magento\Sales\Model\Order $orderobj,
        Emailitems $emailitems,
        array $data = [])
    {
        $this->registry = $registry;
        parent::__construct($context, $data);
        $this->adminHelper = $adminHelper;
        $this->orderobj = $orderobj;
        $this->emailitems = $emailitems;
    }



    /**
     * ######################## TAB settings #################################
     */

    /**
     * {@inheritdoc}
     */
    public function getTabLabel()
    {
        return __('Additional Information');
    }

    /**
     * {@inheritdoc}
     */
    public function getTabTitle()
    {
        return __('Additional Information');
    }

    /**
     * {@inheritdoc}
     */
    public function canShowTab()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function isHidden()
    {
        return false;
    }

    /**
     * Tab should be loaded trough Ajax call.
     *
     * @return bool
     */
    public function isAjaxLoaded()
    {
        return true;
    }


    /**
     * @return \Magento\Sales\Model\Order
     */
    public function getOrderData(){
        $orderId = $this->getRequest()->getParam('order_id');
        $order =   $this->orderobj->load($orderId)->getCollection()
                    ->addFieldToFilter("entity_id",$orderId);
        $order->getSelect()->joinLeft(array("soi" =>'sales_order_item' ),
            'main_table.entity_id = soi.order_id',
            array("soi.order_id","soi.custom_message","soi.message_on_card"));
        $order->getSelect()->joinLeft(array("mo" =>'marketplace_orders' ),
            'main_table.entity_id = mo.order_id',
            array("mo.seller_id"));
        $order->getSelect()->joinLeft(array("mu" =>'marketplace_userdata' ),
            'mo.seller_id = mu.seller_id',
            array("mu.store_owner_mobile_no","mu.shop_title"));
        $order->getSelect()->joinLeft(array("ce" =>'customer_entity' ),
            'mo.seller_id = ce.entity_id',
            array("ce.firstname","ce.middlename","ce.lastname"));
        $order->getSelect()->group("main_table.entity_id");
        return $order->getData();

  }

    /**
     * @return mixed
     */
   public function getOrderTrackingUrl()
   {
       return $this->emailitems->getGuestTokenUrl();
   }

}