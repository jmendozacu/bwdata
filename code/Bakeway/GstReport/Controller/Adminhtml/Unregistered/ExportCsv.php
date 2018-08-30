<?php
/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 02-05-2018
 * Time: 17:45
 */

namespace Bakeway\GstReport\Controller\Adminhtml\Unregistered;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Controller\ResultFactory;
use Magento\Backend\App\Action;

class ExportCsv
    extends \Magento\Backend\App\Action
{
    protected $fileFactory;
    /**
     * ExportCsv constructor.
     * @param Action\Context $context
     */
    public function __construct(\Magento\Backend\App\Action\Context $context, \Magento\Framework\App\Response\Http\FileFactory $fileFactory)
    {
        $this->fileFactory = $fileFactory;
        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|void
     */
    public function execute()
    {
        $this->_view->loadLayout();
        $fileName = 'unregistered_gst' . date('Y-m-d') . '.csv';
        $content = $this->_view->getLayout()->createBlock('Bakeway\GstReport\Block\Adminhtml\Unregistered\Grid')->getCsv();
        return $this->fileFactory->create(
            $fileName,
            $content,
            DirectoryList::VAR_DIR
        );
    }

    /**
     * @return bool
     */
    public function _isAllowed()
    {
        return $this->_authorization->isAllowed('Bakeway_GstReport::manage_gstreports');
    }
}