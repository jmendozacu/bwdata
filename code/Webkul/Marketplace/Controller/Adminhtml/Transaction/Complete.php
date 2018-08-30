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

class Complete extends Action
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
        $transId = $this->getRequest()->getParam('trans_id');

        if (isset($transId) && $transId != "") {
            $collection = $this->transCollection
                            ->addFieldToFilter('transaction_id', ['eq'=>$transId])
                            ->addFieldToFilter('transaction_status', PayoutsHelper::TRANS_STATUS_PROCESSING);
            foreach ($collection as $transaction) {
                $transEntityId = $transaction->getData('entity_id');
                $sellerId = $transaction->getData('seller_id');
                if ($transEntityId) {
                    $this->payoutsHelper->updateSellerStatistics($transEntityId, $sellerId);
                }
                $transaction->setData('transaction_status', PayoutsHelper::TRANS_STATUS_PAID);
                $transaction->save();
            }

            if ($collection->count() > 0) {
                $this->messageManager->addSuccessMessage(__("Transaction %1 has been marked as completed.", $transId));
            } else {
                $this->messageManager->addErrorMessage(__("Transaction not found or not in processing state"));
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
