<?php

/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Bakeway\EventsListing\Plugin\Captcha;

use Thai\S3\Model\MediaStorage\File\Storage\S3 as ThaiBucketStorage;
use Magento\Store\Model\StoreManagerInterface as StoreManager;

class DefaultModel {

    /**
     * @var ThaiBucketStorage
     */
    protected $thaiBucketStorage;

    /**
     * @var StoreManager
     */
    protected $storeManager;

    public function __construct(
    ThaiBucketStorage $thaiBucketStorage, StoreManager $storeManager
    ) {
        $this->thaiBucketStorage = $thaiBucketStorage;
        $this->storeManager = $storeManager;
    }

    public function afterGetImgSrc(\Magento\Captcha\Model\DefaultModel $subject, $result) {
        $this->thaiBucketStorage->saveFile("captcha/" . $this->getCurrentWebsiteId() . "/" . $subject->getId() . $subject->getSuffix());
        return $subject->getImgUrl() . $subject->getId() . $subject->getSuffix();
    }

    public function getCurrentWebsiteId() {
        return $this->storeManager->getStore()->getWebsite()->getCode();
    }

}
