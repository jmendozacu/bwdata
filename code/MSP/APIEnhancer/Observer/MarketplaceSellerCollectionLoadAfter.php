<?php
/**
 * MageSpecialist
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to info@magespecialist.it so we can send you a copy immediately.
 *
 * @category   MSP
 * @package    MSP_APIEnhancer
 * @copyright  Copyright (c) 2017 Skeeller srl (http://www.magespecialist.it)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace MSP\APIEnhancer\Observer;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Store\Model\StoreManagerInterface;
use MSP\APIEnhancer\Api\CustomerAuthInterface;
use MSP\APIEnhancer\Api\TagInterface;

class MarketplaceSellerCollectionLoadAfter implements ObserverInterface
{
    /**
     * @var TagInterface
     */
    private $tag;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var CustomerAuthInterface
     */
    private $customerAuth;

    /**
     * @var TimezoneInterface
     */
    private $timezone;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    public function __construct(
        TagInterface $tag,
        StoreManagerInterface $storeManager,
        ScopeConfigInterface $scopeConfig,
        CustomerAuthInterface $customerAuth,
        TimezoneInterface $timezone
    ) {
        $this->tag = $tag;
        $this->storeManager = $storeManager;
        $this->customerAuth = $customerAuth;
        $this->timezone = $timezone;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $collection = $observer->getCollection();

        $tags = ['marketplace_seller'];
        foreach ($collection as $seller) {
            $tags[] = 'marketplace_seller_' . $seller->getData('seller_id');
        }

        $this->tag->addTags($tags);
    }
}
