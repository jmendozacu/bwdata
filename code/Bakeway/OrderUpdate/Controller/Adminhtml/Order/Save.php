<?php
namespace Bakeway\OrderUpdate\Controller\Adminhtml\Order;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Bakeway\OrderUpdate\Model\OrderupdateFactory as Orderupdate;
use Symfony\Component\Config\Definition\Exception\Exception;
use Magento\Sales\Model\OrderFactory as OrderFactory;
use Magento\Framework\Controller\ResultFactory;
use Bakeway\Quotemanagement\Helper\Data as QuotemanagementHelper;

class Save extends Action
{

    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var \Magento\Backend\Model\View\Result\Page
     */
    protected $resultPage;

    /**
     * Orderupdate
     */
    protected $orderupdate;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $_date;

    /**
     * OrderFactory
     */
    protected $orderFactory;

    /**
     * QuotemanagementHelper
     */
    protected  $quotemanagementHelper;

   /**
    * Save constructor.
    * @param Context $context
    * @param PageFactory $resultPageFactory
    * @param Orderupdate $orderupdate
    * @param \Magento\Framework\Stdlib\DateTime\DateTime $date
    * @param OrderFactory $orderFactory
    * @param QuotemanagementHelper $quotemanagementHelper
    */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        Orderupdate $orderupdate,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        OrderFactory $orderFactory,
        QuotemanagementHelper $quotemanagementHelper

    )
    {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->orderupdate = $orderupdate;
        $this->_date = $date;
        $this->auth = $context->getAuth();
        $this->orderFactory = $orderFactory;
        $this->quotemanagementHelper= $quotemanagementHelper;
    }

    public function execute()
    {
        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/ordercreate.log');
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);
        $logger->info("------------------------start----------------------------------");
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $orderId = $this->getRequest()->getParam("entity_id");
        $order =  $this->orderFactory->create()->load($orderId);
        if($this->checkExistOrderId($order->getIncrementId()) === true) {
        if(isset($orderId) && !empty($orderId)){

            $orderObj = $this->orderupdate->create();
            $orderObj->setOrderId($order->getIncrementId());
            $orderObj->setCreatedAt($this->_date->gmtDate());
            $orderObj->setCreatedBy($this->getCurrentadminName());
            try{
                $orderObj->save();

                $logger->info("log has saved in table for order id ".$order->getIncrementId());
                $logger->info("order has forwarded for order id ".$order->getIncrementId());
                /**
                 * call forward order api method to resend order email to customer and updated order status
                 */

                $this->quotemanagementHelper->forwardOrder($order->getIncrementId());

                $this->messageManager->addSuccess(__('Order '.$order->getIncrementId().' has been updated successfully !!!'));
                $resultRedirect->setUrl($this->_redirect->getRedirectUrl());
                return $resultRedirect;
            }catch (Exception $e){

                $this->messageManager->addError(__('Order '.$order->getIncrementId().' has not updated error is '.$e->getMessage()));
                $resultRedirect->setUrl($this->_redirect->getRedirectUrl());
                return $resultRedirect;
            }

         }
        }

        $logger->info("------------------------end----------------------------------");
        $this->messageManager->addError(__('Order '.$order->getIncrementId().' has been already in updated mode'));
        $resultRedirect->setUrl($this->_redirect->getRedirectUrl());
        return $resultRedirect;
    }

    /**
     * get current user email
     * return email
     */
    public function getCurrentadminName()
    {
        $loginUserEmail = $this->auth->getUser()->getUsername();

        if (!empty($loginUserEmail)) {
            return $loginUserEmail;
        }
        return;
    }

    /**
     * @param $orderId
     * @return bool
     */
    public function checkExistOrderId($orderId)
    {
       $collection =  $this->orderupdate->create()->getCollection()
           ->addFieldToFilter("order_id",$orderId)
           ->getSize();
       if(!empty($collection)){
           return false;
       }
       return true;

    }
}
