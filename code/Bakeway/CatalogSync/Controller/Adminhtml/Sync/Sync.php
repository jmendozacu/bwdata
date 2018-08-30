<?php
namespace Bakeway\CatalogSync\Controller\Adminhtml\Sync;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Bakeway\CatalogSync\Helper\Data as CatalogSyncHelper;
use Symfony\Component\Config\Definition\Exception\Exception;
use Magento\Framework\Controller\ResultFactory;

class Sync extends Action
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
     * @var CatalogSyncHelper
     */
    protected  $catalogSyncHelper;

    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        CatalogSyncHelper $catalogSyncHelper
    )
    {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->catalogSyncHelper = $catalogSyncHelper;
    }

    public function execute()
    {
        $this->resultPage = $this->resultPageFactory->create();

        try{
            $this->catalogSyncHelper->syncToBakewayCatalog();

        }catch (Exception $e)
        {
            echo $e->getMessage();
        }

        $this->messageManager->addSuccess(__('Sync has Completed successfully !!!'));
        $this->_redirect('catalogsync/sync/index');
        return;


    }
}
