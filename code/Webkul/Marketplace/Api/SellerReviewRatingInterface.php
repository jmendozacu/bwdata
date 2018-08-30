<?php

/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 29-05-2018
 * Time: 14:50
 */
namespace Webkul\Marketplace\Api;

interface SellerReviewRatingInterface
{

    /**
     * Save review ratings
     *
     * @param int $seller_id
     * @param string $buyer_email
     * @param string $review
     * @param int $rating
     * @return array
     */
    public function saveReviewRating($seller_id,
            $buyer_email,
            $review,
            $rating);

    /**
     * Get Reviews and Rating
     * 
     * @param int $seller_id
     * @return array
     */
    public function getReviewRating($seller_id);
}
