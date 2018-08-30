<?php
/**
 * Created by PhpStorm.
 * User: kushagra
 * Date: 17/3/18
 * Time: 12:53 PM
 */
namespace Bakeway\Deliveryrangeprice\Controller\Freeshippingvalue;

use Aws\Ec2\Exception\Ec2Exception;
use Magento\Framework\App\RequestInterface;
use Symfony\Component\Config\Definition\Exception\Exception;
use Webkul\Marketplace\Model\SellerFactory as sellerFactory;
use Bakeway\Deliveryrangeprice\Helper\Data as DeliveryrangepriceHelper;
use Bakeway\Logs\Helper\Data as LogsHelper;

class Save extends \Magento\Framework\App\Action\Action
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
     * @param sellerFactory $sellerFactory
     * @param DeliveryrangepriceHelper $deliveryrangepriceHelper
     * @param LogsHelper $logsHelper
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
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
        $this->logsHelper = $logsHelper;

    }

    /**
     * Check customer authentication.
     *
     * @param RequestInterface $request
     *
     * @return \Magento\Framework\App\ResponseInterface
     */
    public function dispatch(RequestInterface $request) {
        $loginUrl = $this->_objectManager->get(
            'Magento\Customer\Model\Url'
        )->getLoginUrl();

        if (!$this->_customerSession->authenticate($loginUrl)) {
            $this->_actionFlag->set('', self::FLAG_NO_DISPATCH, true);
        }

        return parent::dispatch($request);
    }

    public function execute()
    {
        $existMaxPrice = "";

        $maxPriceVal = $this->getRequest()->getParam('max-price-value');

        $existMaxPrice = $this->deliveryrangepriceHelper->getSellerMaxInPrice($this->_customerSession->getCustomerId());


        if($maxPriceVal != $existMaxPrice) {
            if (isset($maxPriceVal)) {
                /**
                 * get current logged in customer id
                 */
                $sellerId = $this->_customerSession->getCustomerId();

                $sellerCollection = $this->sellerFactory->create()->getCollection()
                    ->addFieldToFilter('seller_id', $sellerId)
                    ->addFieldToSelect('entity_id')
                    ->getFirstItem();

                if (isset($sellerCollection['entity_id'])) {

                    $sellerModel = $this->sellerFactory->create()->load($sellerCollection['entity_id']);
                    $sellerModel->setIsDeiveryMaxPrice($maxPriceVal);

                    try {
                        $sellerModel->save();
                        /*
                        * saving logs
                        */
                        $this->logsHelper->getFreeshippingLogParam($sellerId,$maxPriceVal);
                        $this->messageManager->addSuccess(__('Saved successfully!!!'));
                        $this->_redirect('deliveryrangeprice/delivery/rangeprice/');



                    } catch (Exception $e) {
                        $this->messageManager->addError($e->getMessage());
                    }
                }



            }
        }else{
            $this->messageManager->addSuccess(__('Saved successfully!!!'));
        }
        $this->_redirect('deliveryrangeprice/delivery/rangeprice/');
        $this->resultPage = $this->resultPageFactory->create();
        return $this->resultPage;
    }

}