<?php
/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 15-05-2018
 * Time: 12:14
 */

namespace Bakeway\GstReport\Block\Adminhtml\Icici;


class Grid
    extends \Magento\Backend\Block\Widget\Grid\Extended
{
    /**
     * @var \Magento\Sales\Model\ResourceModel\Sale\CollectionFactory
     */
    protected $collectionFactory;

    /**
     * Grid constructor.
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Backend\Helper\Data $backendHelper
     * @param \Magento\Sales\Model\ResourceModel\Sale\CollectionFactory $collectionFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $collectionFactory,
        array $data = []
    )
    {
        $this->collectionFactory = $collectionFactory;
        parent::__construct($context, $backendHelper, $data);
    }

    /**
     * @return $this|\Magento\Backend\Block\Widget\Grid\Extended
     */
    protected function _prepareCollection()
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToSelect('increment_id');
        $collection->addFieldToSelect('entity_id');
        $collection->getSelect()->join(array('sosh' => 'sales_order_status_history'), 'main_table.entity_id = sosh.parent_id', array('sosh.created_at'));
        $collection->addFieldToFilter('sosh.status', array('eq' => 'complete'));
        $collection->addFieldToFilter('sosh.entity_name', array('eq' => 'order'));
        $collection->addFilterToMap('created_at', 'main_table.created_at');
        $collection->getSelect()->group('main_table.entity_id');
        $this->setCollection($collection);

        parent::_prepareCollection();

        return $this;
    }

    /**
     * @return \Magento\Backend\Block\Widget\Grid\Extended
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _prepareColumns()
    {
        $this->addColumn(
            'created_at',
            [
                'header' => __('Order Date'),
                'type' => 'date',
                'index' => 'created_at',
                'header_css_class' => 'col-id',
                'column_css_class' => 'col-id'
            ]

        );

        $this->addColumn(
            'increment_id',
            [
                'header' => __('Order ID'),
                'type' => 'text',
                'index' => 'increment_id',
                'filter' => false,
                'header_css_class' => 'col-id',
                'column_css_class' => 'col-id'
            ]

        );

        $block = $this->getLayout()->getBlock('grid.bottom.links');
        if ($block) {
            $this->setChild('grid.bottom.links', $block);
        }

        $this->addExportType('*/*/exportCsv', __('CSV'));

        return parent::_prepareColumns(); // TODO: Change the autogenerated stub
    }
}