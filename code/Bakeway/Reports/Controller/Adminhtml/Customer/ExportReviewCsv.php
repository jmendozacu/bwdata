<?php
namespace Bakeway\Reports\Controller\Adminhtml\Customer;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem as Filesystem;

class ExportReviewCsv extends Action
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
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * @var \Magento\Framework\App\Response\Http\FileFactory
     */
    protected $_fileFactory;


    /**
     * ExportReviewCsv constructor.
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param \Magento\Framework\App\Response\Http\FileFactory $fileFactory
     * @param \Magento\Framework\Registry $registry
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        \Magento\Framework\App\Response\Http\FileFactory $fileFactory,
        \Magento\Framework\Registry $registry,
         Filesystem $filesystem
    )
    {
        parent::__construct($context);
        $this->_fileFactory = $fileFactory;
        $this->resultPageFactory = $resultPageFactory;
        $this->filesystem = $filesystem;
        $this->registry = $registry;
   }

    /**
     * @return \Magento\Framework\App\ResponseInterface
     * @throws \Exception
     */
    public function execute()
    {
        $this->resultPage = $this->resultPageFactory->create();
        $fileName   = 'reviewReport '.date("d-m-Y h:i:s ").'.csv';
        $content = $this->resultPage->getLayout()->createBlock('Bakeway\Reports\Block\Adminhtml\Reviews\Grid')->getCsv();
        return $this->_fileFactory->create($fileName, $content ,DirectoryList::VAR_DIR);

    }


    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Bakeway_Reports::report_custom_sales_orders');
    }


}
