<?php
/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 02-05-2018
 * Time: 16:03
 */

namespace Bakeway\GstReport\Block\Adminhtml\Registered;


class Grid
    extends \Magento\Backend\Block\Widget\Grid\Extended
{
    protected $collectionFactory;

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Webkul\Marketplace\Model\ResourceModel\Sellertransaction\CollectionFactory $collectionFactory,
        array $data = [])
    {
        $this->collectionFactory = $collectionFactory;
        parent::__construct($context, $backendHelper, $data);
    }

    protected function _construct()
    {
        parent::_construct();
        $this->setId('registeredGrid');
        $this->setDefaultSort('id');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(false);

    }

    protected function _prepareCollection()
    {
        $collection = $this->collectionFactory->create();
        $collection->getSelect()->joinLeft(array('msl' => 'marketplace_saleslist'), 'main_table.entity_id = msl.trans_id', array('msl.commission_tax_details', 'msl.magerealorder_id', 'msl.total_commission'));
        $collection->getSelect()->joinLeft(array('so' => 'sales_order'), 'msl.magerealorder_id = so.entity_id', array('so.status', 'so.fee'));
        $collection->getSelect()->joinLeft(array('mud' => 'marketplace_userdata'), 'mud.seller_id = main_table.seller_id', array('mud.userdata_gstin_number', 'mud.store_owner_bank_registered_name'));
        $collection->getSelect()->joinLeft(array('bpl' => 'bakeway_partner_locations'), 'mud.seller_id = bpl.seller_id', array());
        $collection->getSelect()->joinLeft(array('bc' => 'bakeway_cities'), 'bc.id = bpl.city_id', array('bc.name'));
        $collection->addFieldToFilter('mud.userdata_gstin_number', array('notnull' => true));
        $collection->addFieldToFilter('main_table.transaction_status', array('eq' => 1));
        $collection->addFilterToMap('created_at', 'main_table.created_at');
        $collection->getSelect()->group('main_table.entity_id');
        $this->setCollection($collection);
        parent::_prepareCollection();

        return $this;

    }


    protected function _prepareColumns()
    {
        $this->addColumn(
            'bill_no',
            [
                'header' => __('Bill No'),
                'type' => 'text',
                'index' => 'bill_no',
                'filter' => false,
                'header_css_class' => 'col-id',
                'column_css_class' => 'col-id',
                'renderer' => 'Bakeway\GstReport\Block\Adminhtml\Registered\Renderer\BillNo'
            ]
        );

        $this->addColumn(
            'created_at',
            [
                'header' => __('Bill Date'),
                'type' => 'date',
                'index' => 'created_at',
                'header_css_class' => 'col-id',
                'column_css_class' => 'col-id'
            ]

        );

        $this->addColumn(
            'store_owner_bank_registered_name',
            [
                'header' => __('Beneficiary Name'),
                'type' => 'text',
                'index' => 'store_owner_bank_registered_name',
                'filter' => false,
                'header_css_class' => 'col-id',
                'column_css_class' => 'col-id'
            ]

        );


        $this->addColumn(
            'total_commission',
            [
                'header' => __('Commission Amount'),
                'type' => 'text',
                'filter' => false,
                'index' => 'total_commission',
                'header_css_class' => 'col-id',
                'column_css_class' => 'col-id',
                'renderer' => 'Bakeway\GstReport\Block\Adminhtml\Registered\Renderer\TotalCommission'
            ]

        );

        $this->addColumn(
            'cgst',
            [
                'header' => __('CGST'),
                'type' => 'text',
                'filter' => false,
                'index' => 'seller_id',
                'header_css_class' => 'col-id',
                'column_css_class' => 'col-id',
                'renderer' => 'Bakeway\GstReport\Block\Adminhtml\Registered\Renderer\CGST'
            ]

        );

        $this->addColumn(
            'sgst',
            [
                'header' => __('SGST'),
                'type' => 'text',
                'filter' => false,
                'index' => 'seller_id',
                'header_css_class' => 'col-id',
                'column_css_class' => 'col-id',
                'renderer' => 'Bakeway\GstReport\Block\Adminhtml\Registered\Renderer\SGST'
            ]

        );

        $this->addColumn(
            'fee',
            [
                'header' => __('Convenience Fee'),
                'type' => 'text',
                'index' => 'fee',
                'filter' => false,
                'header_css_class' => 'col-id',
                'column_css_class' => 'col-id',
                'renderer' => 'Bakeway\GstReport\Block\Adminhtml\Registered\Renderer\Fee'
            ]

        );

        $this->addColumn(
            'fee_cgst',
            [
                'header' => __('CGST'),
                'type' => 'text',
                'index' => 'fee_cgst',
                'filter' => false,
                'header_css_class' => 'col-id',
                'column_css_class' => 'col-id',
                'renderer' => 'Bakeway\GstReport\Block\Adminhtml\Registered\Renderer\FeeCGST'
            ]

        );

        $this->addColumn(
            'fee_sgst',
            [
                'header' => __('SGST'),
                'type' => 'text',
                'index' => 'fee_sgst',
                'filter' => false,
                'header_css_class' => 'col-id',
                'column_css_class' => 'col-id',
                'renderer' => 'Bakeway\GstReport\Block\Adminhtml\Registered\Renderer\FeeSGST'
            ]

        );

        $this->addColumn(
            'total_cgst',
            [
                'header' => __('TOTAL CGST'),
                'type' => 'text',
                'index' => 'total_cgst',
                'filter' => false,
                'header_css_class' => 'col-id',
                'column_css_class' => 'col-id',
                'renderer' => 'Bakeway\GstReport\Block\Adminhtml\Registered\Renderer\TotalCGST'
            ]

        );

        $this->addColumn(
            'total_sgst',
            [
                'header' => __('TOTAL SGST'),
                'type' => 'text',
                'index' => 'total_sgst',
                'filter' => false,
                'header_css_class' => 'col-id',
                'column_css_class' => 'col-id',
                'renderer' => 'Bakeway\GstReport\Block\Adminhtml\Registered\Renderer\TotalSGST'
            ]

        );
        
        $this->addColumn(
            'name',
            [
                'header' => __('City'),
                'type' => 'text',
                'index' => 'name',
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