<?php

/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 29-05-2018
 * Time: 14:51
 */
namespace Webkul\Marketplace\Model;

use \Webkul\Marketplace\Model\Feedbackcount;
use \Webkul\Marketplace\Model\Feedback;
use \Webkul\Marketplace\Model\Seller;
use \Magento\Customer\Model\Customer;

class SellerReviewRating
        implements \Webkul\Marketplace\Api\SellerReviewRatingInterface
{

    /**
     * Customer Model
     * @var \Magento\Customer\Model\Customer 
     */
    protected $customerModel;

    /**
     * User Data Model
     * @var \Webkul\Marketplace\Model\Seller
     */
    protected $userData;

    /**
     * Feedback Model
     * @var \Webkul\Marketplace\Model\Feedback
     */
    protected $feedbackModel;

    /**
     * @var Model \Webkul\Marketplace\Model\Feedbackcount
     */
    protected $feedbackcountModel;

    /**
     * SellerReviewRating constructor.
     * @param \Webkul\Marketplace\Model\Feedback $feedbackModel
     * @param \Webkul\Marketplace\Model\Feedbackcount $feedbackcountModel
     */
    public function __construct(
    Feedback $feedbackModel,
            Feedbackcount $feedbackcountModel,
            Seller $userData,
            Customer $customerModel
    )
    {
        $this->feedbackModel = $feedbackModel;
        $this->feedbackcountModel = $feedbackcountModel;
        $this->userData = $userData;
        $this->customerModel = $customerModel;
    }

    /**
     * @param int $seller_id
     * @param string $buyer_email
     * @param string $review
     * @param int $rating
     * @return array
     * @throws \Exception
     */
    public function saveReviewRating($seller_id,
            $buyer_email,
            $review,
            $rating)
    {
        $datetime = new \DateTime(date('Y-m-d H:i:s'));
        $datetime->setTimezone(new \DateTimeZone('Asia/Kolkata'));
        $createdAt = $datetime->format('Y-m-d H:i:s');
        
        $datetime1 = new \DateTime(date('Y-m-d H:i:s'));
        $datetime1->setTimezone(new \DateTimeZone('Asia/Kolkata'));
        $updatedAt = $datetime1->format('Y-m-d H:i:s');      
        
        $this->customerModel->setWebsiteId(1);
        $buyerId = $this->customerModel->loadByEmail($buyer_email)->getId();
        $returnMessage = '';
        $feedbackCollection = $this->feedbackModel->getCollection()
                ->addFieldToFilter('seller_id', $seller_id)
                ->addFieldToFilter('buyer_id', $buyerId);
        if (empty($feedbackCollection->getData())) {
            $this->feedbackModel->setSellerId($seller_id);
            $this->feedbackModel->setBuyerId($buyerId);
            $this->feedbackModel->setBuyerEmail($buyer_email);
            $this->feedbackModel->setStatus(0);
            $this->feedbackModel->setFeedReview($review);
            $this->feedbackModel->setFeedValue($rating);
            $this->feedbackModel->setCreatedAt($createdAt);
            $this->feedbackModel->setUpdatedAt($updatedAt);
            $this->feedbackModel->save();

            $feedbackCountCollection = $this->feedbackcountModel->getCollection()->addFieldToFilter('seller_id',
                    $seller_id);
            $avgRating = 0;
            if (empty($feedbackCountCollection->getData())) {
                $this->feedbackcountModel->setSellerId($seller_id);
                $this->feedbackcountModel->setFeedbackCount($rating);
                $this->feedbackcountModel->setUserCount(1);
                $this->feedbackcountModel->setAvgRating($avgRating);
                $this->feedbackcountModel->setCreatedAt($createdAt);
                $this->feedbackcountModel->setUpdatedAt($updatedAt);
                $this->feedbackcountModel->save();
            } else {
                $data = $feedbackCountCollection->getData();
                $model = $this->feedbackcountModel->load($data[0]['entity_id']);
                $feedbackCount = $model->getFeedbackCount() + $rating;
                $userCount = $model->getUserCount() + 1;
                $model->setFeedbackCount($feedbackCount);
                $model->setUserCount($userCount);
                $model->setAvgRating($avgRating);
                $model->setCreatedAt($createdAt);
                $model->setUpdatedAt($updatedAt);
                $model->save();
            }
            $returnMessage['status'] = true;
        } else {
            $returnMessage['status'] = false;
        }

        return json_decode(json_encode($returnMessage), false);
    }

    /**
     * Get Seller Review Rating
     * 
     * @param int $seller_id
     * @return array
     */
    public function getReviewRating($seller_id)
    {
        $sellerData = $this->userData->getCollection()->addFieldToFilter('seller_id',
                $seller_id);
        $sData = $sellerData->getData();
        $data = $sData[0];
        if (isset($data) && !empty($data)) {
            $deliver = $data['delivery'] ? true : false;
            $pickup = $data['delivery'] ? false : true;
            $returnData['sellerName'] = $data['shop_title'];
            $returnData['sellerId'] = $seller_id;
            $returnData['sellerArea'] = $data['store_locality_area'];
            $returnData['averageRating'] = $data['average_ratings'];
            $returnData['pickup'] = $pickup;
            $returnData['delivery'] = $deliver;
            $returnData['ratings'] = array();
            $feedbackCollection = $this->feedbackModel->getCollection()
                    ->addFieldToFilter('seller_id', $seller_id)
                    ->addFieldToFilter('status', 1);
            foreach ($feedbackCollection as $key => $feedback) {
                $datetime = new \DateTime($feedback->getCreatedAt());
                $datetime->setTimezone(new \DateTimeZone('Asia/Kolkata'));
                $createdAt = $datetime->format('Y-m-d H:i:s');
                
                $customer = $this->customerModel->load($feedback->getBuyerId());
                $customerName = $customer->getFirstname() . ' ' . $customer->getLastname();
                array_push(
                        $returnData['ratings'],
                        array(
                    'reviewerName' => $customerName,
                    'comments' => $feedback->getFeedReview(),
                    'ratingGiven' => $feedback->getFeedValue(),
                    'reviewedDate' => $createdAt,
                        )
                );
            }
            return json_decode(json_encode($returnData), false);
        } else {
            return json_decode(json_encode($returnData), false);
        }
    }

    /**
     * Calculate seller ratings
     * @param int $sellerId
     */
    public function calculateRatings($sellerId)
    {
        $feedbackCollection = $this->feedbackModel->getCollection()
                ->addFieldToFilter('seller_id', $sellerId)
                ->addFieldToFilter('status', 1);
        $userCount = 0;
        $feedbackCount = 0;
        $avgCount = 0;
        foreach ($feedbackCollection as $feedback) {
            $feedbackCount += $feedback->getFeedValue();
            $userCount++;
        }

        $feedbackCountCollection = $this->feedbackcountModel->getCollection()->addFieldToFilter('seller_id',
                $sellerId);
        $data = $feedbackCountCollection->getData();
        $model = $this->feedbackcountModel->load($data[0]['entity_id']);
        $model->setFeedbackCount($feedbackCount);
        $model->setUserCount($userCount);
        if ($feedbackCount) {
            $avgCount = $feedbackCount / $userCount;
        }
        $model->setAvgRating($avgCount);
        $model->save();
    }

}
