<?php
/**
 * Bakeway
 *
 * @category  Bakeway
 * @package   Bakeway_PartnerWebapi
 * @author    Bakeway
 */

namespace Bakeway\PartnerWebapi\Helper;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Catalog\Model\ResourceModel\Product\Attribute\Collection as AttributeCollection;
use Webkul\Marketplace\Helper\Data as MarketplaceHelper;
use Webkul\Marketplace\Model\ResourceModel\Seller\CollectionFactory as SellerCollection;
use Thai\S3\Model\MediaStorage\File\Storage\S3 as ThaiBucketStorage;
use Magento\CatalogRule\Model\ResourceModel\Rule\CollectionFactory as RulesCollection;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Catalog\Model\CategoryRepository as CategoryRepository;
use Magento\Catalog\Model\ResourceModel\Category as CategoryResourceModel;

/**
 * Bakeway PartnerWebapi Helper Data.
 */
class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    const EAV_ATTR_SET_ID = 9;

    const PROD_EAV_ENTITY_ID = 4;

    const FILTER_DELIVERY_CODE = 'delivery';
    const FILTER_DELIVERY_LABEL = 'Delivery Options';
    const DELIVERY_OPTION_PICKUP = '1';
    const DELIVERY_OPTION_DELIVERY = '2';
    const DELIVERY_OPTION_BOTH = '3';

    const FILTER_BAKERYTYPE_CODE = 'bakery_type';
    const FILTER_BAKERYTYPE_LABEL = 'Bakery Types';

    const CATEGORY_FILTER_CODE = 'category_id';
    const CATEGORY_FILTER_LABEL = 'Categories';
    const PARENT_CAKE_CATEGORY = 'Cakes';
    const ADDON_CAT_NAME = 'Add ons';

    const ADV_ORDER_INT_TIME_FILTER_CODE = 'advance_order_intimation';
    const ADV_ORDER_INT_TIME_FILTER_LABEL = "Order Intimation";

    const LATE_NIGHT_DEL_FILTER_CODE = "late_night_delivery";
    const LATE_NIGHT_DEL_FILTER_LABEL = "Late Night Delivery";

    const PRICE_FILTER_CODE = "price";
    const PRICE_FILTER_LABEL = "Price";

    const OFFERS_FILTER = 'offers';
    const OFFERS_FILTER_LABEL = 'Offers';

    /**
     * Custom directory relative to the "media" folder
     */
    const DIRECTORY = 'avatar';

    /**
     * @var \Magento\Framework\Filesystem\Directory\WriteInterface
     */
    protected $mediaDirectory;

    /**
     * @var \Magento\Framework\Image\AdapterFactory
     */
    protected $imageFactory;

    /**
     * Store manager
     *
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var AttributeCollection
     */
    protected $attributesCollection;

    /**
     * @var \Magento\Eav\Model\Config
     */
    protected $eavConfig;

    /**
     * @var MarketplaceHelper
     */
    protected $marketplaceHelper;

    /**
     * @var SellerCollection
     */
    protected $sellerCollection;

    /**
     * @var ThaiBucketStorage
     */
    protected $thaiBucketStorage;

    /**
     * @var RulesCollection
     */
    protected $rulesCollection;

    /**
     * @var CategoryCollectionFactory
     */
    protected $categoryCollection;

    /**
     * @var CategoryRepository
     */
    protected $categoryRepository;

    /**
     * @var CategoryResourceModel
     */
    protected $categoryResourceModel;

    /**
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Framework\Filesystem $filesystem
     * @param \Magento\Framework\Image\AdapterFactory $imageFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param AttributeCollection $attributesCollection
     * @param \Magento\Eav\Model\Config $eavConfig
     * @param MarketplaceHelper $marketplaceHelper
     * @param SellerCollection $sellerCollection
     * @param ThaiBucketStorage $thaiBucketStorage
     * @param RulesCollection $rulesCollection
     * @param CategoryCollectionFactory $categoryCollection
     * @param CategoryRepository $categoryRepository
     * @param CategoryResourceModel $categoryResourceModel
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Framework\Image\AdapterFactory $imageFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        AttributeCollection $attributesCollection,
        \Magento\Eav\Model\Config $eavConfig,
        MarketplaceHelper $marketplaceHelper,
        SellerCollection $sellerCollection,
        ThaiBucketStorage $thaiBucketStorage,
        RulesCollection $rulesCollection,
        CategoryCollectionFactory $categoryCollection,
        CategoryRepository $categoryRepository,
        CategoryResourceModel $categoryResourceModel
    ) {
        $this->mediaDirectory = $filesystem->getDirectoryWrite(DirectoryList::MEDIA);
        $this->imageFactory = $imageFactory;
        $this->storeManager = $storeManager;
        parent::__construct($context);
        $this->attributesCollection = $attributesCollection;
        $this->eavConfig = $eavConfig;
        $this->marketplaceHelper = $marketplaceHelper;
        $this->sellerCollection = $sellerCollection;
        $this->thaiBucketStorage = $thaiBucketStorage;
        $this->rulesCollection = $rulesCollection;
        $this->categoryCollection = $categoryCollection;
        $this->categoryRepository = $categoryRepository;
        $this->categoryResourceModel = $categoryResourceModel;
    }

    /**
     * First check this file on FS
     *
     * @param string $filename
     * @return bool
     */
    protected function fileExists($filename)
    {
        if ($this->mediaDirectory->isFile($filename)) {
            return true;
        }
        return false;
    }

    /**
     * Resize image
     * @return string
     */
    public function resize($image, $width = null, $height = null)
    {
        $mediaFolder = self::DIRECTORY;

        /**
         * returning uncached image as per BKWYADMIN-556
         * in future if we want to cache it remove return statement
         */
        return $this->storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) . $mediaFolder."/" . $image;

        if ($width === null && $height === null) {
            return $this->storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) . $mediaFolder."/" . $image;
        }
        $path = $mediaFolder . '/cache';
        if ($width !== null) {
            $path .= '/' . $width . 'x';
            if ($height !== null) {
                $path .= $height ;
            }
        }

        $absolutePath = $this->mediaDirectory->getAbsolutePath($mediaFolder) ."/". $image;
        $imageResized = $this->mediaDirectory->getAbsolutePath($path) . $image;

        if (!$this->fileExists($path . $image) && $this->fileExists($mediaFolder ."/". $image)) {
            $imageFactory = $this->imageFactory->create();
            $imageFactory->open($absolutePath);
            $imageFactory->constrainOnly(true);
            $imageFactory->keepTransparency(true);
            $imageFactory->keepFrame(true);
            $imageFactory->keepAspectRatio(true);
            $imageFactory->resize($width, $height);
            $imageFactory->save($imageResized);
            $this->thaiBucketStorage->saveFile($path . $image);
        }

        return $this->storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) . $path . $image;
    }

    /**
     * @return mixed
     */
    public function getProductFilterableAttributes()
    {
        $collection = $this->attributesCollection
            ->addFieldToFilter('is_filterable', 1);
        $collection->getSelect()->joinInner(['eav_attr'=>$collection->getTable('eav_attribute')],
            'main_table.attribute_id=eav_attr.attribute_id',
            ['*']);
        $collection->getSelect()->joinInner(['eav_entity_attr'=>$collection->getTable('eav_entity_attribute')],
            'main_table.attribute_id=eav_entity_attr.attribute_id',
            ['*']);
        $collection->getSelect()->where('eav_attr.entity_type_id=?', self::PROD_EAV_ENTITY_ID);
        $collection->getSelect()->where('eav_attr.frontend_input IN ("boolean", "select")');
        $collection->getSelect()->where('eav_entity_attr.attribute_set_id=?', self::EAV_ATTR_SET_ID);
        $attributes = $collection->getData();

        return $attributes;
    }

    /**
     * @param array|null $attrArr
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getProductAttributeFilters($attrArr = null)
    {
        $result = [];
        $attrCollection = $this->getProductFilterableAttributes();
        $i=0;
        foreach ($attrCollection as $attrModel) {
            if (is_array($attrArr) && !empty($attrArr)) {
                if (!in_array($attrModel['attribute_code'], $attrArr)) {
                    continue;
                }
            }
            $attribute = $this->eavConfig->getAttribute('catalog_product', $attrModel['attribute_code']);
            $options = $attribute->getSource()->getAllOptions();

            foreach($options as $key=>$values){
                if (trim($values['label'])=="" || trim($values['value'])=="") {
                    unset($options[$key]);
                }
            }
            $result[$i]['attr_code'] = $attrModel['attribute_code'];
            $result[$i]['label'] = $attrModel['frontend_label'];
            $result[$i]['value'] = array_merge($options);
            $i++;
        }
        return $result;
    }

    /**
     * @return array
     */
    public function getBakeryDeliveryFilter() {
        $result = [];
        $result['attr_code'] = self::FILTER_DELIVERY_CODE;
        $result['label'] = self::FILTER_DELIVERY_LABEL;
        $result['value'] = [
            [ 'label' => "Pickup only", 'value' => self::DELIVERY_OPTION_PICKUP],
            [ 'label' => "Delivery only", 'value' => self::DELIVERY_OPTION_DELIVERY],
            [ 'label' => "Both", 'value' => self::DELIVERY_OPTION_BOTH]
        ];
        return $result;
    }

    public function getBakeryTypeFilter() {
        $result = [];
        $result['attr_code'] = self::FILTER_BAKERYTYPE_CODE;
        $result['label'] = self::FILTER_BAKERYTYPE_LABEL;
        $options = $this->marketplaceHelper->getBakeryTypeOptionArray();
        array_shift($options);
        $result['value'] = $options;
        return $result;
    }
    
    public function getBakeryCategoryFilter() {
        $result = [];
        $child = [];
        $parentCatCollection = $this->categoryCollection->create()
                ->addFieldToFilter('name',['eq'=>self::PARENT_CAKE_CATEGORY])
                ->getFirstItem();
        $parentCatId = $parentCatCollection->getData('entity_id');
        if (isset($parentCatId)) {
            $parentCat = $this->categoryRepository->get($parentCatId);
            $children = $parentCat->getChildrenCategories();
            $i=0;
            foreach ($children as $category) {
                $prodCount = $this->categoryResourceModel->getProductCount($category);
                $childCat = $this->categoryRepository->get($category->getEntityId());
                if ($prodCount > 0) {
                    $child[$i]['label'] = $childCat->getName();
                    $child[$i]['value'] = $childCat->getEntityId();
                    $child[$i]['image'] = $childCat->getImageUrl();
                    $i++;
                }
            }
        }
        $result['attr_code'] = self::CATEGORY_FILTER_CODE;
        $result['label'] = self::CATEGORY_FILTER_LABEL;
        $result['value'] = $child;
        return $result;
    }

    public function getClosedBakeriesByDate($deliveryDate) {
        $result = [];
        $deliveryDate = date('Y-m-d H:i:s', strtotime($deliveryDate));
        $days = ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday"];
        $day = date('l', strtotime($deliveryDate));
        $dayKey = array_search ($day, $days);

        /**
         * Collection of shops who are closed on the given date
         */
        $shopClosedCollection = $this->sellerCollection->create();
        $shopClosedCollection->addFieldToSelect(['entity_id','seller_id']);
        $shopClosedCollection->addFieldToFilter( 'userdata_shop_temporarily_u_from', ['lteq' => $deliveryDate]);
        $shopClosedCollection->addFieldToFilter('userdata_shop_temporarily_u_to', ['gteq' => $deliveryDate]);
        $sellerIds = $shopClosedCollection->getColumnValues('seller_id');

        /**
         * Omitting the closed shops from the shops collection
         */
        $collection = $this->sellerCollection->create();
        $collection->addFieldToFilter('userdata_shop_operatational_status', 0);
        if (!empty($sellerIds)) {
            $collection->addFieldToFilter('seller_id', ['nin'=>$sellerIds]);
        }

        foreach ($collection as $seller) {
            $operationalDays = $seller->getData('userdata_operational_days');
            $operationalArray = unserialize($operationalDays);
            /**
             * get the bakeries closed on the selected day
             */
            if (is_array($operationalArray) && $operationalArray[$dayKey] != 1) {
                $result[] = $seller->getData('seller_id');
            }

            if (!is_array($operationalArray)) {
                $result[] = $seller->getData('seller_id');
            }
        }
        $result = array_unique (array_merge ($sellerIds, $result));
        return $result;
    }

    public function getCatalogRuleNames() {
        $result = [];
        $collection = $this->rulesCollection->create();
        foreach ($collection as $rule) {
            $result[$rule['rule_id']] = $rule['name'];
        }
        return $result;
    }

    public function getDeliveryTimeInterval()
    {
        $interval = $this->scopeConfig->getValue('react_site_settings/react_settings_general/delivery_time_interval',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        return $interval;
    }

    public function getLateNightDeliveryFilter()
    {
        $values['label'] = 'Till 11 PM';
        $values['value'] = "11-2";
        $result['attr_code'] = self::LATE_NIGHT_DEL_FILTER_CODE;
        $result['label'] = self::LATE_NIGHT_DEL_FILTER_LABEL;
        $result['value'] = $values;
        return $result;
    }

}