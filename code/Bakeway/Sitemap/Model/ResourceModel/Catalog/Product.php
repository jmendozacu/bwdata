<?php
/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Bakeway\Sitemap\Model\ResourceModel\Catalog;

use Magento\Store\Model\Store;
use \Webkul\Marketplace\Model\ResourceModel\Product\CollectionFactory as SellerProductCollectionFactory;

/**
 * Sitemap resource product collection model
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Product extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    const NOT_SELECTED_IMAGE = 'no_selection';

    const ENTITY_TYPE = 'bakeway-product';

    const STORE_ID = 1;

    /**
     * Collection Zend Db select
     *
     * @var \Magento\Framework\DB\Select
     */
    protected $_select;

    /**
     * Attribute cache
     *
     * @var array
     */
    protected $_attributesCache = [];

    /**
     * @var \Magento\Catalog\Model\Product\Gallery\ReadHandler
     */
    protected $mediaGalleryReadHandler;

    /**
     * Sitemap data
     *
     * @var \Magento\Sitemap\Helper\Data
     */
    protected $_sitemapData = null;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product
     */
    protected $_productResource;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var \Magento\Catalog\Model\Product\Visibility
     */
    protected $_productVisibility;

    /**
     * @var \Magento\Catalog\Model\Product\Attribute\Source\Status
     */
    protected $_productStatus;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\Gallery
     */
    protected $mediaGalleryResourceModel;

    /**
     * @var \Magento\Catalog\Model\Product\Media\Config
     */
    protected $_mediaConfig;

    /**
     * @var SellerProductCollectionFactory
     */
    protected $sellerProductCollectionFactory;

    /**
     * @param \Magento\Framework\Model\ResourceModel\Db\Context $context
     * @param \Magento\Sitemap\Helper\Data $sitemapData
     * @param \Magento\Catalog\Model\ResourceModel\Product $productResource
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Catalog\Model\Product\Visibility $productVisibility
     * @param \Magento\Catalog\Model\Product\Attribute\Source\Status $productStatus
     * @param \Magento\Catalog\Model\ResourceModel\Product\Gallery $mediaGalleryResourceModel
     * @param \Magento\Catalog\Model\Product\Gallery\ReadHandler $mediaGalleryReadHandler
     * @param \Magento\Catalog\Model\Product\Media\Config $mediaConfig
     * @param string $connectionName
     * @param SellerProductCollectionFactory $sellerProductCollectionFactory
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        \Magento\Sitemap\Helper\Data $sitemapData,
        \Magento\Catalog\Model\ResourceModel\Product $productResource,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Catalog\Model\Product\Visibility $productVisibility,
        \Magento\Catalog\Model\Product\Attribute\Source\Status $productStatus,
        \Magento\Catalog\Model\ResourceModel\Product\Gallery $mediaGalleryResourceModel,
        \Magento\Catalog\Model\Product\Gallery\ReadHandler $mediaGalleryReadHandler,
        \Magento\Catalog\Model\Product\Media\Config $mediaConfig,
        $connectionName = null,
        SellerProductCollectionFactory $sellerProductCollectionFactory
    ) {
        $this->_productResource = $productResource;
        $this->_storeManager = $storeManager;
        $this->_productVisibility = $productVisibility;
        $this->_productStatus = $productStatus;
        $this->mediaGalleryResourceModel = $mediaGalleryResourceModel;
        $this->mediaGalleryReadHandler = $mediaGalleryReadHandler;
        $this->_mediaConfig = $mediaConfig;
        $this->_sitemapData = $sitemapData;
        $this->sellerProductCollectionFactory = $sellerProductCollectionFactory;
        parent::__construct($context, $connectionName);
    }

    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init('catalog_product_entity', 'entity_id');
    }

    /**
     * Add attribute to filter
     *
     * @param int $storeId
     * @param string $attributeCode
     * @param mixed $value
     * @param string $type
     * @return \Magento\Framework\DB\Select|bool
     */
    protected function _addFilter($storeId, $attributeCode, $value, $type = '=')
    {
        if (!$this->_select instanceof \Magento\Framework\DB\Select) {
            return false;
        }

        switch ($type) {
            case '=':
                $conditionRule = '=?';
                break;
            case 'in':
                $conditionRule = ' IN(?)';
                break;
            default:
                return false;
                break;
        }

        $attribute = $this->_getAttribute($attributeCode);
        if ($attribute['backend_type'] == 'static') {
            $this->_select->where('e.' . $attributeCode . $conditionRule, $value);
        } else {
            $this->_joinAttribute($storeId, $attributeCode);
            if ($attribute['is_global']) {
                $this->_select->where('t1_' . $attributeCode . '.value' . $conditionRule, $value);
            } else {
                $ifCase = $this->getConnection()->getCheckSql(
                    't2_' . $attributeCode . '.value_id > 0',
                    't2_' . $attributeCode . '.value',
                    't1_' . $attributeCode . '.value'
                );
                $this->_select->where('(' . $ifCase . ')' . $conditionRule, $value);
            }
        }

        return $this->_select;
    }

    /**
     * Join attribute by code
     *
     * @param int $storeId
     * @param string $attributeCode
     * @return void
     */
    protected function _joinAttribute($storeId, $attributeCode)
    {
        $connection = $this->getConnection();
        $attribute = $this->_getAttribute($attributeCode);
        $linkField = $this->_productResource->getLinkField();
        $attrTableAlias = 't1_' . $attributeCode;
        $this->_select->joinLeft(
            [$attrTableAlias => $attribute['table']],
            "e.{$linkField} = {$attrTableAlias}.{$linkField}"
            . ' AND ' . $connection->quoteInto($attrTableAlias . '.store_id = ?', Store::DEFAULT_STORE_ID)
            . ' AND ' . $connection->quoteInto($attrTableAlias . '.attribute_id = ?', $attribute['attribute_id']),
            []
        );

        if (!$attribute['is_global']) {
            $attrTableAlias2 = 't2_' . $attributeCode;
            $this->_select->joinLeft(
                ['t2_' . $attributeCode => $attribute['table']],
                "{$attrTableAlias}.{$linkField} = {$attrTableAlias2}.{$linkField}"
                . ' AND ' . $attrTableAlias . '.attribute_id = ' . $attrTableAlias2 . '.attribute_id'
                . ' AND ' . $connection->quoteInto($attrTableAlias2 . '.store_id = ?', $storeId),
                []
            );
        }
    }

    /**
     * Get attribute data by attribute code
     *
     * @param string $attributeCode
     * @return array
     */
    protected function _getAttribute($attributeCode)
    {
        if (!isset($this->_attributesCache[$attributeCode])) {
            $attribute = $this->_productResource->getAttribute($attributeCode);

            $this->_attributesCache[$attributeCode] = [
                'entity_type_id' => $attribute->getEntityTypeId(),
                'attribute_id' => $attribute->getId(),
                'table' => $attribute->getBackend()->getTable(),
                'is_global' => $attribute->getIsGlobal() ==
                    \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
                'backend_type' => $attribute->getBackendType(),
            ];
        }
        return $this->_attributesCache[$attributeCode];
    }

    /**
     * Get category collection array
     *
     * @param null|string|bool|int|Store $storeId
     * @return array|bool
     */
    public function getCollection($storeId = null)
    {
        $products = [];

        /* @var $store Store */
        $store = $this->_storeManager->getStore(self::STORE_ID);
        if (!$store) {
            return false;
        }
        $connection = $this->getConnection();

        $this->_select = $connection->select()->from(
            ['e' => $this->getMainTable()],
            [$this->getIdFieldName(), $this->_productResource->getLinkField(), 'updated_at']
        )->joinInner(
            ['w' => $this->getTable('catalog_product_website')],
            'e.entity_id = w.product_id',
            []
        )->joinInner(
            ['sp' => $this->getTable('marketplace_product')],
            'e.entity_id = sp.mageproduct_id',
            ['seller_id']
        )->joinLeft(
            ['mpudata' => $this->getTable('marketplace_userdata')],
            'sp.seller_id = mpudata.seller_id',
            ['seller_id', 'is_conglomerate']
        )->joinLeft(
            ['url_rewrite' => $this->getTable('url_rewrite')],
            'e.entity_id = url_rewrite.entity_id AND url_rewrite.metadata IS NULL'
            . $connection->quoteInto(' AND url_rewrite.entity_type = ?', self::ENTITY_TYPE),
            ['url' => 'request_path']
        )->where(
            'w.website_id = ?',
            $store->getWebsiteId()
        )->where(
            'sp.status = ?', 1
        )->where(
            'mpudata.is_seller = ?', 1
        )->where(
            'mpudata.is_live_ready = ?', 1
        );

        $this->addCategoriesFilter(['in'=>(13)]);
        $this->_addFilter(self::STORE_ID, 'visibility', $this->_productVisibility->getVisibleInSiteIds(), 'in');
        $this->_addFilter(self::STORE_ID, 'status', $this->_productStatus->getVisibleStatusIds(), 'in');
        $query = $connection->query($this->_select);
        
        while ($row = $query->fetch()) {
            $product = $this->_prepareProduct($row, self::STORE_ID);
            $products[$product->getId()] = $product;
        }

        return $products;
    }

    /**
     * Prepare product
     *
     * @param array $productRow
     * @param int $storeId
     * @return \Magento\Framework\DataObject
     */
    protected function _prepareProduct(array $productRow, $storeId)
    {
        $product = new \Magento\Framework\DataObject();

        $product['id'] = $productRow[$this->getIdFieldName()];
        if (empty($productRow['url'])) {
            $productRow['url'] = 'catalog/product/view/id/' . $product->getId();
        }
        $product->addData($productRow);

        return $product;
    }

    /**
     * Load product images
     *
     * @param \Magento\Framework\DataObject $product
     * @param int $storeId
     * @return void
     */
    protected function _loadProductImages($product, $storeId)
    {
        /** @var $helper \Magento\Sitemap\Helper\Data */
        $helper = $this->_sitemapData;
        $imageIncludePolicy = $helper->getProductImageIncludePolicy($storeId);

        // Get product images
        $imagesCollection = [];
        if (\Magento\Sitemap\Model\Source\Product\Image\IncludeImage::INCLUDE_ALL == $imageIncludePolicy) {
            $imagesCollection = $this->_getAllProductImages($product, $storeId);
        } elseif (\Magento\Sitemap\Model\Source\Product\Image\IncludeImage::INCLUDE_BASE == $imageIncludePolicy &&
            $product->getImage() &&
            $product->getImage() != self::NOT_SELECTED_IMAGE
        ) {
            $imagesCollection = [
                new \Magento\Framework\DataObject(
                    ['url' => $this->_getMediaConfig()->getBaseMediaUrlAddition() . $product->getImage()]
                ),
            ];
        }

        if ($imagesCollection) {
            // Determine thumbnail path
            $thumbnail = $product->getThumbnail();
            if ($thumbnail && $product->getThumbnail() != self::NOT_SELECTED_IMAGE) {
                $thumbnail = $this->_getMediaConfig()->getBaseMediaUrlAddition() . $thumbnail;
            } else {
                $thumbnail = $imagesCollection[0]->getUrl();
            }

            $product->setImages(
                new \Magento\Framework\DataObject(
                    ['collection' => $imagesCollection, 'title' => $product->getName(), 'thumbnail' => $thumbnail]
                )
            );
        }
    }

    /**
     * Get all product images
     *
     * @param \Magento\Framework\DataObject $product
     * @param int $storeId
     * @return array
     */
    protected function _getAllProductImages($product, $storeId)
    {
        $product->setStoreId($storeId);
        $gallery = $this->mediaGalleryResourceModel->loadProductGalleryByAttributeId(
            $product,
            $this->mediaGalleryReadHandler->getAttribute()->getId()
        );

        $imagesCollection = [];
        if ($gallery) {
            $productMediaPath = $this->_getMediaConfig()->getBaseMediaUrlAddition();
            foreach ($gallery as $image) {
                $imagesCollection[] = new \Magento\Framework\DataObject(
                    [
                        'url' => $productMediaPath . $image['file'],
                        'caption' => $image['label'] ? $image['label'] : $image['label_default'],
                    ]
                );
            }
        }

        return $imagesCollection;
    }

    /**
     * Get media config
     *
     * @return \Magento\Catalog\Model\Product\Media\Config
     */
    protected function _getMediaConfig()
    {
        return $this->_mediaConfig;
    }


    /**
     * Map equal and not equal conditions to in and not in
     *
     * @param string $conditionType
     * @return mixed
     */
    private function mapConditionType($conditionType)
    {
        $conditionsMap = [
            'eq' => 'in',
            'neq' => 'nin'
        ];
        return isset($conditionsMap[$conditionType]) ? $conditionsMap[$conditionType] : $conditionType;
    }

    /**
     * Filter Product by Categories
     *
     * @param array $categoriesFilter
     */
    public function addCategoriesFilter(array $categoriesFilter)
    {
        foreach ($categoriesFilter as $conditionType => $values) {
            $categorySelect = $this->getConnection()->select()->from(
                ['cat' => $this->getTable('catalog_category_product')],
                'cat.product_id'
            )->where($this->getConnection()->prepareSqlCondition('cat.category_id', ['in' => $values]));
            $selectCondition = [
                $this->mapConditionType($conditionType) => $categorySelect
            ];
            $this->_select->where($this->getConnection()->prepareSqlCondition('e.entity_id' ,$selectCondition));
        }
    }
}
