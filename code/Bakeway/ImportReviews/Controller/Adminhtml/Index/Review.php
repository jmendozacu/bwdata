<?php
/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 24-04-2018
 * Time: 17:11
 */

namespace Bakeway\ImportReviews\Controller\Adminhtml\Index;

use \Magento\Framework\Controller\ResultFactory;

class Review extends \Magento\Backend\App\Action
{
    /**
     * @var \Magento\Catalog\Model\ImageUploader
     */
    protected $fileUploader;

    /**
     * @var \Magento\Framework\File\Csv
     */
    protected $csv;

    /**
     * @var
     */
    protected $saveReview;

    /**
     * Review constructor.
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Bakeway\ImportReviews\Model\FileUploader $fileUploader
     * @param \Magento\Framework\File\Csv $csv
     * @param \Bakeway\ImportReviews\Model\SaveReviews $saveReview
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Bakeway\ImportReviews\Model\FileUploader $fileUploader,
        \Magento\Framework\File\Csv $csv,
        \Bakeway\ImportReviews\Model\SaveReviews $saveReview
    )
    {
        parent::__construct($context);
        $this->fileUploader = $fileUploader;
        $this->saveReview = $saveReview;
        $this->csv = $csv;
    }

    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Bakeway_ImportReviews::manage_importratings');
    }

    /**
     * Upload Reviews
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|void
     */
    public function execute()
    {
        try {
            $result = $this->fileUploader->saveFileToTmpDir('reviewfile');
            if (!empty($result['file']) && isset($result['file'])) {
                $path = $result['path'] . '/' . $result['file'];
                $importRawData = $this->csv->getData($path);
                $response = $this->saveReview->saveReviews($importRawData);

                if ($response) {
                    $this->messageManager->addSuccessMessage('Review/s Saved Successfully');
                } else {
                    $this->messageManager->addErrorMessage('Unable to Save Review/s');
                }

                $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
                $resultRedirect->setUrl($this->_redirect->getRefererUrl());
            }

        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage('Unable to Save Review/s');
        }

        return $resultRedirect;
    }
}