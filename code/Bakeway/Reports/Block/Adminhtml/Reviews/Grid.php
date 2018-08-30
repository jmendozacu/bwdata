<?php
namespace Bakeway\Reports\Block\Adminhtml\Reviews;

use Bakeway\Reports\Helper\Data as Reportshelper;


class Grid extends \Magento\Backend\Block\Widget\Grid\Extended
{

    /**
     * @var \Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\CollectionFactory]
     */
    protected $_setsFactory;

    /**
     * @var \Magento\Catalog\Model\ProductFactory
     */
    protected $_productFactory;

    /**
     * @var \Magento\Catalog\Model\Product\Type
     */
    protected $_type;

    /**
     * @var \Magento\Catalog\Model\Product\Attribute\Source\Status
     */
    protected $_status;

    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\CollectionFactory
     */
	protected $_collectionFactory;

    /**
     * @var \Magento\Catalog\Model\Product\Visibility
     */
    protected $_visibility;

    /**
     * @var \Magento\Store\Model\WebsiteFactory
     */
    protected $_websiteFactory;

    /**
     * @var \magento\Sales\Model\OrderFactory
     */
    protected $orderFactory;

    /**
     * @var Reportshelper
     */
    protected $reportshelper;
    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * Grid constructor.
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Backend\Helper\Data $backendHelper
     * @param \Magento\Store\Model\WebsiteFactory $websiteFactory
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     * @param \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $collectionFactory
     * @param Reportshelper $reportshelper
     * @param \Magento\Framework\Registry $registry
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Magento\Store\Model\WebsiteFactory $websiteFactory,
		\Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $collectionFactory,
        Reportshelper $reportshelper,
        \Magento\Framework\Registry $registry,
        array $data = []
    ) {

        $this->_collectionFactory = $collectionFactory;
        $this->orderFactory = $orderFactory;
        $this->_websiteFactory = $websiteFactory;
        $this->reportshelper = $reportshelper;
        $this->registry = $registry;
       // $this->_removeButton('add');
        parent::__construct($context, $backendHelper, $data);
    }

    /**
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('productGrid');
        $this->setDefaultSort('id');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(false);
       
    }

    /**
     * @return Store
     */
    protected function _getStore()
    {
        $storeId = (int)$this->getRequest()->getParam('store', 0);
        return $this->_storeManager->getStore($storeId);
    }

    /**
     * @return $this
     */
    protected function _prepareCollection()
    {
        try{
			
            /*
             * main  order colllection
             */
			$collection =$this->_collectionFactory->create()
                         ->addFieldToSelect(["increment_id","state",'created_at','order_review_token'])
                         ->addFieldToFilter("order_review_token",['neq'=>""])
                         ->addFieldToFilter("state",\Magento\Sales\Model\Order::STATE_COMPLETE)
                         ->setOrder("created_at",'DESC');
            $collection->getSelect()->joinLeft( array('rev'=>'review'), 'main_table.entity_id = rev.order_id', array('rev.order_review_status'));
            $collection->getSelect()->group("main_table.entity_id");
            $collection->addFilterToMap('order_review_status', 'rev.order_review_status');
			$this->setCollection($collection);

			parent::_prepareCollection();
       	return $this;
		}
		catch(Exception $e)
		{
			echo $e->getMessage();
		}
    }

    public function setCustomVariable()
    {
        $this->registry->register('custom_var', 'Added Value');
    }

    /**
     * @param \Magento\Backend\Block\Widget\Grid\Column $column
     * @return $this
     */
    protected function _addColumnFilterToCollection($column)
    {
        if ($this->getCollection()) {
            if ($column->getId() == 'websites') {
                $this->getCollection()->joinField(
                    'websites',
                    'catalog_product_website',
                    'website_id',
                    'product_id=entity_id',
                    null,
                    'left'
                );
            }
        }
        return parent::_addColumnFilterToCollection($column);
    }

    /**
     * @return $this
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function _prepareColumns()
    {

        $this->addColumn(
            'increment_id',
            [
                'header' => __('Order ID'),
                'type' => 'text',
                'index' => 'increment_id',
                'header_css_class' => 'col-id',
                'column_css_class' => 'col-id'
            ]
        );

       

        $this->addColumn(
            'order_review_token',
            [
                'header' => __('Token'),
                'type' => 'text',
                'index' => 'order_review_token',
                'header_css_class' => 'col-id',
                'column_css_class' => 'col-id',
                'renderer' => 'Bakeway\Reports\Block\Adminhtml\Reviews\Renderer\Orderreviewlink'
            ]
        );

        $this->addColumn(
            'created_at',
            [
                'header' => __('Date'),
                'type' => 'datetime',
                'index' => 'created_at',
                'header_css_class' => 'col-id',
                'column_css_class' => 'col-id'
            ]
        );
    	/*{{CedAddGridColumn}}*/

        $block = $this->getLayout()->getBlock('grid.bottom.links');
        if ($block) {
            $this->setChild('grid.bottom.links', $block);
        }


         $this->addExportType('*/*/exportreviewCsv', __('CSV'));

        return parent::_prepareColumns();
    }



    /**
     * @return string
     */
    public function getGridUrl()
    {
        return $this->getUrl('customreports/customer/reviews', ['_current' => true]);
    }




}
