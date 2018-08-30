<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace Bakeway\Brands\Block\Adminhtml\Brands;

/**
 * Description of Grid
 *
 * @author Admin
 */
class Grid extends \Magento\Backend\Block\Widget\Grid\Extended
{

    /** @var \Bakeway\Brands\Model\Brands */
    protected $brandModel;

    /** @var \Bakeway\Cities\Model\Cities */
    protected $cityModel;

    /**
     * Construct
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Backend\Helper\Data $backendHelper
     * @param \Bakeway\Brands\Model\Brands $brandModel
     * @param \Bakeway\Cities\Model\Cities $cityModel
     * @param array $data
     */
    public function __construct(\Magento\Backend\Block\Template\Context $context,
            \Magento\Backend\Helper\Data $backendHelper,
            \Bakeway\Brands\Model\Brands $brandModel,
            \Bakeway\Cities\Model\Cities $cityModel,
            array $data = array())
    {
        parent::__construct($context, $backendHelper, $data);
        $this->brandModel = $brandModel;
        $this->cityModel = $cityModel;
    }

    /**
     * Construct
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('brandsGrid');
        $this->setDefaultSort('entity_id');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(false);
    }

    /**
     * Prepare Collection
     * @return array
     */
    protected function _prepareCollection()
    {
        $collection = $this->brandModel->getCollection();
        $collection->getSelect()->joinInner(array('bc' => 'bakeway_cities'),
                'main_table.city_id = bc.id', array('bc.name'));
        $this->setCollection($collection);
        parent::_prepareCollection();

        return $this;
    }

    /**
     * Prepare Column
     * @return array
     */
    protected function _prepareColumns()
    {
        $this->addColumn(
                'entity_id',
                [
            'header' => __('ID'),
            'type' => 'text',
            'index' => 'entity_id',
            'header_css_class' => 'col-id',
            'column_css_class' => 'col-id'
                ]
        );

        $this->addColumn(
                'brand_name',
                [
            'header' => __('Brand Name'),
            'type' => 'text',
            'index' => 'brand_name',
            'header_css_class' => 'col-id',
            'column_css_class' => 'col-id'
                ]
        );

        $this->addColumn(
                'name',
                [
            'header' => __('City Name'),
            'type' => 'text',
            'index' => 'name',
            'header_css_class' => 'col-id',
            'column_css_class' => 'col-id'
                ]
        );

        $this->addColumn(
                'status',
                [
            'header' => __('Status'),
            'type' => 'text',
            'index' => 'status',
            'header_css_class' => 'col-id',
            'column_css_class' => 'col-id',
            'renderer' => 'Bakeway\Brands\Block\Adminhtml\Brands\Renderer\Status',
            'filter' => false,
                ]
        );

        $this->addColumn('action',
                array(
            'header' => 'Action',
            'type' => 'action',
            'getter' => 'getId',
            'filter' => false,
            'actions' => array(
                array(
                    'caption' => __('Edit'),
                    'url' => array('base' => '*/*/edit'),
                    'field' => 'entity_id'
                ),
                array(
                    'caption' => __('Delete'),
                    'url' => array('base' => '*/*/delete'),
                    'field' => 'entity_id',
                    'confirm' => 'Are you sure you want to delete this record?',
                )
            )
                )
        );

        $block = $this->getLayout()->getBlock('grid.bottom.links');
        if ($block) {
            $this->setChild('grid.bottom.links', $block);
        }

        return parent::_prepareColumns();
    }

    /**
     * Row url
     * @param type $row
     * @return array
     */
    public function getRowUrl($row)
    {
        return $this->getUrl(
                        'brands/index/edit',
                        ['store' => $this->getRequest()->getParam('store'), 'entity_id' => $row->getEntityId()]
        );
    }

}
