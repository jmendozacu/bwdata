<?php
namespace Bakeway\Partnerlocations\Block\Adminhtml\Customer\Edit\Tab\View;

use Bakeway\Partnerlocations\Model\ResourceModel\Partnerlocations\Collection as LocationsCollection;
use Magento\Customer\Controller\RegistryConstants;
use Bakeway\Cities\Helper\Data as BakewayCitiesHelper;
use Bakeway\ProductApi\Helper\Data as ProductApiHelper;
use Bakeway\Partnerlocations\Helper\Data as PartnerlocationHelper;
 
class Grid  extends  \Magento\Backend\Block\Widget\Grid\Extended
{
   /**
     * Core registry
     *
     * @var \Magento\Framework\Registry|null
     */
    protected $_coreRegistry = null;
 
    /**
     * @var \Bakeway\Deliveryrangeprice\Model\ResourceModel\Rangeprice\Collection
     */
    protected $_cmsPage;

    /**
     * @var LocationsCollection
     */
    protected $locationCollection;

    /**
     * @var BakewayCitiesHelper
     */
    protected $bakewayCitiesHelper;

    /**
     * @var  \Webkul\Marketplace\Model\Seller
     */
    protected $sellermodel;

    /**
     * @var  \Webkul\Marketplace\Helper\Dat
     */
    protected $marketplacehelper;

    /**
     * @var ProductApiHelper
     */
    protected $productApiHelper;
    /**
     * @var PartnerlocationHelper
     */
    protected $partnerlocationHelper;

    /**
     * Constructor
     *
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Backend\Helper\Data $backendHelper
     * @param \Magento\Sales\Model\Resource\Order\Grid\CollectionFactory $collectionFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Bakeway\Deliveryrangeprice\Model\ResourceModel\Rangeprice\Collection $collectionFactory,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Cms\Model\Page $cmsPage,
        LocationsCollection $locationCollection,
        BakewayCitiesHelper $bakewayCitiesHelper,
        \Webkul\Marketplace\Model\Seller $sellermodel,
        \Webkul\Marketplace\Helper\Data $marketplacehelper,
        ProductApiHelper $productApiHelper,
        PartnerlocationHelper $partnerlocationHelper,
        array $data = []
    ) {
        $this->_coreRegistry = $coreRegistry;
        $this->_cmsPage = $cmsPage;
        $this->locationCollection = $locationCollection;
        $this->bakewayCitiesHelper = $bakewayCitiesHelper;
        $this->sellermodel = $sellermodel;
        $this->marketplacehelper = $marketplacehelper;
        $this->productApiHelper = $productApiHelper;
        $this->partnerlocationHelper = $partnerlocationHelper;
        parent::__construct($context, $backendHelper, $data);
    }
   
    /**
     * Initialize the orders grid.
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('partner_locations_grid');
        $this->setDefaultSort('id');
        $this->setUseAjax(true);
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
        $this->setVarNameFilter('grid_record');
    }
    /**
     * {@inheritdoc}
     */
    

     /**
     * @return Store
     */
    protected function _getStore()
    {
        $storeId = (int)$this->getRequest()->getParam('store', 0);
        return $this->_storeManager->getStore($storeId);
    }

  
   /**
     * @return string|null
     */
    public function getCustomerId()
    {
        return $this->_coreRegistry->registry(RegistryConstants::CURRENT_CUSTOMER_ID);
    }

    /**
     * {@inheritdoc}
     */
    protected function _prepareCollection()
    {
        try {
            $collection = $this->locationCollection
                ->addFieldToFilter('seller_id', $this->getCustomerId());
            $this->setCollection($collection);
            return parent::_prepareCollection();
        } catch (\Exception $e) {

        }
    }






    /**
     * @return $this
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function _prepareColumns()
    {

        $this->addColumn(
            'id',
            [
                'header' => __('ID'),
                'type' => 'number',
                'index' => 'id',
                'header_css_class' => 'col-id',
                'column_css_class' => 'col-id'
            ]
        );
        $this->addColumn(
            'city_id',
            [
                'header' => __('City'),
                'type' => 'options',
                'index' => 'city_id',
                'options' => $this->bakewayCitiesHelper->getCitiesOptions(),
                'filter_index'=>'main_table.is_active',
            ]
        );

        $this->addColumn(
            'sub_loc_id',
            [
                'header' => __('Suburb'),
                'type' => 'options',
                'index' => 'sub_loc_id',
                'options' => $this->partnerlocationHelper->getSubLocOptions(),
                'filter_index'=>'main_table.is_active',
            ]
        );

        $this->addColumn(
            'store_latitude',
            [
                'header' => __('Latitude'),
                'type' => 'text',
                'index' => 'store_latitude',
                'header_css_class' => 'col-id',
                'column_css_class' => 'col-id'
            ]
        );
        $this->addColumn(
            'store_longitude',
            [
                'header' => __('Longitude'),
                'type' => 'text',
                'index' => 'store_longitude',
                'header_css_class' => 'col-id',
                'column_css_class' => 'col-id'
            ]
        );
        $this->addColumn(
            'store_locality_area',
            [
                'header' => __('Locality'),
                'type' => 'text',
                'index' => 'store_locality_area',
                'header_css_class' => 'col-id',
                'column_css_class' => 'col-id'
            ]
        );
        $this->addColumn(
            'store_street_address',
            [
                'header' => __('Street Address'),
                'type' => 'text',
                'index' => 'store_street_address',
                'header_css_class' => 'col-id',
                'column_css_class' => 'col-id'
            ]
        );

        $this->addColumn(
            'store_unique_name',
            [
                'header' => __('Unique Name'),
                'type' => 'text',
                'index' => 'store_unique_name',
                'header_css_class' => 'col-id',
                'column_css_class' => 'col-id'
            ]
        );

        $this->addColumn(
            'store_headline',
            [
                'header' => __('Headline'),
                'type' => 'text',
                'index' => 'store_headline',
                'header_css_class' => 'col-id',
                'column_css_class' => 'col-id'
            ]
        );

        $this->addColumn(
            'is_grab_active',
            [
                'header' => __('GRABS\'S Status'),
                'type' => 'options',
                'index' => 'is_grab_active',
                'options' => $this->_cmsPage->getAvailableStatuses(),
                'filter_index'=>'main_table.is_grab_active',
            ]
        );

        $this->addColumn(
            'is_active',
            [
                'header' => __('Status'),
                'type' => 'options',
                'index' => 'is_active',
                'options' => $this->_cmsPage->getAvailableStatuses(),
                'filter_index'=>'main_table.is_active',
            ]
        );
        $this->addColumn(
            'edit', [
                'header' => __('Edit'),
                'type' => 'action',
                'getter' => 'getId',
                'actions' => [
                    [
                        'caption' => __('Edit'),
                        'url' => [
                            'base' => 'partnerlocations/locations/edit'
                        ],
                        'field' => 'id'
                    ]
                ],
                'filter' => false,
                'sortable' => false,
                'index' => 'id',
                'header_css_class' => 'col-action',
                'column_css_class' => 'col-action'
            ]
        );
        $block = $this->getLayout()->getBlock('grid.bottom.links');
        if ($block) {
            $this->setChild('grid.bottom.links', $block);
        }
        return parent::_prepareColumns();


    }
    /**
     * Get headers visibility
     *
     * @return bool
     *
     * @SuppressWarnings(PHPMD.BooleanGetMethodName)
     */
    public function getHeadersVisibility()
    {
        return $this->getCollection()->getSize() >= 0;
    }

    /**
     * @return string
     */
    public function getGridUrl()
    {
        return $this->getUrl('partnerlocations/locations/partnerlocations', ['_current' => true,'collapse' => null]);
    }

    /**
     * @param \Magento\Catalog\Model\Product|\Magento\Framework\Object $row
     * @return string
     */
    public function getRowUrl($row)
    {
        return $this->getUrl(
            'partnerlocations/locations/edit',
            ['store' => $this->getRequest()->getParam('store'), 'id' => $row->getId()]
        );
    }
}