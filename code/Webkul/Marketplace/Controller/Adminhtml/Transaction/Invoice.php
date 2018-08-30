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

namespace Webkul\Marketplace\Controller\Adminhtml\Transaction;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Webkul\Marketplace\Model\ResourceModel\Sellertransaction\Collection as TransCollection;
use Bakeway\PayoutsCalculation\Helper\Data as PayoutsHelper;

class Invoice extends Action
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
     * @var TransCollection
     */
    protected $transCollection;

    /**
     * @var PayoutsHelper
     */
    protected $payoutsHelper;

    /**
     * @param Context       $context
     * @param PageFactory   $resultPageFactory
     * @param TransCollection $transCollection
     * @param PayoutsHelper $payoutsHelper
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        TransCollection $transCollection,
        PayoutsHelper $payoutsHelper
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->transCollection = $transCollection;
        $this->payoutsHelper = $payoutsHelper;
        parent::__construct($context);
    }

    /**
     * Seller Transaction list page.
     *
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Webkul_Marketplace::transaction');
        $resultPage->getConfig()->getTitle()->prepend(__('Sellers Transaction'));
        $transId = $this->getRequest()->getParam('id');

        if (isset($transId) && $transId != "") {

            $collection = $this->transCollection
                ->addFieldToFilter('entity_id', ['eq'=>$transId])
                ->addFieldToFilter('transaction_status', PayoutsHelper::TRANS_STATUS_PAID);
            if ($collection->getSize()) {
//                $helper = $this->_objectManager->create(
//                    'Webkul\Marketplace\Helper\Data'
//                );
//
//                $isPartner = $helper->isSeller();
//                $isNotifyView = $this->getRequest()->getParam('n')?true:false;
                //if ($isPartner == 1) {
                    //echo "inside this w";exit;
                    /** @var \Magento\Framework\View\Result\Page $resultPage */
                    $resultPage = $this->resultPageFactory->create();
                    $resultPage->getConfig()->getTitle()->set(
                        __('Transaction View')
                    );
                    return $resultPage;
//                } else {
//                    return $this->_redirect('marketplace/transaction/index');
//                }
            } else {
                return $this->_redirect('marketplace/transaction/index');
            }
        }
        $this->_redirect('marketplace/transaction/index');
        return;
    }

    /**
     * Check for is allowed.
     *
     * @return boolean
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Webkul_Marketplace::transaction');
    }
}
