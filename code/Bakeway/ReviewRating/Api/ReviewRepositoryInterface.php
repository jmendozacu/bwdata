<?php

namespace Bakeway\ReviewRating\Api;

/**
 * ReviewRepositoryInterface
 */

interface ReviewRepositoryInterface
{

    /**
     * Get review details from token
     * @api
     * @param string $token
     * @return array
     * @throws \Magento\Framework\Exception\NotFoundException
     */

    public function getTokenReviewdetails($token);

    /**
     * @param string $token
     * @param string $text
     * @param mixed $ratingData
     * @return mixed
     */
    public function saveReview($token, $text, $ratingData);
}