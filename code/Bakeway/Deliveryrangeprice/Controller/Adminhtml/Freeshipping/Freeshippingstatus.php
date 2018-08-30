<?php
/**
 * Created by PhpStorm.
 * User: kushagra
 * Date: 17/3/18
 * Time: 12:53 PM
 */
namespace Bakeway\Deliveryrangeprice\Controller\Adminhtml\Freeshipping;

use Magento\Framework\App\Filesystem\DirectoryList;
use Symfony\Component\Config\Definition\Exception\Exception;
use Webkul\Marketplace\Model\SellerFactory as sellerFactory;
use Bakeway\Deliveryrangeprice\Helper\Data as DeliveryrangepriceHelper;
use Bakeway\Logs\Helper\Data as LogsHelper;


class Freeshippingstatus extends \Magento\Backend\App\Action
{


    /**
     * @var \Magento\Framework\View\Result\PageFactory
    */
    protected $resultPageFactory;
    /**
     * @var sellerFactory
     */
    protected $sellerFactory;
    /**
     * @var DeliveryrangepriceHelper
     */
    protected $deliveryrangepriceHelper;
    /**
     * @var LogsHelper
     */
    protected $logsHelper;
    /**
     * Save constructor.
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     * @param \Magento\Customer\Model\Session $customerSession
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Customer\Model\Session $customerSession,
        sellerFactory $sellerFactory,
        DeliveryrangepriceHelper $deliveryrangepriceHelper,
        LogsHelper $logsHelper
    )
    {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->_customerSession = $customerSession;
        $this->sellerFactory = $sellerFactory;
        $this->deliveryrangepriceHelper = $deliveryrangepriceHelper;
        $this->auth = $context->getAuth();
        $this->logsHelper = $logsHelper;

    }


    public function execute()
    {
        $vendorId = $this->getRequest()->getParam('seller_id');

        $status = $this->getRequest()->getParam('free_delivery');

        if (isset($status)) {


                $sellerCollection = $this->sellerFactory->create()->getCollection()
                    ->addFieldToFilter('seller_id', $vendorId)
                    ->addFieldToSelect('entity_id')
                    ->getFirstItem();

                if (isset($sellerCollection['entity_id'])) {

                    $sellerModel = $this->sellerFactory->create()->load($sellerCollection['entity_id']);
                    $sellerModel->setIsFreeDelivery($status);

                    try {
                        $sellerModel->save();
                        $this->messageManager->addSuccess(__('Saved successfully!!!'));
                        $this->_redirect('customer/index/edit/', array('id' =>$vendorId, '_current' => true,'mode' =>'manage_free_delivery_save_target'));
                        return;

                    } catch (Exception $e) {
                        $this->messageManager->addError($e->getMessage());
                    }
                }



            }
        $this->messageManager->addSuccess(__('Saved successfully!!!'));

        $this->_redirect('customer/index/edit/', array('id' =>$vendorId, '_current' => true,'mode' =>'manage_free_delivery_save_target'));
        return;
    }





}