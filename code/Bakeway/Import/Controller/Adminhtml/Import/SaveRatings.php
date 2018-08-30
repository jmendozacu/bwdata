<?php

/**
 *
 * Copyright © 2015 Bakewaycommerce. All rights reserved.
 */
namespace Bakeway\Import\Controller\Adminhtml\Import;

use Magento\Framework\Controller\ResultFactory;

class SaveRatings extends \Magento\Backend\App\Action
{

    /** @var \Webkul\Marketplace\Model\Feedbackcount */
    protected $feedbackcountModel;

    /** @var \Webkul\Marketplace\Model\Seller */
    protected $userData;

    /** @var \Magento\Framework\View\Result\PageFactory */
    protected $resultPageFactory;

    /**
     * Construct
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Webkul\Marketplace\Model\Feedbackcount $feedbackcountModel
     * @param \Webkul\Marketplace\Model\Seller $userData
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     */
    public function __construct(\Magento\Backend\App\Action\Context $context,
            \Webkul\Marketplace\Model\Feedbackcount $feedbackcountModel,
            \Webkul\Marketplace\Model\Seller $userData,
            \Magento\Framework\View\Result\PageFactory $resultPageFactory)
    {
        parent::__construct($context);
        $this->feedbackcountModel = $feedbackcountModel;
        $this->userData = $userData;
        $this->resultPageFactory = $resultPageFactory;
    }

    /**
     * Import seller ratings
     */
    public function execute()
    {
        if (!empty($_FILES['import_ratings_file']['tmp_name'])) {
            $target_dir = BP . "/var/import/";

            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }

            $target_file = $target_dir . basename($_FILES["import_ratings_file"]["name"]);
            move_uploaded_file($_FILES['import_ratings_file']['tmp_name'],
                    $target_file);

            $filename = BP . '/var/import/' . $_FILES["import_ratings_file"]["name"];
            $fp = fopen($filename, "r");
            $counter = 0;

            while (($row = fgetcsv($fp, "5400", ",")) != FALSE) {
                if ($counter) {
                    try {
                        $sellerId = $row[0];
                        $sellerRating = $row[1];

                        $rating = $sellerRating * 100;
                        $avgRating = $rating / 100;
                        $datetime = new \DateTime(date('Y-m-d H:i:s'));
                        $datetime->setTimezone(new \DateTimeZone('Asia/Kolkata'));
                        $createdAt = $datetime->format('Y-m-d H:i:s');

                        $datetime1 = new \DateTime(date('Y-m-d H:i:s'));
                        $datetime1->setTimezone(new \DateTimeZone('Asia/Kolkata'));
                        $updatedAt = $datetime1->format('Y-m-d H:i:s');


                        $feedbackCountCollection = $this->feedbackcountModel->getCollection()->addFieldToFilter('seller_id',
                                $sellerId);
                        if (empty($feedbackCountCollection->getData())) {
                            $this->feedbackcountModel->setSellerId($sellerId);
                            $this->feedbackcountModel->setFeedbackCount($rating);
                            $this->feedbackcountModel->setUserCount(1);
                            $this->feedbackcountModel->setAvgRating($avgRating);
                            $this->feedbackcountModel->setCreatedAt($createdAt);
                            $this->feedbackcountModel->setUpdatedAt($updatedAt);
                            $this->feedbackcountModel->save();
                            
                            $objectManager = \Magento\Framework\App\ObjectManager::getInstance(); // Instance of object manager
                            $resource = $objectManager->get('Magento\Framework\App\ResourceConnection');
                            $connection = $resource->getConnection();
                            $tableName = $resource->getTableName('marketplace_userdata'); 
                            
                            $sqlDisable = 'SET FOREIGN_KEY_CHECKS=0;';
                            $connection->query($sqlDisable);
                            $feedbackCountCollection = $this->userData->getCollection()->addFieldToFilter('seller_id',
                                $sellerId);
                            $data = $feedbackCountCollection->getData();
                            $userDataModel = $this->userData->load($data[0]['entity_id']);
                            $userDataModel->setAverageRatings($avgRating);
                            $userDataModel->save();
                            $sqlEnable = 'SET FOREIGN_KEY_CHECKS=0;';
                            $connection->query($sqlEnable);
                        }

                        $this->messageManager->addSuccess(__('Seller ratings imported successfully !!!'));
                        $this->_redirect('import/import/ratings');
                    } catch (Exception $ex) {
                        $this->messageManager->addSuccess(__($ex->getMessage()));
                        $this->_redirect('import/import/ratings');
                    }
                }
                $counter++;
            }
        } else {
            $this->messageManager->addSuccess(__('Something went wrong !!!'));
            $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
            $resultRedirect->setUrl($this->_redirect->getRedirectUrl());
            return $resultRedirect;
        }
    }

}
