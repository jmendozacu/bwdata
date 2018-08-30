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
use Webkul\Marketplace\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Webkul\Marketplace\Model\Product as ProductStatus;
use Bakeway\ProductApi\Helper\Data as ProductApiHelper;
use Webkul\Marketplace\Helper\Data as MarketplaceHelper;
use Thai\S3\Model\MediaStorage\File\Storage\S3 as ThaiBucketStorage;
use Bakeway\OrderstatusEmail\Model\Email as OrderstatusEmail;
use \Magento\Framework\Exception\LocalizedException;
use Bakeway\Cities\Helper\Data as Citieshelper;
/**
 * Webkul Marketplace AdminhtmlCustomerSaveAfterObserver Observer.
 */
class AdminhtmlCustomerSaveAfterObserver implements ObserverInterface {

    /**
     * File Uploader factory.
     *
     * @var \Magento\MediaStorage\Model\File\UploaderFactory
     */
    protected $_fileUploaderFactory;

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
     * Store manager.
     *
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $_productRepository;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    private $_messageManager;
    protected $_mediaDirectory;

    /**
     * @var \Webkul\Marketplace\Model\ResourceModel\Product\Collection
     */
    protected $_sellerProduct;

    /**
     * @var \Magento\Framework\Json\DecoderInterface
     */
    protected $_jsonDecoder;

    /**
     * @var ProductApiHelper
     */
    protected $productApiHelper;

    /**
     * @var \Bakeway\CommissionLog\Model\CommissionLogFactory
     */
    protected $commissionLogFactory;

    /**
     * @var MarketplaceHelper
     */
    protected $marketplaceHelper;

    /**
     * @var ThaiBucketStorage
     */
    protected $thaiBucketStorage;

    /**
     * @var OrderstatusEmail
     */
    protected $orderstatusEmail;
    /**
     * @var \Bakeway\Partnerlocations\Helper\Data
     */
    protected $partnerLocationhelper;

    /**
     * @var Citieshelper
     */
    protected $citieshelper;

    /**
     * @var \Magento\Backend\Model\Auth\Session
     */
    protected $authSession;

    /**
     * AdminhtmlCustomerSaveAfterObserver constructor.
     * @param Filesystem $filesystem
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $date
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param CollectionFactory $collectionFactory
     * @param \Magento\MediaStorage\Model\File\UploaderFactory $fileUploaderFactory
     * @param ProductCollection $sellerProduct
     * @param \Magento\Framework\Json\DecoderInterface $jsonDecoder
     * @param ProductApiHelper $productApiHelper
     * @param \Bakeway\CommissionLog\Model\CommissionLogFactory $commissionLogFactory
     * @param MarketplaceHelper $marketplaceHelper
     * @param ThaiBucketStorage $thaiBucketStorage
     * @param OrderstatusEmail $orderstatusEmail
     * @param \Bakeway\Partnerlocations\Helper\Data $partnerLocationhelper
     * @param Citieshelper $citieshelper
     * @param \Magento\Backend\Model\Auth\Session $authSession
     */
    public function __construct(
        Filesystem $filesystem, \Magento\Framework\ObjectManagerInterface $objectManager, \Magento\Framework\Stdlib\DateTime\DateTime $date, \Magento\Framework\Message\ManagerInterface $messageManager, \Magento\Store\Model\StoreManagerInterface $storeManager, \Magento\Catalog\Api\ProductRepositoryInterface $productRepository, CollectionFactory $collectionFactory, \Magento\MediaStorage\Model\File\UploaderFactory $fileUploaderFactory, ProductCollection $sellerProduct, \Magento\Framework\Json\DecoderInterface $jsonDecoder, ProductApiHelper $productApiHelper, \Bakeway\CommissionLog\Model\CommissionLogFactory $commissionLogFactory, MarketplaceHelper $marketplaceHelper
        , ThaiBucketStorage $thaiBucketStorage,OrderstatusEmail $orderstatusEmail,
        \Bakeway\Partnerlocations\Helper\Data $partnerLocationhelper, Citieshelper $citieshelper,
        \Magento\Backend\Model\Auth\Session $authSession) {
        $this->_mediaDirectory = $filesystem->getDirectoryWrite(DirectoryList::MEDIA);
        $this->_fileUploaderFactory = $fileUploaderFactory;
        $this->_productRepository = $productRepository;
        $this->_objectManager = $objectManager;
        $this->_messageManager = $messageManager;
        $this->_collectionFactory = $collectionFactory;
        $this->_storeManager = $storeManager;
        $this->_date = $date;
        $this->_sellerProduct = $sellerProduct;
        $this->_jsonDecoder = $jsonDecoder;
        $this->productApiHelper = $productApiHelper;
        $this->commissionLogFactory = $commissionLogFactory;
        $this->marketplaceHelper = $marketplaceHelper;
        $this->thaiBucketStorage = $thaiBucketStorage;
        $this->orderstatusEmail = $orderstatusEmail;
        $this->partnerLocationhelper = $partnerLocationhelper;
        $this->citieshelper = $citieshelper;
        $this->authSession = $authSession;
    }

    /**
     * admin customer save after event handler.
     *
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer) {
        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/customer.log');
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);
        $this->moveDirToMediaDir();
        $customer = $observer->getCustomer();
        $customerid = $customer->getId();
        $postData = $observer->getRequest()->getPostValue();
        /**
         * Setting country to IN only
         */
        $postData['country_pic'] = 'IN';
        /**
         * Setting default city to pune
         */
        if (!isset($postData['store_city'])) {
            $postData['store_city'] = 1;
        }

        $_daysarray = "";
        /* get stored value of shop avaialiability */
        $avaCollection = $this->_objectManager->create(
            'Webkul\Marketplace\Model\Seller'
        )
            ->getCollection()
            ->addFieldToFilter('seller_id', $customerid)
            ->getLastItem();
        $_userdataod = $avaCollection['userdata_operational_days'];
        $_userdatasos = $avaCollection['userdata_shop_operatational_status'];
        $_userdatastuf = $avaCollection['userdata_shop_temporarily_u_from'];
        $_userdatastut = $avaCollection['userdata_shop_temporarily_u_to'];


        /* get stored value of shop avaialiability */
        if (isset($postData['userdata_operational_days'])):
            $_DaysVal = serialize($postData['userdata_operational_days']);
        elseif ($_userdataod):
            $_DaysVal = $_userdataod;
        else:
            $daysArr = [];
            for ($i = 1; $i <= 7; $i++):
                $daysArr[] = $i;
            endfor;
            $_DaysVal = serialize($daysArr);
        endif;


        if ($this->isSeller($customerid)) {
            list($data, $errors) = $this->validateprofiledata($observer);
            $productIds = isset($postData['sellerassignproid']) ?
                $postData['sellerassignproid'] : '';
            $sellerId = $customerid;
            if (isset($postData['is_seller_remove']) && $postData['is_seller_remove'] == true
            ) {
                $this->removePartner($sellerId);
                $this->_messageManager->addSuccess(
                    __('You removed the customer from seller.')
                );

                return $this;
            }
            if ($productIds != '' || $productIds != 0) {
                $this->assignProduct($sellerId, $productIds);
            }
            $collectionselect = $this->_objectManager->create(
                'Webkul\Marketplace\Model\Saleperpartner'
            )->getCollection()
                ->addFieldToFilter(
                    'seller_id', $sellerId
                );
            if ($collectionselect->getSize() == 1) {
                foreach ($collectionselect as $verifyrow) {
                    $autoid = $verifyrow->getEntityId();
                }

                $collectionupdate = $this->_objectManager->get(
                    'Webkul\Marketplace\Model\Saleperpartner'
                )->load($autoid);
                if (!isset($postData['commission'])) {
                    $postData['commission'] = $collectionupdate->getCommissionRate();
                }
                $collectionupdate->setCommissionRate($postData['commission']);
                $collectionupdate->save();
            } else {
                if (!isset($postData['commission'])) {
                    $postData['commission'] = 0;
                }
                $collectioninsert = $this->_objectManager->create(
                    'Webkul\Marketplace\Model\Saleperpartner'
                );
                $collectioninsert->setSellerId($sellerId);
                $collectioninsert->setCommissionRate($postData['commission']);
                $collectioninsert->save();
            }
            if (empty($errors)) {
                /**
                 * Image validations for banner and logo pic to 500KB
                 */
                $isSizeError = false;
                if (isset($_FILES['banner_pic'])) {
                    if ($_FILES['banner_pic']['size'] > 500000) {
                        $isSizeError = true;
                    }
                }
                if (isset($_FILES['logo_pic'])) {
                    if ($_FILES['logo_pic']['size'] > 500000) {
                        $isSizeError = true;
                    }
                }
                if ($isSizeError === true) {
                    throw new LocalizedException(__('Banner and logo pic should be less than or equals 500kb'));
                    return;
                }

                $target = $this->_mediaDirectory->getAbsolutePath('avatar/');
                $targetPaymentDoc = $this->_mediaDirectory->getAbsolutePath('payment_doc/');
                // upload logo file
                $postData['banner_pic'] = $this->uploadSellerProfileImage(
                    $target, 'banner_pic'
                );

                // upload logo file
                $postData['logo_pic'] = $this->uploadSellerProfileImage(
                    $target, 'logo_pic'
                );

                /*
                 * custom code start @kush
                 */
                //upload cheque image
                $postData['userdata_cancelled_cheque'] = $this->uploadSellerProfileImage(
                    $targetPaymentDoc, 'userdata_cancelled_cheque'
                );
                //agreement document
                $postData['userdata_agreement_document'] = $this->uploadSellerProfileImageDocument(
                    $targetPaymentDoc, 'userdata_agreement_document'
                );
                /*
                 * custom code end
                 */

                $autoId = '';
                $collection = $this->_objectManager->create(
                    'Webkul\Marketplace\Model\Seller'
                )
                    ->getCollection()
                    ->addFieldToFilter('seller_id', $sellerId);
                foreach ($collection as $value) {
                    $autoId = $value->getId();
                    $postData['banner_pic'] = $postData['banner_pic'] ?
                        $postData['banner_pic'] : $value->getBannerPic();
                    $postData['logo_pic'] = $postData['logo_pic'] ?
                        $postData['logo_pic'] : $value->getLogoPic();
                    $postData['userdata_cancelled_cheque'] = $postData['userdata_cancelled_cheque'] ?
                        $postData['userdata_cancelled_cheque'] : $value->getUserdataCancelledCheque();
                    $postData['userdata_agreement_document'] = $postData['userdata_agreement_document'] ?
                        $postData['userdata_agreement_document'] : $value->getUserdataAgreementDocument();
                }
                $value = $this->_objectManager->create(
                    'Webkul\Marketplace\Model\Seller'
                )->load($autoId);


                /*
                 * saving known for and Highlight fields
                 */

                $_KForVal = explode(",", $value->getKnownFor());

                $_SHighlightsVal = explode(",", $value->getStoreHighlights());

                if (isset($postData['known_for'])) {
                    $postData['known_for'] = $postData['known_for'];
                } elseif (!empty($_KForVal[0])) {
                    $postData['known_for'] = $_KForVal[0];
                } else {
                    $postData['known_for'] = "";
                }

                if (isset($postData['known_for_1'])) {
                    $postData['known_for_1'] = $postData['known_for_1'];
                } elseif (!empty($_KForVal[1])) {
                    $postData['known_for_1'] = $_KForVal[1];
                } else {
                    $postData['known_for_1'] = "";
                }

                if (isset($postData['known_for_2'])) {
                    $postData['known_for_2'] = $postData['known_for_2'];
                } elseif (!empty($_KForVal[2])) {
                    $postData['known_for_2'] = $_KForVal[2];
                } else {
                    $postData['known_for_2'] = "";
                }

                if (isset($postData['known_for_3'])) {
                    $postData['known_for_3'] = $postData['known_for_3'];
                } elseif (!empty($_KForVal[3])) {
                    $postData['known_for_3'] = $_KForVal[3];
                } else {
                    $postData['known_for_3'] = "";
                }

                if (isset($postData['known_for_4'])) {
                    $postData['known_for_4'] = $postData['known_for_4'];
                } elseif (!empty($_KForVal[4])) {
                    $postData['known_for_4'] = $_KForVal[4];
                } else {
                    $postData['known_for_4'] = "";
                }
                /*
                 * for highlights fields
                 */

                if (isset($postData['store_highlights'])) {
                    $postData['store_highlights'] = $postData['store_highlights'];
                } elseif (!empty($_SHighlightsVal[0])) {
                    $postData['store_highlights'] = $_SHighlightsVal[0];
                } else {
                    $postData['store_highlights'] = "";
                }

                if (isset($postData['store_highlights1'])) {
                    $postData['store_highlights1'] = $postData['store_highlights1'];
                } elseif (!empty($_SHighlightsVal[1])) {
                    $postData['store_highlights1'] = $_SHighlightsVal[1];
                } else {
                    $postData['store_highlights1'] = "";
                }
                if (isset($postData['store_highlights2'])) {
                    $postData['store_highlights2'] = $postData['store_highlights2'];
                } elseif (!empty($_SHighlightsVal[2])) {
                    $postData['store_highlights2'] = $_SHighlightsVal[2];
                } else {
                    $postData['store_highlights2'] = "";
                }


                if (isset($postData['store_highlights3'])) {
                    $postData['store_highlights3'] = $postData['store_highlights3'];
                } elseif (!empty($_SHighlightsVal[3])) {
                    $postData['store_highlights3'] = $_SHighlightsVal[3];
                } else {
                    $postData['store_highlights3'] = "";
                }


                if (isset($postData['store_highlights4'])) {
                    $postData['store_highlights4'] = $postData['store_highlights4'];
                } elseif (!empty($_SHighlightsVal[4])) {
                    $postData['store_highlights4'] = $_SHighlightsVal[4];
                } else {
                    $postData['store_highlights4'] = "";
                }

                $postKfval = array($postData['known_for'], $postData['known_for_1'], $postData['known_for_2']
                , $postData['known_for_3'], $postData['known_for_4']);
                $postHighlightsval = array($postData['store_highlights'], $postData['store_highlights1'], $postData['store_highlights2']
                , $postData['store_highlights3'], $postData['store_highlights4']);

                $_UpdateKnownFor = implode(",", $postKfval);
                $_UpdateHighlights = implode(",", $postHighlightsval);

                /*
                 * shop avaialbiity
                 */
                if (isset($postData['userdata_shop_temporarily_u_from'])):
                    $_FromDate = $postData['userdata_shop_temporarily_u_from'];
                else:
                    if (!empty($_userdatastuf)):
                        $_FromDate = $_userdatastuf;
                    else:
                        $_FromDate = "";
                    endif;


                endif;

                if (isset($postData['userdata_shop_temporarily_u_to'])):
                    $_ToDate = $postData['userdata_shop_temporarily_u_to'];
                else:

                    if (!empty($_userdatastuf)):
                        $_ToDate = $_userdatastut;
                    else:
                        $_ToDate = "";
                    endif;
                endif;


                if (isset($postData['userdata_shop_operatational_status'])):
                    $_OperaStatus = $postData['userdata_shop_operatational_status'];
                else:
                    if (!empty($_userdatasos)):
                        $_OperaStatus = $_userdatasos;
                    else:
                        $_OperaStatus = "";
                    endif;
                endif;
                /*
                 * operational days saving
                 * @start
                 */




                $oldBusinessName = $value->getData('business_name');
                $oldStoreCity = $value->getData('store_city');
                $oldStoreLocalityArea = $value->getData('store_locality_area');

                //echo "<pre>";print_r($value->getData());exit;

                $adminUser = $this->getCurrentAdminUser();
                $adminUsername = $adminUser->getUsername();
                $logger->info("Old Data before saving Customer by $adminUsername : ". $customerid);
                $oldCustomerEntireData = $value->getData();
                $logger->info($customerid."  :::  ".json_encode($oldCustomerEntireData));
                $logger->info("New Data for Customer edited by $adminUsername : ". $customerid);
                $logger->info($customerid."  :::  ".json_encode($postData));
                /*
                 * @end
                 */
                $value->addData($postData);
                $value->setIsSeller(1);
                $value->setUpdatedAt($this->_date->gmtDate());
                $value->setKnownFor($_UpdateKnownFor);
                $value->setStoreHighlights($_UpdateHighlights);
                $value->setUserdataOperationalDays($_DaysVal);
                $value->setUserdataShopTemporarilyUFrom($_FromDate);
                $value->setUserdataShopTemporarilyUTo($_ToDate);
                $value->setUserdataShopOperatationalStatus($_OperaStatus);
                $value->setBannerPic($postData['banner_pic']);
                $value->save();
                /* save commision log */

                $sellerCommissonLogRecord = $this->commissionLogFactory->create()
                    ->getCollection()
                    ->addFieldToFilter(
                        'seller_id', $sellerId
                    )
                    ->setOrder("created_at", "desc")
                    ->getFirstItem();
                $sellerbusinessName = $sellerCity = "";
                $_SellerCommModel = $this->commissionLogFactory->create();
                $_SellerCommModel->setSellerId($sellerId);
                $_SellerCommModel->setCreatedAt($this->_date->gmtDate());
                $_SellerCommModel->setCreatedBy($this->getCurrentadminemail());
                $sellerName = $customer->getFirstName()." ".$customer->getLastName();
                $existingCommision = $sellerCommissonLogRecord->getCommissionRate();
                $sellerbusinessName = $value->getData('business_name');
                $sellerCity = $this->citieshelper->getCityNameById($value->getData('store_city'));

                if (!empty($existingCommision)):
                    if ($existingCommision !== number_format($postData['commission'], 4)):
                        $_SellerCommModel->setCommissionRate($postData['commission']);
                        $_SellerCommModel->save();
                        /*code for commision log email trigger*/
                        $this->orderstatusEmail->sendCommissionLogEmail($postData['commission'],$this->getCurrentadminemail(),$sellerName,$this->_date->gmtDate(),$sellerId, $sellerbusinessName,$sellerCity);

                    endif;
                else:
                    $_SellerCommModel->setCommissionRate($postData['commission']);
                    $_SellerCommModel->save();
                    $this->orderstatusEmail->sendCommissionLogEmail($postData['commission'],$this->getCurrentadminemail(),$sellerName,$this->_date->gmtDate(),$sellerId,$sellerbusinessName,$sellerCity);
                endif;
                /* end */
                if (isset($postData['seller_category_ids'])) {
                    $catIds = '';
                    foreach ($postData['seller_category_ids'] as $categoryId => $selected) {
                        if ($selected) {
                            $catIds = $catIds . $categoryId . ',';
                        }
                    }
                    $catIds = rtrim($catIds, ',');
                    $value->setAllowedCategories($catIds);
                }
                if (isset($postData['company_description'])) {
                    $postData['company_description'] = str_replace(
                        'script', '', $postData['company_description']
                    );
                    $value->setCompanyDescription(
                        $postData['company_description']
                    );
                }

                if (isset($postData['return_policy'])) {
                    $postData['return_policy'] = str_replace(
                        'script', '', $postData['return_policy']
                    );
                    $value->setReturnPolicy($postData['return_policy']);
                }

                if (isset($postData['shipping_policy'])) {
                    $postData['shipping_policy'] = str_replace(
                        'script', '', $postData['shipping_policy']
                    );
                    $value->setShippingPolicy($postData['shipping_policy']);
                }

                if (isset($postData['meta_description'])) {
                    $value->setMetaDescription($postData['meta_description']);
                }

                /**
                 * set taxvat number for seller
                 */
                if (isset($postData['taxvat'])) {
                    $customer = $this->_objectManager->create(
                        'Magento\Customer\Model\Customer'
                    )->load($sellerId);
                    $customer->setTaxvat($postData['taxvat']);
                    $customer->setId($sellerId)->save();
                }

                if (array_key_exists('country_pic', $postData)) {
                    $value->setCountryPic($postData['country_pic']);
                }

                $value->save();

                /**
                 * Saving contract start and end dates.
                 */
                $contractId = '';
                $contractCollection = $this->_objectManager->create(
                    'Bakeway\Vendorcontract\Model\Vendorcontract'
                )
                    ->getCollection()
                    ->addFieldToFilter('seller_id', $sellerId);
                foreach ($contractCollection as $contract) {
                    $contractId = $contract->getId();
                }

                if ($contractId != '') {
                    $contractObj = $this->_objectManager->create(
                        'Bakeway\Vendorcontract\Model\Vendorcontract'
                    )->load($contractId);
                } else {
                    $contractObj = $this->_objectManager->create(
                        'Bakeway\Vendorcontract\Model\Vendorcontract'
                    );
                }
                if (isset($postData['contract_start_date']) && isset($postData['contract_end_date'])) {

                    $contractObj->setSellerId($sellerId);
                    $contractObj->setStartDate($postData['contract_start_date']);
                    $contractObj->setEndDate($postData['contract_end_date']);
                    $contractObj->save();
                }

                /**
                 * Check if bakery is live ready
                 */
                $inContract = $this->marketplaceHelper->getIsInContract($customerid);
                $businessName = $value->getData('business_name');
                $countryPic = $value->getData('country_pic');
                $storeZipcode = $value->getData('store_zipcode');
                /*
                 * store address count
                 */
                $storeLocalityArea =  $storeCity = "";
                $storeLocalityvalue = $this->partnerLocationhelper->getSingleLocality($customerid);
                if(!empty($storeLocalityvalue)){
                    $storeLocalityArea = $storeLocalityvalue;
                }

                $storeCityvalue = $this->partnerLocationhelper->getSinglelocalitycity($customerid);
                if(!empty($storeCityvalue)){
                    $storeCity = $storeCityvalue;
                }

                $isConglomerate = $value->getData('is_conglomerate');
                if (
                    isset($businessName) && trim($businessName) != '' &&
                    isset($countryPic) && trim($countryPic) == 'IN' &&
                    isset($storeZipcode) && trim($storeZipcode) != '' &&
                    isset($storeLocalityArea) && trim($storeLocalityArea) != '' &&
                    isset($storeCity) && $storeCity != '' &&
                    $inContract === true
                ) {
                    $value->setData('is_live_ready', 1);

                } else {
                    $value->setData('is_live_ready', 0);
                }
                $value->save();

                /**
                 * Generate SEO URLS
                 * Check if business name changed
                 * Setting the vendor url
                 */
                if (isset($postData['business_name']) || isset($postData['store_city']) || isset($postData['store_locality_area'])) {
                    $newBusinessName = $oldBusinessName;
                    $newStoreCity = $oldStoreCity;

                    if (isset($postData['business_name']) && !empty($postData['business_name'])) {
                        $newBusinessName = $postData['business_name'];
                    }

                    if (isset($postData['store_city']) && !empty($postData['store_city'])) {
                        $newStoreCity = $postData['store_city'];
                    }

                    if (($oldBusinessName != $newBusinessName) || ($oldStoreCity != $newStoreCity)) {
                        $this->productApiHelper->createVendorUrl($sellerId);
                        $this->productApiHelper->createSellerAllProductUrls($sellerId);
                    }
                }
            }
        } else {

            if (isset($postData['is_seller_add'])) {
                $isSellerAdd = $postData['is_seller_add'];
                $profileurl = $postData['profileurl'];
            } else {
                $isSellerAdd = false;
            }

            /*
             * saving known for and Highlight fields
             */
            if ($isSellerAdd == false) {
                if(isset($postData['known_for']) || isset($postData['known_for_1']) || isset($postData['known_for_2']) || isset($postData['known_for_3']) ||
                    isset($postData['known_for_4'])) {
                    $postData['known_for'] = array($postData['known_for'], $postData['known_for_1'], $postData['known_for_2']
                    , $postData['known_for_3'], $postData['known_for_4']);
                }

                if(isset($postData['store_highlights']) || isset($postData['store_highlights1']) || isset($postData['store_highlights2']) || isset($postData['store_highlights3']) ||
                    isset($postData['store_highlights4'])) {
                    $postData['store_highlights'] = array($postData['store_highlights'], $postData['store_highlights1'], $postData['store_highlights2']
                    , $postData['store_highlights3'], $postData['store_highlights4']);
                    $postData['store_highlights'] = implode(",", $postData['store_highlights']);
                }
            }

            /*
             * saving known for and Highlight fields
             */
            if ($isSellerAdd == true) {
                if ($profileurl != '') {
                    $profileurlcount = $this->_objectManager->create(
                        'Webkul\Marketplace\Model\Seller'
                    )->getCollection();
                    $profileurlcount->addFieldToFilter('shop_url', $profileurl);
                    $sellerProfileId = 0;
                    $sellerProfileUrl = '';
                    $collectionselect = $this->_objectManager->create(
                        'Webkul\Marketplace\Model\Seller'
                    )->getCollection();
                    $collectionselect->addFieldToFilter('seller_id', $customerid);
                    foreach ($collectionselect as $coll) {
                        $sellerProfileId = $coll->getEntityId();
                        $sellerProfileUrl = $coll->getShopUrl();
                    }
                    if ($profileurlcount->getSize() && ($profileurl != $sellerProfileUrl)) {
                        $this->_messageManager->addError(
                            __('This Shop URL already Exists.')
                        );
                    } else {
                        $collection = $this->_objectManager->get(
                            'Webkul\Marketplace\Model\Seller'
                        )->load($sellerProfileId);
                        $collection->setIsSeller(1);
                        $collection->setStoreCity($postData['store_city']); //Setting default city to 1 = Pune
                        $collection->setShopUrl($profileurl);
                        $collection->setSellerId($customerid);
                        $collection->setCreatedAt($this->_date->gmtDate());
                        $collection->setUpdatedAt($this->_date->gmtDate());
                        $collection->save();

                        $helper = $this->_objectManager->get(
                            'Webkul\Marketplace\Helper\Data'
                        );
                        $adminStoreEmail = $helper->getAdminEmailId();
                        $adminEmail = $adminStoreEmail ? $adminStoreEmail :
                            $helper->getDefaultTransEmailId();
                        $adminUsername = 'Admin';

                        $seller = $this->_objectManager->get(
                            'Magento\Customer\Model\Customer'
                        )->load($customerid);

                        $emailTempVariables['myvar1'] = $seller->getName();
                        $emailTempVariables['myvar2'] = $this->_storeManager
                            ->getStore()->getUrl(
                                'customer/account/login'
                            );
                        $senderInfo = [
                            'name' => $adminUsername,
                            'email' => $adminEmail,
                        ];
                        $receiverInfo = [
                            'name' => $seller->getName(),
                            'email' => $seller->getEmail(),
                        ];
                        $this->_objectManager->create(
                            'Webkul\Marketplace\Helper\Email'
                        )->sendSellerApproveMail(
                            $emailTempVariables, $senderInfo, $receiverInfo
                        );
                        $this->_messageManager->addSuccess(
                            __('You created the customer as seller.')
                        );
                    }
                } else {
                    $this->_messageManager->addError(
                        __('Enter Shop Name of Customer.')
                    );
                }
            }
        }

        return $this;
    }

    /**
     * Upload Seller Profile Image.
     *
     * @param string $target
     * @param string $fileName
     *
     * @return string
     */
    protected function uploadSellerProfileImage($target, $fileName) {
        try {
            /** @var $uploader \Magento\MediaStorage\Model\File\Uploader */
            $uploader = $this->_fileUploaderFactory->create(
                ['fileId' => $fileName]
            );
            $uploader->setAllowedExtensions(
                ['jpg', 'jpeg', 'gif', 'png']
            );
            $uploader->setAllowRenameFiles(true);
            $resul = $uploader->save($target);

            if ($resul['file']) {
                return $resul['file'];
            }
            $this->thaiBucketStorage->saveFile($target.$resul['file']);
        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * Upload Seller Agreement Document Upload.
     *
     * @param string $target
     * @param string $fileName
     *
     * @return string
     */
    protected function uploadSellerProfileImageDocument($target, $fileName) {
        try {
            /** @var $uploader \Magento\MediaStorage\Model\File\Uploader */
            $uploader = $this->_fileUploaderFactory->create(
                ['fileId' => $fileName]
            );
            $uploader->setAllowedExtensions(
                ['jpg', 'jpeg', 'gif', 'png', 'pdf', 'xls' ,'csv','xml' ,'doc' ,'docx']
            );
            $uploader->setAllowRenameFiles(true);
            $resul = $uploader->save($target);
            if ($resul['file']) {
                return $resul['file'];
            }
            $this->thaiBucketStorage->saveFile($target.$resul['file']);
        } catch (\Exception $e) {
            return '';
        }
    }

    public function isSeller($customerid) {
        $sellerStatus = 0;
        $model = $this->_objectManager->create(
            'Webkul\Marketplace\Model\Seller'
        )
            ->getCollection()
            ->addFieldToFilter('seller_id', $customerid);
        foreach ($model as $value) {
            $sellerStatus = $value->getIsSeller();
        }

        return $sellerStatus;
    }

    private function validateprofiledata($observer) {
        $errors = [];
        $data = [];
        $paramData = $observer->getRequest()->getParams();
        foreach ($paramData as $code => $value) {
            switch ($code) :
                case 'twitter_id':
                    if (trim($value) != '' && preg_match('/[\'^�$%&*()}{@#~?><>, |=_+�-]/', $value)) {
                        $errors[] = __(
                            'Twitterid cannot contain space and special characters'
                        );
                    } else {
                        $data[$code] = $value;
                    }
                    break;
                case 'facebook_id':
                    if (trim($value) != '' && preg_match('/[\'^�$%&*()}{@#~?><>, |=_+�-]/', $value)) {
                        $errors[] = __(
                            'Facebookid cannot contain space and special characters'
                        );
                    } else {
                        $data[$code] = $value;
                    }
            endswitch;
        }

        return [$data, $errors];
    }

    private function removePartner($sellerId) {
        $collectionselectdelete = $this->_objectManager->create(
            'Webkul\Marketplace\Model\Seller'
        )->getCollection();
        $collectionselectdelete->addFieldToFilter(
            'seller_id', $sellerId
        );
        foreach ($collectionselectdelete as $delete) {
            $autoid = $delete->getEntityId();
        }
        $collectiondelete = $this->_objectManager->get(
            'Webkul\Marketplace\Model\Seller'
        )->load($autoid);
        $collectiondelete->delete();
        //Set Produt status disabled
        $sellerProduct = $this->_objectManager->create(
            'Webkul\Marketplace\Model\Product'
        )
            ->getCollection()
            ->addFieldToFilter(
                'seller_id', $sellerId
            );

        foreach ($sellerProduct as $productInfo) {
            $allStores = $this->_storeManager->getStores();
            foreach ($allStores as $_eachStoreId => $val) {
                $product = $this->_productRepository->getById(
                    $productInfo->getMageproductId()
                );
                $product->setStatus(
                    ProductStatus::STATUS_DISABLED
                );
                $this->_productRepository->save($product);
            }

            $productInfo->setStatus(0);
            $productInfo->save();
        }

        $helper = $this->_objectManager->get(
            'Webkul\Marketplace\Helper\Data'
        );
        $adminStoreEmail = $helper->getAdminEmailId();
        $adminEmail = $adminStoreEmail ?
            $adminStoreEmail : $helper->getDefaultTransEmailId();
        $adminUsername = 'Admin';

        $seller = $this->_objectManager->get(
            'Magento\Customer\Model\Customer'
        )->load($sellerId);

        $emailTempVariables['myvar1'] = $seller->getName();
        $emailTempVariables['myvar2'] = $this->_storeManager->getStore()
            ->getUrl(
                'customer/account/login'
            );
        $senderInfo = [
            'name' => $adminUsername,
            'email' => $adminEmail,
        ];
        $receiverInfo = [
            'name' => $seller->getName(),
            'email' => $seller->getEmail(),
        ];
        $this->_objectManager->create(
            'Webkul\Marketplace\Helper\Email'
        )->sendSellerDisapproveMail(
            $emailTempVariables, $senderInfo, $receiverInfo
        );
    }

    public function assignProduct($sellerId, $productIds) {
        $productids = array_flip($this->_jsonDecoder->decode($productIds));

        // get all assign products
        $assignProductIds = $this->_sellerProduct->getAllAssignProducts(
            '`adminassign`= 1 AND `seller_id`=' . $sellerId
        );

        // set product status to 2 to unassign products from seller
        $coditionArr = [];
        foreach ($assignProductIds as $key => $id) {
            $condition = '`mageproduct_id`=' . $id;
            array_push($coditionArr, $condition);
        }
        if (count($coditionArr)) {
            $coditionData = implode(' OR ', $coditionArr);

            $this->_sellerProduct->setProductData(
                $coditionData, ['adminassign' => 2]
            );
        }

        // set product status to 1 to assign selected products from seller
        $productCollection = $this->_objectManager->create(
            'Magento\Catalog\Model\Product'
        )->getCollection()
            ->addFieldToFilter(
                'entity_id', ['in' => $productids]
            );
        $successMessage = '';
        foreach ($productCollection as $product) {
            $proid = $product->getID();
            $userid = '';
            $collection = $this->_objectManager->create(
                'Webkul\Marketplace\Model\Product'
            )->getCollection()
                ->addFieldToFilter(
                    'mageproduct_id', $proid
                );
            $flag = 1;
            foreach ($collection as $coll) {
                $flag = 0;
                if ($sellerId != $coll['seller_id']) {
                    $this->_messageManager->addError(
                        __('The product with id %1 is already assigned to other seller.', $proid)
                    );
                } else {
                    $coll->setAdminassign(1)->save();
                }
            }
            if ($flag) {
                $collection1 = $this->_objectManager->create(
                    'Webkul\Marketplace\Model\Product'
                );
                $collection1->setMageproductId($proid);
                $collection1->setSellerId($sellerId);
                $collection1->setStatus($product->getStatus());
                $collection1->setAdminassign(1);
                $collection1->setCreatedAt($this->_date->gmtDate());
                $collection1->setUpdatedAt($this->_date->gmtDate());
                $collection1->save();

                /**
                 * Assigning bakeway sku to product after assignment
                 * Setting SKU for Bakeway
                 */
                $product->setSku($sellerId . "-" . $proid);

                /**
                 * Setting the product url
                 */
                $urlKey = $this->productApiHelper->createProductUrl($proid);
                $product->setUrlKey($urlKey);

                $product->save();

                $successMessage = __(
                    'Products has been successfully assigned to seller.'
                );
            }
        }

        // remove unassign products from seller
        $unassignProductIds = $this->_sellerProduct->getAllAssignProducts(
            '`adminassign`= 2 AND `seller_id`=' . $sellerId
        );
        $this->unassignProduct($sellerId, $unassignProductIds);

        if ($successMessage) {
            $this->_messageManager->addSuccess($successMessage);
        }
    }

    public function unassignProduct($sellerId, $productIds) {
        $productids = $productIds;
        $collection = $this->_objectManager->create(
            'Webkul\Marketplace\Model\Product'
        )->getCollection()
            ->addFieldToFilter(
                'mageproduct_id', ['in' => $productids]
            )
            ->addFieldToFilter(
                'seller_id', $sellerId
            );
        foreach ($collection as $coll) {
            $coll->delete();
        }
    }

    private function moveDirToMediaDir($value = '') {
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
            if (!file_exists($mediaAvatarFullPath)) {
                mkdir($mediaAvatarFullPath, 0777, true);
                $avatarBannerImage = $reader->getModuleDir(
                        '', 'Webkul_Marketplace'
                    ) . '/view/base/web/images/avatar/banner-image.png';
                copy($avatarBannerImage, $mediaAvatarFullPath . '/banner-image.png');
                $avatarNoImage = $reader->getModuleDir(
                        '', 'Webkul_Marketplace'
                    ) . '/view/base/web/images/avatar/noimage.png';
                copy($avatarNoImage, $mediaAvatarFullPath . '/noimage.png');
            }

            $mediaMarketplaceFullPath = $filesystem->getDirectoryRead(
                \Magento\Framework\App\Filesystem\DirectoryList::MEDIA
            )->getAbsolutePath('marketplace');
            if (!file_exists($mediaMarketplaceFullPath)) {
                mkdir($mediaMarketplaceFullPath, 0777, true);
            }

            $mediaMarketplaceBannerFullPath = $filesystem->getDirectoryRead(
                \Magento\Framework\App\Filesystem\DirectoryList::MEDIA
            )->getAbsolutePath('marketplace/banner');
            if (!file_exists($mediaMarketplaceBannerFullPath)) {
                mkdir($mediaMarketplaceBannerFullPath, 0777, true);
                $marketplaceBannerImage = $reader->getModuleDir(
                        '', 'Webkul_Marketplace'
                    ) . '/view/base/web/images/marketplace/banner/sell-page-banner.png';
                copy(
                    $marketplaceBannerImage, $mediaMarketplaceBannerFullPath . '/sell-page-banner.png'
                );
            }

            $mediaMarketplaceIconFullPath = $filesystem->getDirectoryRead(
                \Magento\Framework\App\Filesystem\DirectoryList::MEDIA
            )->getAbsolutePath('marketplace/icon');
            if (!file_exists($mediaMarketplaceIconFullPath)) {
                mkdir($mediaMarketplaceIconFullPath, 0777, true);
                $icon1BannerImage = $reader->getModuleDir(
                        '', 'Webkul_Marketplace'
                    ) . '/view/base/web/images/marketplace/icon/icon-add-products.png';
                copy(
                    $icon1BannerImage, $mediaMarketplaceIconFullPath . '/icon-add-products.png'
                );

                $icon2BannerImage = $reader->getModuleDir(
                        '', 'Webkul_Marketplace'
                    ) . '/view/base/web/images/marketplace/icon/icon-collect-revenues.png';
                copy(
                    $icon2BannerImage, $mediaMarketplaceIconFullPath . '/icon-collect-revenues.png'
                );

                $icon3BannerImage = $reader->getModuleDir(
                        '', 'Webkul_Marketplace'
                    ) . '/view/base/web/images/marketplace/icon/icon-register-yourself.png';
                copy(
                    $icon3BannerImage, $mediaMarketplaceIconFullPath . '/icon-register-yourself.png'
                );

                $icon4BannerImage = $reader->getModuleDir(
                        '', 'Webkul_Marketplace'
                    ) . '/view/base/web/images/marketplace/icon/icon-start-selling.png';
                copy(
                    $icon4BannerImage, $mediaMarketplaceIconFullPath . '/icon-start-selling.png'
                );
            }

            $mediaPlaceholderFullPath = $filesystem->getDirectoryRead(
                \Magento\Framework\App\Filesystem\DirectoryList::MEDIA
            )->getAbsolutePath('placeholder');
            if (!file_exists($mediaPlaceholderFullPath)) {
                mkdir($mediaPlaceholderFullPath, 0777, true);
                $placeholderImage = $reader->getModuleDir(
                        '', 'Webkul_Marketplace'
                    ) . '/view/base/web/images/placeholder/image.jpg';
                copy(
                    $placeholderImage, $mediaPlaceholderFullPath . '/image.jpg'
                );
            }
        } catch (\Exception $e) {
            $this->_messageManager->addError($e->getMessage());
        }
    }

    public function getCurrentadminemail() {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $obj = $objectManager->create('Magento\Backend\App\Action\Context');

        $loginUserEmail = $obj->getAuth()->getUser()->getUsername();

        if (!empty($loginUserEmail)) {
            return $loginUserEmail;
        }
        return;
    }


    public function getCurrentAdminUser()
    {
        return $this->authSession->getUser();
    }

}
