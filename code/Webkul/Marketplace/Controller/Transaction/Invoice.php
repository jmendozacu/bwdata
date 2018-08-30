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

namespace Webkul\Marketplace\Controller\Transaction;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\App\RequestInterface;

/**
 * Webkul Marketplace Transaction View Controller.
 */
class Invoice extends Action
{
    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $_customerSession;

    /**
     * @var PageFactory
     */
    protected $_resultPageFactory;

    /**
     * @param Context     $context
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        \Magento\Customer\Model\Session $customerSession
    ) {
        $this->_customerSession = $customerSession;
        $this->_resultPageFactory = $resultPageFactory;
        parent::__construct($context);
    }

    /**
     * Check customer authentication.
     *
     * @param RequestInterface $request
     *
     * @return \Magento\Framework\App\ResponseInterface
     */
    public function dispatch(RequestInterface $request)
    {
        $loginUrl = $this->_objectManager->get(
            'Magento\Customer\Model\Url'
        )->getLoginUrl();

        if (!$this->_customerSession->authenticate($loginUrl)) {
            $this->_actionFlag->set('', self::FLAG_NO_DISPATCH, true);
        }

        return parent::dispatch($request);
    }

    /**
     * Default customer account page.
     *
     * @return \Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        $id = 0;
        $paramData = $this->getRequest()->getParams();
        if (!empty($paramData['id'])) {
            $id = $paramData['id'];
        }
        $collection = $this->_objectManager
        ->create('Webkul\Marketplace\Model\Sellertransaction')
        ->getCollection()
        ->addFieldToFilter(
            'seller_id',
            $this->_customerSession->getCustomerId()
        )
        ->addFieldToFilter(
            'entity_id',
            $id
        );
        if ($collection->getSize()) {
            $helper = $this->_objectManager->create(
                'Webkul\Marketplace\Helper\Data'
            );
            $isPartner = $helper->isSeller();
            $isNotifyView = $this->getRequest()->getParam('n')?true:false;
            if ($isPartner == 1) {
                /** @var \Magento\Framework\View\Result\Page $resultPage */
                $resultPage = $this->_resultPageFactory->create();
                $resultPage->getConfig()->getTitle()->set(
                    __('Transaction View')
                );
                if ($isNotifyView) {
                    $this->_updateNotification($collection);
                }
                return $resultPage;
            } else {
                return $this->resultRedirectFactory->create()->setPath(
                    'marketplace/account/becomeseller',
                    ['_secure' => $this->getRequest()->isSecure()]
                );
            }
        } else {
            return $this->resultRedirectFactory->create()->setPath(
                'marketplace/transaction/invoice',
                ['_secure' => $this->getRequest()->isSecure()]
            );
        }
    }

    /**
     * Updated notification, mark as read.
     */
    protected function _updateNotification($collection)
    {
        foreach ($collection as $value) {
            $isNotification = $value->getSellerPendingNotification();
            if ($isNotification) {
                $value->setSellerPendingNotification(0);
                $value->setId($value->getEntityId())->save();
            }
        }
    }
}
