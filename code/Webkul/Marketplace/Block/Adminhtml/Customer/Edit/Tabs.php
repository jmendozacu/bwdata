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

namespace Webkul\Marketplace\Block\Adminhtml\Customer\Edit;

use Magento\Customer\Controller\RegistryConstants;
use Magento\Ui\Component\Layout\Tabs\TabInterface;
use Magento\Backend\Block\Widget\Form;
use Magento\Backend\Block\Widget\Form\Generic;
use Bakeway\Cities\Helper\Data as BakewayCitiesHelper;

/**
 * Customer Seller form block.
 */
class Tabs extends Generic implements TabInterface {

    /**
     * @var \Magento\Store\Model\System\Store
     */
    protected $_systemStore;

    /**
     * @var ObjectManagerInterface
     */
    protected $_objectManager;
    protected $_dob = null;

    /**
     * Core registry.
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry;

    /**
     * @var Magento\Directory\Model\ResourceModel\Country\CollectionFactory
     */
    protected $_country;

    /**
     * @var Webkul\Marketplace\Helper\Data
     */
    protected $_marketplaceHelper;

    /**
     * @var BakewayCitiesHelper
     */
    protected $_bakewayCitiesHelper;

    /**
     * @var \Magento\Cms\Model\Page 
     */
    protected $cmsPage;

    /**
     * Tabs constructor.
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param \Magento\Store\Model\System\Store $systemStore
     * @param \Magento\Directory\Model\ResourceModel\Country\Collection $country
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param \Webkul\Marketplace\Helper\Data $marketplaceHelper
     * @param BakewayCitiesHelper $bakewayCitiesHelper
     * @param array $data
     */
    public function __construct(
    \Magento\Backend\Block\Template\Context $context, \Magento\Framework\Registry $registry, \Magento\Framework\Data\FormFactory $formFactory, \Magento\Store\Model\System\Store $systemStore, \Magento\Directory\Model\ResourceModel\Country\Collection $country, \Magento\Framework\ObjectManagerInterface $objectManager, \Webkul\Marketplace\Helper\Data $marketplaceHelper, BakewayCitiesHelper $bakewayCitiesHelper, \Magento\Cms\Model\Page $cmsPage, array $data = []
    ) {
        $this->_coreRegistry = $registry;
        $this->_systemStore = $systemStore;
        $this->_objectManager = $objectManager;
        $this->_country = $country;
        $this->_marketplaceHelper = $marketplaceHelper;
        $this->_bakewayCitiesHelper = $bakewayCitiesHelper;
        $this->cmsPage = $cmsPage;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    /**
     * @return string|null
     */
    public function getCustomerId() {
        return $this->_coreRegistry->registry(
                        RegistryConstants::CURRENT_CUSTOMER_ID
        );
    }

    /**
     * @return \Magento\Framework\Phrase
     */
    public function getTabLabel() {
        return __('Seller Account Information');
    }

    /**
     * @return \Magento\Framework\Phrase
     */
    public function getTabTitle() {
        return __('Seller Account Information');
    }

    /**
     * @return bool
     */
    public function canShowTab() {
        $coll = $this->_objectManager->create(
                        'Webkul\Marketplace\Block\Adminhtml\Customer\Edit'
                )->getMarketplaceUserCollection();
        $isSeller = false;
        foreach ($coll as $row) {
            $isSeller = $row->getIsSeller();
        }
        if ($this->getCustomerId() && $isSeller) {
            return true;
        }

        return false;
    }

    /**
     * @return bool
     */
    public function isHidden() {
        $coll = $this->_objectManager->create(
                        'Webkul\Marketplace\Block\Adminhtml\Customer\Edit'
                )->getMarketplaceUserCollection();
        $isSeller = false;
        foreach ($coll as $row) {
            $isSeller = $row->getIsSeller();
        }
        if ($this->getCustomerId() && $isSeller) {
            return false;
        }

        return true;
    }

    /**
     * Tab class getter.
     *
     * @return string
     */
    public function getTabClass() {
        return '';
    }

    /**
     * Return URL link to Tab content.
     *
     * @return string
     */
    public function getTabUrl() {
        return '';
    }

    /**
     * Tab should be loaded trough Ajax call.
     *
     * @return bool
     */
    public function isAjaxLoaded() {
        return false;
    }

    public function initForm() {
        if (!$this->canShowTab()) {
            return $this;
        }
        /*         * @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create();
        $form->setHtmlIdPrefix('marketplace_');
        $customerId = $this->_coreRegistry->registry(
                RegistryConstants::CURRENT_CUSTOMER_ID
        );
        $storeid = $this->_storeManager->getStore()->getId();

        $fieldset = $form->addFieldset(
                'base_fieldset', ['legend' => __('Seller Profile Information')]
        );
        $customer = $this->_objectManager->create(
                        'Magento\Customer\Model\Customer'
                )->load($customerId);
        $partner = $this->_objectManager->create(
                        'Webkul\Marketplace\Block\Adminhtml\Customer\Edit'
                )->getSellerInfoCollection();
        $contract = $this->_objectManager->create(
                        'Webkul\Marketplace\Block\Adminhtml\Customer\Edit'
                )->getContractInfoCollection();
        $twAactive = '';
        $fbAactive = '';
        $gplusActive = '';
        $instagramActive = '';
        $youtubeActive = '';
        $vimeoActive = '';
        $pinterestActive = '';
        $moleskineActive = '';

        /* if ($partner['tw_active'] == 1) {
          $twAactive = "value='1' checked='checked'";
          }
          if ($partner['fb_active'] == 1) {
          $fbAactive = "value='1' checked='checked'";
          }
          if ($partner['gplus_active'] == 1) {
          $gplusActive = "value='1' checked='checked'";
          }
          if ($partner['instagram_active'] == 1) {
          $instagramActive = "value='1' checked='checked'";
          }
          if ($partner['youtube_active'] == 1) {
          $youtubeActive = "value='1' checked='checked'";
          }
          if ($partner['vimeo_active'] == 1) {
          $vimeoActive = "value='1' checked='checked'";
          }
          if ($partner['pinterest_active'] == 1) {
          $pinterestActive = "value='1' checked='checked'";
          }
          if ($partner['moleskine_active'] == 1) {
          $moleskineActive = "value='1' checked='checked'";
          }
          $fieldset->addField(
          'twitter_id',
          'text',
          [
          'name' => 'twitter_id',
          'data-form-part' => $this->getData('target_form'),
          'label' => __('Twitter ID'),
          'title' => __('Twitter ID'),
          'value' => $partner['twitter_id'],
          'after_element_html' => '<input
          type="checkbox"
          name="tw_active"
          data-form-part="customer_form"
          onchange="this.value = this.checked ? 1 : 0;"
          title="'.__('Allow to Display Twitter Icon in Profile Page').'"
          '.$twAactive.'
          >',
          ]
          );
          $fieldset->addField(
          'facebook_id',
          'text',
          [
          'name' => 'facebook_id',
          'data-form-part' => $this->getData('target_form'),
          'label' => __('Facebook ID'),
          'title' => __('Facebook ID'),
          'value' => $partner['facebook_id'],
          'after_element_html' => '<input
          type="checkbox"
          name="fb_active"
          data-form-part="customer_form"
          onchange="this.value = this.checked ? 1 : 0;"
          title="'.__('Allow to Display Facebook Icon in Profile Page').'"
          '.$fbAactive.'
          >',
          ]
          );
          $fieldset->addField(
          'instagram_id',
          'text',
          [
          'name' => 'instagram_id',
          'data-form-part' => $this->getData('target_form'),
          'label' => __('Instagram ID'),
          'title' => __('Instagram ID'),
          'value' => $partner['instagram_id'],
          'after_element_html' => '<input
          type="checkbox"
          name="instagram_active"
          data-form-part="customer_form"
          onchange="this.value = this.checked ? 1 : 0;"
          title="'.__('Allow to Display Instagram Icon in Profile Page').'"
          '.$instagramActive.'
          >',
          ]
          );
          $fieldset->addField(
          'gplus_id',
          'text',
          [
          'name' => 'gplus_id',
          'data-form-part' => $this->getData('target_form'),
          'label' => __('Google+ ID'),
          'title' => __('Google+ ID'),
          'value' => $partner['gplus_id'],
          'after_element_html' => '<input
          type="checkbox"
          name="gplus_active"
          data-form-part="customer_form"
          onchange="this.value = this.checked ? 1 : 0;"
          title="'.__('Allow to Display Google+ Icon in Profile Page').'"
          '.$gplusActive.'
          >',
          ]
          );
          $fieldset->addField(
          'youtube_id',
          'text',
          [
          'name' => 'youtube_id',
          'data-form-part' => $this->getData('target_form'),
          'label' => __('Youtube ID'),
          'title' => __('Youtube ID'),
          'value' => $partner['youtube_id'],
          'after_element_html' => '<input
          type="checkbox"
          name="youtube_active"
          data-form-part="customer_form"
          onchange="this.value = this.checked ? 1 : 0;"
          title="'.__('Allow to Display Youtube Icon in Profile Page').'"
          '.$youtubeActive.'
          >',
          ]
          );
          $fieldset->addField(
          'vimeo_id',
          'text',
          [
          'name' => 'vimeo_id',
          'data-form-part' => $this->getData('target_form'),
          'label' => __('Vimeo ID'),
          'title' => __('Vimeo ID'),
          'value' => $partner['vimeo_id'],
          'after_element_html' => '<input
          type="checkbox"
          name="vimeo_active"
          data-form-part="customer_form"
          onchange="this.value = this.checked ? 1 : 0;"
          title="'.__('Allow to Display Vimeo Icon in Profile Page').'"
          '.$vimeoActive.'
          >',
          ]
          );
          $fieldset->addField(
          'pinterest_id',
          'text',
          [
          'name' => 'pinterest_id',
          'data-form-part' => $this->getData('target_form'),
          'label' => __('Pinterest ID'),
          'title' => __('Pinterest ID'),
          'value' => $partner['pinterest_id'],
          'after_element_html' => '<input
          type="checkbox"
          name="pinterest_active"
          data-form-part="customer_form"
          onchange="this.value = this.checked ? 1 : 0;"
          title="'.__('Allow to Display Pinterest Icon in Profile Page').'"
          '.$pinterestActive.'
          >',
          ]
          );
          $fieldset->addField(
          'moleskine_id',
          'text',
          [
          'name' => 'moleskine_id',
          'data-form-part' => $this->getData('target_form'),
          'label' => __('Moleskine ID'),
          'title' => __('Moleskine ID'),
          'value' => $partner['moleskine_id'],
          'after_element_html' => '<input
          type="checkbox"
          name="moleskine_active"
          data-form-part="customer_form"
          onchange="this.value = this.checked ? 1 : 0;"
          title="'.__('Allow to Display Moleskine Icon in Profile Page').'"
          '.$moleskineActive.'
          >',
          ]
          ); */

        $fieldset->addField(
            'order_alarm', 'select', [
                'name' => 'order_alarm',
                'data-form-part' => $this->getData('target_form'),
                'label' => __('Seller Order Alarm Status'),
                'title' => __('Seller Order Alarm Status'),
                'value' =>  $partner['order_alarm'],
                'values' => $this->cmsPage->getAvailableStatuses(),
            ]
        );



        if (isset($partner['bakeway_poc_id'])) {
            $userId = $partner['bakeway_poc_id'];
        } else {
            $userId = "";
        }


        $fieldset->addField(
                'bakeway_poc_id', 'select', [
            'name' => 'bakeway_poc_id',
            'data-form-part' => $this->getData('target_form'),
            'label' => __('Bakeway Point Of Contact'),
            'title' => __('Bakeway Point Of Contact'),
            'value' => $userId,
            'values' => $this->_marketplaceHelper->getAdminUserOptionArray(),
                ]
        );
        /*$fieldset->addField(
                'contact_email', 'text', [
            'name' => 'contact_email',
            'class' => 'validate-email',
            'data-form-part' => $this->getData('target_form'),
            'label' => __('Communication Email'),
            'title' => __('Communication Email'),
            'value' => $partner['contact_email'],
                ]
        );*/
        $fieldset->addField(
                'contact_number', 'text', [
            'name' => 'contact_number',
            'data-form-part' => $this->getData('target_form'),
            'label' => __('Store Landline Number'),
            'title' => __('Store Landline Number'),
            'value' => $partner['contact_number'],
                ]
        );
        $fieldset->addField(
                'shop_title', 'text', [
            'name' => 'shop_title',
            'data-form-part' => $this->getData('target_form'),
            'label' => __('Business Title'),
            'title' => __('Business Title'),
            'value' => $partner['shop_title'],
            'class' => "input-text validate-alphanum-with-spaces"
                ]
        );
        $fieldset->addField(
                'merchant_name', 'text', [
            'name' => 'merchant_name',
            'data-form-part' => $this->getData('target_form'),
            'label' => __('Merchant Name'),
            'title' => __('Merchant Name'),
            'value' => $partner['merchant_name'],
                ]
        );
        if (isset($partner['business_name']) && $partner['business_name'] != '') {
            $fieldset->addField(
                'business_name', 'text', [
                    'name' => 'business_name',
                    'data-form-part' => $this->getData('target_form'),
                    'label' => __('Business Name'),
                    'title' => __('Business Name'),
                    'readonly' => true,
                    'value' => $partner['business_name'],
                ]
            );
        } else {
            $fieldset->addField(
                'business_name', 'text', [
                    'name' => 'business_name',
                    'data-form-part' => $this->getData('target_form'),
                    'label' => __('Business Name'),
                    'title' => __('Business Name'),
                    'value' => $partner['business_name'],
                ]
            );
        }



        /*$fieldset->addField(
                'taxvat', 'text', [
            'name' => 'taxvat',
            'data-form-part' => $this->getData('target_form'),
            'label' => __('Tax/VAT Number'),
            'title' => __('Tax/VAT Number'),
            'value' => $customer->getTaxvat(),
                ]
        );*/

        /*$fieldset->addField(
                'company_locality', 'text', [
            'name' => 'company_locality',
            'data-form-part' => $this->getData('target_form'),
            'label' => __('Company Locality'),
            'title' => __('Company Locality'),
            'value' => $partner['company_locality'],
                ]
        );
        $fieldset->addField(
                'country_pic', 'select', [
            'name' => 'country_pic',
            'data-form-part' => $this->getData('target_form'),
            'label' => __('Select Country'),
            'title' => __('Select Country'),
            'values' => $this->_country->loadByStore()->toOptionArray(),
            'value' => $partner['country_pic'],
                ]
        );*/
        /*$fieldset->addField(
                'company_description', 'textarea', [
            'name' => 'company_description',
            'data-form-part' => $this->getData('target_form'),
            'label' => __('Company Description'),
            'title' => __('Company Description'),
            'value' => $partner['company_description'],
                ]
        );
        $fieldset->addField(
                'return_policy', 'textarea', [
            'name' => 'return_policy',
            'data-form-part' => $this->getData('target_form'),
            'label' => __('Return Policy'),
            'title' => __('Return Policy'),
            'value' => $partner['return_policy'],
                ]
        );
        $fieldset->addField(
                'shipping_policy', 'textarea', [
            'name' => 'shipping_policy',
            'data-form-part' => $this->getData('target_form'),
            'label' => __('Shipping Policy'),
            'title' => __('Shipping Policy'),
            'value' => $partner['shipping_policy'],
                ]
        );*/
      /*  $fieldset->addField(
                'meta_keyword', 'textarea', [
            'name' => 'meta_keyword',
            'data-form-part' => $this->getData('target_form'),
            'label' => __('Meta Keywords'),
            'title' => __('Meta Keywords'),
            'value' => $partner['meta_keyword'],
                ]
        );
        $fieldset->addField(
                'meta_description', 'textarea', [
            'name' => 'meta_description',
            'data-form-part' => $this->getData('target_form'),
            'label' => __('Meta Description'),
            'title' => __('Meta Description'),
            'value' => $partner['meta_description'],
                ]
        );
        $fieldset->addField(
                'banner_pic', 'file', [
            'name' => 'banner_pic',
            'data-form-part' => $this->getData('target_form'),
            'label' => __('Company Banner'),
            'title' => __('Company Banner'),
            'value' => $partner['banner_pic'],
            'after_element_html' => '<label style="width:100%;">
                    Allowed File Type : [jpg, jpeg, gif, png]
                </label>
                <img style="margin:5px 0;width:700px;" 
                src="' . $this->_marketplaceHelper->getMediaUrl() . 'avatar/' . $partner['banner_pic'] . '"
                />',
                ]
        );*/
        $fieldset->addField(
                'logo_pic', 'file', [
            'name' => 'logo_pic',
            'data-form-part' => $this->getData('target_form'),
            'label' => __('Company Logo'),
            'title' => __('Company Logo'),
            'value' => $partner['logo_pic'],
            'after_element_html' => '<label style="width:100%;">
                    Allowed File Type : [jpg, jpeg, gif, png]
                </label>
                <img style="margin:5px 0;width:250px;" 
                src="' . $this->_marketplaceHelper->getMediaUrl() . 'avatar/' . $partner['logo_pic'] . '"
                />',
                ]
        );
        /**
         * Adding Custom fields for bakeway vendor onboarding here.
         */
        $fieldset->addField(
                'store_owner_name', 'text', [
            'name' => 'store_owner_name',
            'data-form-part' => $this->getData('target_form'),
            'label' => __('Store Owner Name'),
            'title' => __('Store Owner Name'),
            'value' => $partner['store_owner_name'],
                ]
        );
        $fieldset->addField(
                'store_owner_mobile_no', 'text', [
            'name' => 'store_owner_mobile_no',
            'class' => 'validate-digits',
            'data-form-part' => $this->getData('target_form'),
            'label' => __('Store Owner Mobile Number'),
            'title' => __('Store Owner Mobile Number'),
            'value' => $partner['store_owner_mobile_no'],
                ]
        );
        $fieldset->addField(
                'store_owner_email', 'text', [
            'name' => 'store_owner_email',
            'class' => 'validate-email',
            'data-form-part' => $this->getData('target_form'),
            'label' => __('Store Owner Email ID'),
            'title' => __('Store Owner Email ID'),
            'value' => $partner['store_owner_email'],
                ]
        );
        $fieldset->addField(
                'store_manager_name', 'text', [
            'name' => 'store_manager_name',
            'data-form-part' => $this->getData('target_form'),
            'label' => __('Store Manager Name'),
            'title' => __('Store Manager Name'),
            'value' => $partner['store_manager_name'],
                ]
        );
        $fieldset->addField(
                'store_manager_mobile_no', 'text', [
            'name' => 'store_manager_mobile_no',
            'class' => 'validate-digits',
            'data-form-part' => $this->getData('target_form'),
            'label' => __('Store Manager Mobile Number'),
            'title' => __('Store Manager Mobile Number'),
            'value' => $partner['store_manager_mobile_no'],
                ]
        );
        $fieldset->addField(
                'store_manager_email', 'text', [
            'name' => 'store_manager_email',
            'class' => 'validate-email',
            'data-form-part' => $this->getData('target_form'),
            'label' => __('Store Manager Email ID'),
            'title' => __('Store Manager Email ID'),
            'value' => $partner['store_manager_email'],
                ]
        );
        $fieldset->addField(
                'bakery_type', 'select', [
            'name' => 'bakery_type',
            'data-form-part' => $this->getData('target_form'),
            'label' => __('Bakery Type'),
            'title' => __('Bakery Type'),
            'value' => $partner['bakery_type'],
            'values' => $this->_marketplaceHelper->getBakeryTypeOptionArray(),
                ]
        );

        if (isset($partner['userdata_brand'])) {
            $brandVal = $partner['userdata_brand'];
        } else {
            $brandVal = "";
        }
        
        $fieldset->addField(
            'store_city', 'select', [
                'name' => 'store_city',
                'data-form-part' => $this->getData('target_form'),
                'label' => __('Store City'),
                'title' => __('Store City'),
                'value' => $partner['store_city'],
                'values' => $this->_bakewayCitiesHelper->getCitiesOptionArray(),
            ]
        );
        
        $fieldset->addField(
                'userdata_brand', 'select', [
            'name' => 'userdata_brand',
            'data-form-part' => $this->getData('target_form'),
            'class' => 'required-entry',
            'label' => __('Brand'),
            'title' => __('Brand'),
            'required' => true,
            'value' => $brandVal,
            'values' => $this->_marketplaceHelper->getBrandOptionArray($partner['seller_id']),
                ]
        );

        if (isset($contract['start_date'])) {
            $contract_start_date = $contract['start_date'];
        } else {
            $contract_start_date = "";
        }
        $fieldset->addField(
                'contract_start_date', 'date', [
            'name' => 'contract_start_date',
            'data-form-part' => $this->getData('target_form'),
            'label' => __('Contract Start Date'),
            'title' => __('Contract Start Date'),
            'date_format' => 'yyyy-MM-dd',
            'time_format' => 'hh:mm:ss',
            'value' => $contract_start_date
                ]
        );
        if (isset($contract['end_date'])) {
            $contract_end_date = $contract['end_date'];
        } else {
            $contract_end_date = "";
        }
        $fieldset->addField(
                'contract_end_date', 'date', [
            'name' => 'contract_end_date',
            'data-form-part' => $this->getData('target_form'),
            'label' => __('Contract End Date'),
            'title' => __('Contract End Date'),
            'date_format' => 'yyyy-MM-dd',
            'time_format' => 'hh:mm:ss',
            'value' => $contract_end_date
                ]
        );

       /* $fieldset->addField(
                'store_locality_area', 'text', [
            'name' => 'store_locality_area',
            'data-form-part' => $this->getData('target_form'),
            'label' => __('Store Locality Area'),
            'title' => __('Store Locality Area'),
            'value' => $partner['store_locality_area'],
                ]
        );
        $fieldset->addField(
                'store_street_address', 'text', [
            'name' => 'store_street_address',
            'data-form-part' => $this->getData('target_form'),
            'label' => __('Store Street Address'),
            'title' => __('Store Street Address'),
            'value' => $partner['store_street_address'],
                ]
        );*/
        $fieldset->addField(
                'store_zipcode', 'text', [
            'name' => 'store_zipcode',
            'data-form-part' => $this->getData('target_form'),
            'class' => 'validate-digits',
            'label' => __('Store Zipcode'),
            'title' => __('Store Zipcode'),
            'value' => $partner['store_zipcode'],
                ]
        );
        /*$fieldset->addField(
                'store_latitude', 'text', [
            'name' => 'store_latitude',
            'data-form-part' => $this->getData('target_form'),
            'label' => __('Store Latitude'),
            'title' => __('Store Latitudestore_street_address'),
            'value' => $partner['store_latitude'],
                ]
        );
        $fieldset->addField(
                'store_longitude', 'text', [
            'name' => 'store_longitude',
            'data-form-part' => $this->getData('target_form'),
            'label' => __('Store Longitude'),
            'title' => __('Store Longitude'),
            'value' => $partner['store_longitude'],
                ]
        ); */
        $fieldset->addField(
                'shop_open_timing', 'select', [
            'name' => 'shop_open_timing',
            'data-form-part' => $this->getData('target_form'),
            'label' => __('Shop Open Timing'),
            'title' => __('Shop Open Timing'),
            'value' => $partner['shop_open_timing'],
            'values' => $this->_marketplaceHelper->getStoreTimeOptionArray(12),
                ]
        );
        $fieldset->addField(
                'shop_open_AMPM', 'select', [
            'name' => 'shop_open_AMPM',
            'data-form-part' => $this->getData('target_form'),
            'label' => __(''),
            'value' => $partner['shop_open_AMPM'],
            'values' => $this->_marketplaceHelper->geAmPmArray(),
                ]
        );


        $fieldset->addField(
                'shop_close_timing', 'select', [
            'name' => 'shop_close_timing',
            'data-form-part' => $this->getData('target_form'),
            'label' => __('Shop Close Timing'),
            'title' => __('Shop Close Timing'),
            'value' => $partner['shop_close_timing'],
            'values' => $this->_marketplaceHelper->getStoreTimeOptionArray(12),
                ]
        );

        $fieldset->addField(
                'shop_close_AMPM', 'select', [
            'name' => 'shop_close_AMPM',
            'data-form-part' => $this->getData('target_form'),
            'label' => __(''),
            'value' => $partner['shop_close_AMPM'],
            'values' => $this->_marketplaceHelper->geAmPmArray(),
                ]
        );

       /* $fieldset->addField(
                'delivery_time', 'select', [
            'name' => 'delivery_time',
            'after_element_html' => '<br><label style="width:100%;">
                    Time in hours.
                </label>',
            'data-form-part' => $this->getData('target_form'),
            'label' => __('Estimated Delivery Time'),
            'title' => __('Estimated Delivery Time'),
            'value' => $partner['delivery_time'],
            'values' => $this->_marketplaceHelper->getTimeOptionArray(23),
                ]
        );*/

        $fieldset->addField(
                'delivery_time_mins', 'select', [
            'name' => 'delivery_time_mins',
            'after_element_html' => '<br><label style="width:100%;">
                    Time in mins.
                </label>',
            'data-form-part' => $this->getData('target_form'),
            'value' => $partner['delivery_time_mins'],
            'label' => __(''),
            'title' => __(''),
            'values' => $this->_marketplaceHelper->getTimeOptionArray(59),
                ]
        );


        $fieldset->addField(
            'shop_delivery_open_time', 'select', [
                'name' => 'shop_delivery_open_time',
                'after_element_html' => '<br><label style="width:100%;">
                    Time in hours.
                </label>',
                'data-form-part' => $this->getData('target_form'),
                'label' => __('Shop Delivery Open Timing'),
                'title' => __('AShop Delivery Open Timing'),
                'value' => $partner['shop_delivery_open_time'],
                'values' => $this->_marketplaceHelper->getTimeOptionArray(23),
            ]
        );

        $fieldset->addField(
            'shop_delivery_close_time', 'select', [
                'name' => 'shop_delivery_close_time',
                'after_element_html' => '<br><label style="width:100%;">
                    Time in hours.
                </label>',
                'data-form-part' => $this->getData('target_form'),
                'label' => __('Shop Delivery Close Timing'),
                'title' => __('Shop Delivery Close Timing'),
                'value' => $partner['shop_delivery_close_time'],
                'values' => $this->_marketplaceHelper->getTimeOptionArray(23),
            ]
        );

        $fieldset->addField(
                'advanced_order_intimation_time', 'select', [
            'name' => 'advanced_order_intimation_time',
            'after_element_html' => '<br><label style="width:100%;">
                    Time in hours.
                </label>',
            'data-form-part' => $this->getData('target_form'),
            'label' => __('Advanced Order Intimation Time'),
            'title' => __('Advanced Order Intimation Time'),
            'value' => $partner['advanced_order_intimation_time'],
            'values' => $this->_marketplaceHelper->getTimeOptionArray(99),
                ]
        );

        $fieldset->addField(
                'is_pickup', 'select', [
            'name' => 'is_pickup',
            'after_element_html' => '<br><label style="width:100%;">
                  Configure Delivery Pick Up Option.
                </label>',
            'data-form-part' => $this->getData('target_form'),
            'label' => __('Pick up available'),
            'title' => __('Pick up available'),
            'value' => $partner['is_pickup'],
            'values' => $this->cmsPage->getAvailableStatuses()
                ]
        );

        $_daysVal = unserialize($partner['userdata_operational_days']);

        $_Days = array("Monday", "Tusday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday");
        $i = 0;
        foreach ($_Days as $_Daysname) {

            if ($i == 0) {
                $labelVal = 'Capture Operational Days of week ';
            } else {
                $labelVal = '';
            }
            if (!empty($_daysVal[$i])) {
                $CheckedVal = $_daysVal[$i];
            } else {
                $CheckedVal = 0;
            }

            $fieldset->addField('checkbox' . $i, 'checkbox', array(
                'label' => __($labelVal),
                'name' => 'userdata_operational_days[' . $i . ']',
                'onclick' => 'this.value = this.checked ? 0 : 0;',
                'onchange' => "",
                'checked' => $CheckedVal,
                'disabled' => false,
                'after_element_html' => $_Daysname,
                'data-form-part' => $this->getData('target_form'),
                'tabindex' => 1
            ));
            $i++;
        }


        if (isset($partner['userdata_shop_temporarily_u_from'])) {
            $temp_start_date = $partner['userdata_shop_temporarily_u_from'];
        } else {
            $temp_start_date = "";
        }
        $fieldset->addField(
                'userdata_shop_temporarily_u_from', 'date', [
            'name' => 'userdata_shop_temporarily_u_from',
            'data-form-part' => $this->getData('target_form'),
            'label' => __('Temporarily Unavailable From'),
            'title' => __('Temporarily Unavailable From'),
            'date_format' => 'yyyy-MM-dd',
            'time_format' => 'hh:mm:ss',
            'value' => $temp_start_date
                ]
        );
        if (isset($partner['userdata_shop_temporarily_u_to'])) {
            $temp_to_date = $partner['userdata_shop_temporarily_u_to'];
        } else {
            $temp_to_date = "";
        }

        $fieldset->addField(
                'userdata_shop_temporarily_u_to', 'date', [
            'name' => 'userdata_shop_temporarily_u_to',
            'data-form-part' => $this->getData('target_form'),
            'label' => __('To'),
            'title' => __('To'),
            'date_format' => 'yyyy-MM-dd',
            'time_format' => 'hh:mm:ss',
            'value' => $temp_to_date,
            'width' => '50%'
                ]
        );

        $fieldset->addField(
                'userdata_shop_operatational_status', 'checkbox', [
            'name' => 'userdata_shop_operatational_status',
            'data-form-part' => $this->getData('target_form'),
            'label' => __('Permanently Closed '),
            'title' => __('Permanently Closed '),
            'value' => $partner['userdata_shop_operatational_status'],
            'onclick' => 'this.value = this.checked ? 1 : 0;',
            'onchange' => "",
            'checked' => $partner['userdata_shop_operatational_status'],
            'disabled' => false,
            'tabindex' => 1,
                ]
        );

        $_Existingval = $_ExistingvalFirst = "";
        if ($partner['known_for']) {
            $_Existingval = explode(",", $partner['known_for']);
            if (isset($_Existingval[0])) {
                $_ExistingvalFirst = $_Existingval[0];
            } else {
                $_ExistingvalFirst = "";
            }
        }
        $fieldset->addField(
            'known_for', 'text', [
                'name' => 'known_for',
                'data-form-part' => $this->getData('target_form'),
                'label' => __('Known For?'),
                'title' => __('Known For?'),
                'value' => $_ExistingvalFirst,
                'after_element_html' => 'Max 20 characters allowed'
            ]
        );

        for ($i = 1; $i <= 4; $i++) {
            $_val[$i] = "";
            if (isset($_Existingval[$i])) {
                $_val[$i] = $_Existingval[$i];
            }
            $fieldset->addField(
                'known_for_' . $i, 'text', [
                    'name' => 'known_for_' . $i,
                    'data-form-part' => $this->getData('target_form'),
                    'label' => __(''),
                    'title' => __(''),
                    'value' => $_val[$i],
                    'after_element_html' => 'Max 20 characters allowed'
                ]
            );
        }


        $_ExistinghvalFirst = $_ExistingHighlval = "";
        if ($partner['store_highlights']) {
            $_ExistingHighlval = explode(",", $partner['store_highlights']);
            if (isset($_ExistingHighlval[0])) {
                $_ExistinghvalFirst = $_ExistingHighlval[0];
            } else {
                $_ExistinghvalFirst = "";
            }
        }

        $fieldset->addField(
            'store_highlights', 'text', [
                'name' => 'store_highlights',
                'data-form-part' => $this->getData('target_form'),
                'label' => __('Highlights'),
                'title' => __('Highlights'),
                'value' => $_ExistinghvalFirst,
                'after_element_html' => 'Max 20 characters allowed'
            ]
        );


        for ($i = 1; $i <= 4; $i++) {
            $_val[$i] = "";
            if (isset($_ExistingHighlval[$i])) {
                $_val[$i] = $_ExistingHighlval[$i];
            }
            $fieldset->addField(
                'store_highlights' . $i, 'text', [
                    'name' => 'store_highlights' . $i,
                    'data-form-part' => $this->getData('target_form'),
                    'label' => __(''),
                    'title' => __(''),
                    'value' => $_val[$i],
                    'after_element_html' => 'Max 20 characters allowed'
                ]
            );
        }
        /* $fieldset->addField('userdata_operational_days', 'checkboxes', [
          'label' => __('Operational Days of week'),
          'name' => 'userdata_operational_days[]',
          'value' => '4',
          'disabled' => false,
          'data-form-part' => $this->getData('target_form'),
          'values' => array(
          array('value' => '1', 'label' => 'Monday'),
          array('value' => '2', 'label' => 'Tusday'),
          array('value' => '3', 'label' => 'Wednesday'),
          array('value' => '4', 'label' => 'Thursday'),
          array('value' => '5', 'label' => 'Friday'),
          array('value' => '6', 'label' => 'Saturday'),
          array('value' => '7', 'label' => 'Sunday'),
          ),
          'onclick' => "",
          'onchange' => "",
          'after_element_html' => '<br><label style="width:100%;">
          Capture Operational Days of week
          </label>',
          ]); */

        /* $fieldset->addField(
          'store_owner_bank_ifsc',
          'text',
          [
          'name' => 'store_owner_bank_ifsc',
          'class' => 'validate-alphanum',
          'data-form-part' => $this->getData('target_form'),
          'label' => __('Store Owner Bank IFSC Code'),
          'title' => __('Store Owner Bank IFSC Code'),
          'value' => $partner['store_owner_bank_ifsc'],
          ]
          );
          $fieldset->addField(
          'store_owner_bank_micr',
          'text',
          [
          'name' => 'store_owner_bank_micr',
          'class' => '',
          'data-form-part' => $this->getData('target_form'),
          'label' => __('Store Owner Bank MICR Code'),
          'title' => __('Store Owner Bank MICR Code'),
          'value' => $partner['store_owner_bank_micr'],
          ]
          );
          $fieldset->addField(
          'store_owner_bank_account_number',
          'text',
          [
          'name' => 'store_owner_bank_account_number',
          'class' => 'validate-number',
          'data-form-part' => $this->getData('target_form'),
          'label' => __('Store Owner Bank Account Number'),
          'title' => __('Store Owner Bank Account Number'),
          'value' => $partner['store_owner_bank_account_number'],
          ]
          );
          $fieldset->addField(
          'store_owner_bank_account_type',
          'text',
          [
          'name' => 'store_owner_bank_account_type',
          'class' => '',
          'data-form-part' => $this->getData('target_form'),
          'label' => __('Store Owner Bank Account Type'),
          'title' => __('Store Owner Bank Account Type'),
          'value' => $partner['store_owner_bank_account_type'],
          ]
          );
          $fieldset->addField(
          'store_owner_bank_registered_name',
          'text',
          [
          'name' => 'store_owner_bank_registered_name',
          'class' => 'validate-alphanum-with-spaces',
          'data-form-part' => $this->getData('target_form'),
          'label' => __('Store Owner Bank Registered Name'),
          'title' => __('Store Owner Bank Registered Name'),
          'value' => $partner['store_owner_bank_registered_name'],
          ]
          ); */
        $form->setUseContainer(true);

        $this->setForm($form);

        return parent::_prepareForm();
    }

    /**
     * @return string
     */
    protected function _toHtml() {
        if ($this->canShowTab()) {
            $this->initForm();

            return parent::_toHtml();
        } else {
            return '';
        }
    }

    /**
     * Prepare the layout.
     *
     * @return $this
     */
    public function getFormHtml() {
        $html = parent::getFormHtml();
        $html .= $this->getLayout()->createBlock(
                        'Webkul\Marketplace\Block\Adminhtml\Customer\Edit\Js'
                )->toHtml();

        return $html;
    }

}
