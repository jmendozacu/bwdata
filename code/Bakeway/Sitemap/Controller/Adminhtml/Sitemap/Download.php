<?php
/**
 *
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Bakeway\Sitemap\Controller\Adminhtml\Sitemap;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Backend\App\Action;

class Download extends Action
{
    /**
     * @var \Magento\Framework\Controller\Result\RawFactory
     */
    protected $resultRawFactory;

    /**
     * @var \Magento\Framework\Filesystem\Directory\WriteInterface
     */
    protected $directory;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\App\Response\Http\FileFactory $fileFactory
     * @param \Magento\Framework\Controller\Result\RawFactory $resultRawFactory
     * @param \Magento\Framework\Filesystem $filesystem
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\App\Response\Http\FileFactory $fileFactory,
        \Magento\Framework\Controller\Result\RawFactory $resultRawFactory,
        \Magento\Framework\Filesystem $filesystem
    ) {
        parent::__construct(
            $context
        );
        $this->fileFactory = $fileFactory;
        $this->resultRawFactory = $resultRawFactory;
        $this->directory = $filesystem->getDirectoryWrite(DirectoryList::ROOT);
    }

    /**
     * Download backup action
     *
     * @return void|\Magento\Backend\App\Action
     */
    public function execute()
    {
        $fileName = $this->getRequest()->getParam('filename');
        $fileName = str_replace("__-", "/", $fileName);

        if (!$this->directory->isFile($fileName)) {
            /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
            $resultRedirect = $this->resultRedirectFactory->create();
            $resultRedirect->setPath('sitemap/sitemap/index');
            return $resultRedirect;
        }

        $this->fileFactory->create(
            $fileName,
            null,
            DirectoryList::ROOT,
            'application/octet-stream',
            $this->directory->stat($fileName)['size']
        );
        /** @var \Magento\Framework\Controller\Result\Raw $resultRaw */
        $resultRaw = $this->resultRawFactory->create();
        $resultRaw->setContents($this->directory->readFile($fileName));
        return $resultRaw;
    }
}
