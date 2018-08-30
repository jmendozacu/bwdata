<?php
/**
 * Webkul Software.
 *
 * @category  Webkul
 * @package   Webkul_Marketplace
 * @author    Webkul
 * @copyright Copyright (c) 2010-2017 Webkul Software Private Limited (https://webkul.com)
 * @license   https://store.webkul.com/license.html
 */

namespace Webkul\Marketplace\Observer;

use Magento\Framework\Event\ObserverInterface;
use Webkul\Marketplace\Model\ResourceModel\Seller\CollectionFactory;

/**
 * Webkul Marketplace CustomerRegisterSuccessObserver Observer.
 */
class CustomerRegisterSuccessObserver implements ObserverInterface
{
    /**
     * @var ObjectManagerInterface
     */
    protected $_objectManager;

    /**
     * @var CollectionFactory
     */
    protected $_collectionFactory;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $_date;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var \Magento\Framework\Filesystem\Io\File
     */
    protected $_filesystemFile;

    protected $coreRegistry;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    private $_messageManager;

    /**
     * @param \Magento\Framework\ObjectManagerInterface   $objectManager
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $date
     * @param \Magento\Store\Model\StoreManagerInterface  $storeManager
     * @param \Magento\Framework\Filesystem\Io\File       $filesystemFile
     * @param \Magento\Framework\Message\ManagerInterface $messageManager,
     * @param CollectionFactory                           $collectionFactory
     */
    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Filesystem\Io\File $filesystemFile,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        CollectionFactory $collectionFactory,
        \Magento\Framework\Registry $registry
    ) {
        $this->_objectManager = $objectManager;
        $this->_storeManager = $storeManager;
        $this->_filesystemFile = $filesystemFile;
        $this->_messageManager = $messageManager;
        $this->_collectionFactory = $collectionFactory;
        $this->_date = $date;
        $this->coreRegistry = $registry;

    }

    /**
     * customer register event handler.
     *
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
	   $this->moveDirToMediaDir();
        $data = $observer['account_controller'];
        $this->coreRegistry->register('is_new_account', true);
        try {
            $paramData = $data->getRequest()->getParams();
            $paramData['is_seller']  = 1; //default 1
            $paramData['profileurl'] = uniqid(rand(), true); //random id for shop url
            if (!isset($paramData['store_city'])) {
                $paramData['store_city'] = 1; //default city set to 0 = Pune
            }
            $_daysarray = [];
            if (!empty($paramData['is_seller']) && !empty($paramData['profileurl']) && $paramData['is_seller'] == 1) {
                $customer = $observer->getCustomer();

                $profileurlcount = $this->_objectManager->create(
                    'Webkul\Marketplace\Model\Seller'
                )->getCollection();
                $profileurlcount->addFieldToFilter(
                    'shop_url',
                    $paramData['profileurl']
                );
                if (!$profileurlcount->getSize()) {
                    $status = $this->_objectManager->get(
                        'Webkul\Marketplace\Helper\Data'
                    )->getIsPartnerApproval() ? 0 : 1;
                    $customerid = $customer->getId();
                    $model = $this->_objectManager->create(
                        'Webkul\Marketplace\Model\Seller'
                    );
                    $model->setData('is_seller', $status);
                    $model->setData('shop_url', $paramData['profileurl']);
                    $model->setData('seller_id', $customerid);
                    $model->setData('store_city', $paramData['store_city']);
                    /*days selected by default */
                    $_daysarray = array(1, 1, 1, 1, 1, 1, 1);
                    $_DaysVal = serialize($_daysarray);
                    $model->setData('userdata_operational_days', $_DaysVal);
                    $model->setCreatedAt($this->_date->gmtDate());
                    $model->setUpdatedAt($this->_date->gmtDate());
                    if ($status == 0) {
                        $model->setAdminNotification(1);
                    }
                    $model->save();

                    $helper = $this->_objectManager->get(
                        'Webkul\Marketplace\Helper\Data'
                    );
                    if ($helper->getAutomaticUrlRewrite()) {
                        $this->createSellerPublicUrls($paramData['profileurl']);
                    }
                    $adminStoremail = $helper->getAdminEmailId();
                    $adminEmail = $adminStoremail ? $adminStoremail : $helper->getDefaultTransEmailId();
                    $adminUsername = 'Admin';
                    $senderInfo = [
                        'name' => $customer->getFirstName().' '.$customer->getLastName(),
                        'email' => $customer->getEmail(),
                    ];
                    $receiverInfo = [
                        'name' => $adminUsername,
                        'email' => $adminEmail,
                    ];
                    $emailTemplateVariables['myvar1'] = $customer->getFirstName().' '.
                    $customer->getLastName();
                    $emailTemplateVariables['myvar2'] = $this->_objectManager->get(
                        'Magento\Backend\Model\Url'
                    )->getUrl(
                        'customer/index/edit',
                        ['id' => $customer->getId()]
                    );
                    $emailTemplateVariables['myvar3'] = 'Admin';

                    $this->_objectManager->create(
                        'Webkul\Marketplace\Helper\Email'
                    )->sendNewSellerRequest(
                        $emailTemplateVariables,
                        $senderInfo,
                        $receiverInfo
                    );
                } else {
                    $this->_messageManager->addError(
                        __('This Shop URL already Exists.')
                    );
                }
            }
        } catch (\Exception $e) {
            $this->_messageManager->addError($e->getMessage());
        }
    }

    private function moveDirToMediaDir()
    {
        try {
            /** @var \Magento\Framework\ObjectManagerInterface $objManager */
            $objManager = \Magento\Framework\App\ObjectManager::getInstance();
            /** @var \Magento\Framework\Module\Dir\Reader $reader */
            $reader = $objManager->get('Magento\Framework\Module\Dir\Reader');

            /** @var \Magento\Framework\Filesystem $filesystem */
            $filesystem = $objManager->get('Magento\Framework\Filesystem');

            $mediaAvatarFullPath = $filesystem->getDirectoryRead(
                \Magento\Framework\App\Filesystem\DirectoryList::MEDIA
            )->getAbsolutePath('avatar');
            if (!$this->_filesystemFile->fileExists($mediaAvatarFullPath)) {
                $this->_filesystemFile->mkdir($mediaAvatarFullPath, 0777, true);
                $avatarBannerImage = $reader->getModuleDir(
                    '',
                    'Webkul_Marketplace'
                ).'/view/base/web/images/avatar/banner-image.png';
                $this->_filesystemFile->cp($avatarBannerImage, $mediaAvatarFullPath.'/banner-image.png');
                $avatarNoImage = $reader->getModuleDir(
                    '',
                    'Webkul_Marketplace'
                ).'/view/base/web/images/avatar/noimage.png';
                $this->_filesystemFile->cp($avatarNoImage, $mediaAvatarFullPath.'/noimage.png');
            }

            $mediaMarketplaceFullPath = $filesystem->getDirectoryRead(
                \Magento\Framework\App\Filesystem\DirectoryList::MEDIA
            )->getAbsolutePath('marketplace');
            if (!$this->_filesystemFile->fileExists($mediaMarketplaceFullPath)) {
                $this->_filesystemFile->mkdir($mediaMarketplaceFullPath, 0777, true);
            }

            $mediaMarketplaceBannerFullPath = $filesystem->getDirectoryRead(
                \Magento\Framework\App\Filesystem\DirectoryList::MEDIA
            )->getAbsolutePath('marketplace/banner');
            if (!$this->_filesystemFile->fileExists($mediaMarketplaceBannerFullPath)) {
                $this->_filesystemFile->mkdir($mediaMarketplaceBannerFullPath, 0777, true);
                $marketplaceBannerImage = $reader->getModuleDir(
                    '',
                    'Webkul_Marketplace'
                ).'/view/base/web/images/marketplace/banner/sell-page-banner.png';
                $this->_filesystemFile->cp(
                    $marketplaceBannerImage,
                    $mediaMarketplaceBannerFullPath.'/sell-page-banner.png'
                );
            }

            $mediaMarketplaceIconFullPath = $filesystem->getDirectoryRead(
                \Magento\Framework\App\Filesystem\DirectoryList::MEDIA
            )->getAbsolutePath('marketplace/icon');
            if (!$this->_filesystemFile->fileExists($mediaMarketplaceIconFullPath)) {
                $this->_filesystemFile->mkdir($mediaMarketplaceIconFullPath, 0777, true);
                $icon1BannerImage = $reader->getModuleDir(
                    '',
                    'Webkul_Marketplace'
                ).'/view/base/web/images/marketplace/icon/icon-add-products.png';
                $this->_filesystemFile->cp(
                    $icon1BannerImage,
                    $mediaMarketplaceIconFullPath.'/icon-add-products.png'
                );

                $icon2BannerImage = $reader->getModuleDir(
                    '',
                    'Webkul_Marketplace'
                ).'/view/base/web/images/marketplace/icon/icon-collect-revenues.png';
                $this->_filesystemFile->cp(
                    $icon2BannerImage,
                    $mediaMarketplaceIconFullPath.'/icon-collect-revenues.png'
                );

                $icon3BannerImage = $reader->getModuleDir(
                    '',
                    'Webkul_Marketplace'
                ).'/view/base/web/images/marketplace/icon/icon-register-yourself.png';
                $this->_filesystemFile->cp(
                    $icon3BannerImage,
                    $mediaMarketplaceIconFullPath.'/icon-register-yourself.png'
                );

                $icon4BannerImage = $reader->getModuleDir(
                    '',
                    'Webkul_Marketplace'
                ).'/view/base/web/images/marketplace/icon/icon-start-selling.png';
                $this->_filesystemFile->cp(
                    $icon4BannerImage,
                    $mediaMarketplaceIconFullPath.'/icon-start-selling.png'
                );
            }

            $mediaPlaceholderFullPath = $filesystem->getDirectoryRead(
                \Magento\Framework\App\Filesystem\DirectoryList::MEDIA
            )->getAbsolutePath('placeholder');
            if (!$this->_filesystemFile->fileExists($mediaPlaceholderFullPath)) {
                $this->_filesystemFile->mkdir($mediaPlaceholderFullPath, 0777, true);
                $placeholderImage = $reader->getModuleDir(
                    '',
                    'Webkul_Marketplace'
                ).'/view/base/web/images/placeholder/image.jpg';
                $this->_filesystemFile->cp(
                    $placeholderImage,
                    $mediaMarketplaceIconFullPath.'/image.jpg'
                );
            }
        } catch (\Exception $e) {
            $this->_messageManager->addError($e->getMessage());
        }
    }

    private function createSellerPublicUrls($profileurl = '')
    {
        if ($profileurl) {
            $getCurrentStoreId = $this->_objectManager->create(
                'Webkul\Marketplace\Helper\Data'
            )->getCurrentStoreId();

            /*
            * Set Seller Profile Url
            */
            $sourceProfileUrl = 'marketplace/seller/profile/shop/'.$profileurl;
            $requestProfileUrl = $profileurl;
            /*
            * Check if already rexist in url rewrite model
            */
            $urlId = '';
            $profileRequestUrl = '';
            $urlCollectionData = $this->_objectManager->create(
                'Magento\UrlRewrite\Model\UrlRewrite'
            )
            ->getCollection()
            ->addFieldToFilter('target_path', $sourceProfileUrl)
            ->addFieldToFilter('store_id', $getCurrentStoreId);
            foreach ($urlCollectionData as $value) {
                $urlId = $value->getId();
                $profileRequestUrl = $value->getRequestPath();
            }
            if ($profileRequestUrl != $requestProfileUrl) {
                $idPath = rand(1, 100000);
                $this->_objectManager->create('Magento\UrlRewrite\Model\UrlRewrite')
                ->load($urlId)
                ->setStoreId($getCurrentStoreId)
                ->setIsSystem(0)
                ->setIdPath($idPath)
                ->setTargetPath($sourceProfileUrl)
                ->setRequestPath($requestProfileUrl)
                ->save();
            }

            /*
            * Set Seller Collection Url
            */
            $sourceCollectionUrl = 'marketplace/seller/collection/shop/'.$profileurl;
            $requestCollectionUrl = $profileurl.'/collection';
            /*
            * Check if already rexist in url rewrite model
            */
            $urlId = '';
            $collectionRequestUrl = '';
            $urlCollectionData = $this->_objectManager->create(
                'Magento\UrlRewrite\Model\UrlRewrite'
            )
            ->getCollection()
            ->addFieldToFilter('target_path', $sourceCollectionUrl)
            ->addFieldToFilter('store_id', $getCurrentStoreId);
            foreach ($urlCollectionData as $value) {
                $urlId = $value->getId();
                $collectionRequestUrl = $value->getRequestPath();
            }
            if ($collectionRequestUrl != $requestCollectionUrl) {
                $idPath = rand(1, 100000);
                $this->_objectManager->create('Magento\UrlRewrite\Model\UrlRewrite')
                ->load($urlId)
                ->setStoreId($getCurrentStoreId)
                ->setIsSystem(0)
                ->setIdPath($idPath)
                ->setTargetPath($sourceCollectionUrl)
                ->setRequestPath($requestCollectionUrl)
                ->save();
            }

            /*
            * Set Seller Feedback Url
            */
            $sourceFeedbackUrl = 'marketplace/seller/feedback/shop/'.$profileurl;
            $requestFeedbackUrl = $profileurl.'/feedback';
            /*
            * Check if already rexist in url rewrite model
            */
            $urlId = '';
            $feedbackRequestUrl = '';
            $urlFeedbackData = $this->_objectManager->create(
                'Magento\UrlRewrite\Model\UrlRewrite'
            )
            ->getCollection()
            ->addFieldToFilter('target_path', $sourceFeedbackUrl)
            ->addFieldToFilter('store_id', $getCurrentStoreId);
            foreach ($urlFeedbackData as $value) {
                $urlId = $value->getId();
                $feedbackRequestUrl = $value->getRequestPath();
            }
            if ($feedbackRequestUrl != $requestFeedbackUrl) {
                $idPath = rand(1, 100000);
                $this->_objectManager->create('Magento\UrlRewrite\Model\UrlRewrite')
                ->load($urlId)
                ->setStoreId($getCurrentStoreId)
                ->setIsSystem(0)
                ->setIdPath($idPath)
                ->setTargetPath($sourceFeedbackUrl)
                ->setRequestPath($requestFeedbackUrl)
                ->save();
            }

            /*
            * Set Seller Location Url
            */
            $sourceLocationUrl = 'marketplace/seller/location/shop/'.$profileurl;
            $requestLocationUrl = $profileurl.'/location';
            /*
            * Check if already rexist in url rewrite model
            */
            $urlId = '';
            $locationRequestUrl = '';
            $urlLocationData = $this->_objectManager->create(
                'Magento\UrlRewrite\Model\UrlRewrite'
            )
            ->getCollection()
            ->addFieldToFilter('target_path', $sourceLocationUrl)
            ->addFieldToFilter('store_id', $getCurrentStoreId);
            foreach ($urlLocationData as $value) {
                $urlId = $value->getId();
                $locationRequestUrl = $value->getRequestPath();
            }
            if ($locationRequestUrl != $requestLocationUrl) {
                $idPath = rand(1, 100000);
                $this->_objectManager->create('Magento\UrlRewrite\Model\UrlRewrite')
                ->load($urlId)
                ->setStoreId($getCurrentStoreId)
                ->setIsSystem(0)
                ->setIdPath($idPath)
                ->setTargetPath($sourceLocationUrl)
                ->setRequestPath($requestLocationUrl)
                ->save();
            }
        }
    }
}
