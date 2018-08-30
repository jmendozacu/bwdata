<?php
/**
 * Bakeway
 *
 * @category  Bakeway
 * @package   Bakeway_ProductApi
 * @author    Bakeway
 */

namespace Bakeway\ProductApi\Helper;

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;
use Webkul\Marketplace\Model\Product as VendorProduct;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Catalog\Model\Product\Visibility as CatalogVisibility;
use Magento\Catalog\Model\ResourceModel\CategoryProduct;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Bakeway\PartnerWebapi\Helper\Data as WebapiHelper;

class Filter extends \Magento\Framework\App\Helper\AbstractHelper {

    /**
     * @var $categoryModel
     */
    protected $_categoryModel;

    /**
     * @var $categoryfactory
     */
    protected $_categoryfactory;

    /**
     * @var CategoryRepositoryInterface
     */
    protected $categoryRepository;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var VendorProduct
     */
    protected $vendorProduct;

    /**
     * @var ProductCollectionFactory
     */
    protected $productCollection;

    /**
     * @var CatalogVisibility
     */
    protected $catalogVisibility;

    /**
     * @var CategoryProduct
     */
    protected $categoryProducts;

    /**
     * @var CategoryCollectionFactory
     */
    protected $categoryCollection;

    /**
     * @var WebapiHelper
     */
    protected $webApiHelper;

    /**
     * Filter constructor.
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Catalog\Model\CategoryRepository $categoryModel
     * @param \Magento\Catalog\Model\CategoryFactory $categoryfactory
     * @param CategoryRepositoryInterface $categoryRepository
     * @param StoreManagerInterface $storeManager
     * @param VendorProduct $vendorProduct
     * @param ProductCollectionFactory $productCollection
     * @param CatalogVisibility $catalogVisibility
     * @param CategoryProduct $categoryProducts
     * @param CategoryCollectionFactory $categoryCollection
     * @param WebapiHelper $webApiHelper
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Catalog\Model\CategoryRepository $categoryModel,
        \Magento\Catalog\Model\CategoryFactory $categoryfactory,
        CategoryRepositoryInterface $categoryRepository,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        VendorProduct $vendorProduct,
        ProductCollectionFactory $productCollection,
        CatalogVisibility $catalogVisibility,
        CategoryProduct $categoryProducts,
        CategoryCollectionFactory $categoryCollection,
        WebapiHelper $webApiHelper
    ) {
        parent::__construct($context);
        $this->_categoryModel = $categoryModel;
        $this->_categoryfactory = $categoryfactory;
        $this->categoryRepository = $categoryRepository;
        $this->storeManager = $storeManager;
        $this->vendorProduct = $vendorProduct;
        $this->productCollection = $productCollection;
        $this->catalogVisibility = $catalogVisibility;
        $this->categoryProducts = $categoryProducts;
        $this->categoryCollection = $categoryCollection;
        $this->webApiHelper = $webApiHelper;
    }

    /**
     * @param int $sellerId
     * @return array
     */
    public function getPartnerProductCategoryFilter($sellerId) {
        $result = [];
        $child = [];
        $storeCollection = $this->vendorProduct->getCollection()
            ->addFieldToFilter('seller_id', $sellerId)
            ->addFieldToFilter('status', 1)
            ->addFieldToSelect(['mageproduct_id']);
        $storeCollection->getSelect()->group('mageproduct_id');

        $prodIds = $storeCollection->getAllIds();

//        $collection = $this->productCollection->create();
//        $collection->addFieldToFilter('entity_id', ['in' => $storeProductIDs]);
//        $collection->addAttributeToSelect('entity_id');
//        //$collection->setVisibility($this->catalogVisibility->getVisibleInSiteIds());
//        $prodIds = $collection->getColumnValues('entity_id');

        $catIdsArr = [];
        //TODO : Need to find better solution for this direct mysql query
        if (is_array($prodIds) && !empty($prodIds)) {
            $catProdConn = $this->categoryProducts->getConnection();
            $sql = "Select category_id from catalog_category_product where product_id IN (" . implode(',', $prodIds) . ") group by category_id";
            $catIds = $catProdConn->fetchAll($sql);
            foreach ($catIds as $ids) {
                $catIdsArr[] = $ids['category_id'];
            }
        }

        $catCollection = $this->categoryCollection->create()
            ->addFieldToFilter('name', ['neq'=>WebapiHelper::PARENT_CAKE_CATEGORY])
            ->addFieldToFilter('name', ['neq'=>WebapiHelper::ADDON_CAT_NAME])
            ->addFieldToFilter('entity_id',['in'=>$catIdsArr]);

        $i = 0;
        foreach ($catCollection as $category) {
            $childCat = $this->categoryRepository->get($category->getEntityId());
            $child[$i]['label'] = $childCat->getName();
            $child[$i]['value'] = $childCat->getEntityId();
            $child[$i]['image'] = $childCat->getImageUrl();
            $i++;
        }
        $result['attr_code'] = WebapiHelper::CATEGORY_FILTER_CODE;
        $result['label'] = WebapiHelper::CATEGORY_FILTER_LABEL;
        $result['value'] = $child;
        return $result;
    }


    /**
     * @return array
     */
    public function getAdvanceOrderIntimationFilter()
    {
        $result = [];
        $values = [];
        $values[0]['label'] = '2 Hour Delivery';
        $values[0]['value'] = '0-2';

        $result['attr_code'] = WebapiHelper::ADV_ORDER_INT_TIME_FILTER_CODE;
        $result['label'] = WebapiHelper::ADV_ORDER_INT_TIME_FILTER_LABEL;
        $result['value'] = $values;
        return $result;
    }

    /**
     * @return array
     */
    public function getProductCategoryFilter() {
        $result = $this->webApiHelper->getBakeryCategoryFilter();
        return $result;
    }

    /**
     * @return array
     */
    public function getProductPriceFilter() {
        $result = [];
        $values = [];
        $values[0]['label'] = '0 - 500 Rs';
        $values[0]['value'] = '0-500';
        $values[1]['label'] = '500 - 1000 Rs';
        $values[1]['value'] = '500-1000';
        $values[2]['label'] = '1000 - 1500 Rs';
        $values[2]['value'] = '1000-1500';
        $values[3]['label'] = '1500 - 2000 Rs';
        $values[3]['value'] = '1500-2000';
        $values[4]['label'] = 'More than 2000 Rs';
        $values[4]['value'] = '2000-999999999';

        $result['attr_code'] = WebapiHelper::PRICE_FILTER_CODE;
        $result['label'] = WebapiHelper::PRICE_FILTER_LABEL;
        $result['value'] = $values;
        return $result;
    }

    public function getProductAttributeFilter($attrArray) {
        return $this->webApiHelper->getProductAttributeFilters($attrArray);
    }
}
