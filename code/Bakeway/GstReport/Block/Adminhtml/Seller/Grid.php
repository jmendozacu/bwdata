<?php
/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 02-05-2018
 * Time: 16:03
 */

namespace Bakeway\GstReport\Block\Adminhtml\Seller;


class Grid
    extends \Magento\Backend\Block\Widget\Grid\Extended
{
    /**
     * @var \Webkul\Marketplace\Model\ResourceModel\Seller\CollectionFactory
     */
    protected $collectionFactory;

    /**
     * Grid constructor.
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Backend\Helper\Data $backendHelper
     * @param \Webkul\Marketplace\Model\ResourceModel\Seller\CollectionFactory $collectionFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Webkul\Marketplace\Model\ResourceModel\Seller\CollectionFactory $collectionFactory,
        array $data = [])
    {
        $this->collectionFactory = $collectionFactory;
        parent::__construct($context, $backendHelper, $data);
    }

    /**
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('sellerreportGrid');
        $this->setDefaultSort('entity_id');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(false);

    }

    /**
     * @return $this|\Magento\Backend\Block\Widget\Grid\Extended
     */
    protected function _prepareCollection()
    {
        $collection = $this->collectionFactory->create();
        $collection->getSelect()->join(array('mpsalp' => 'marketplace_saleperpartner'), 'main_table.seller_id = mpsalp.seller_id', array('mpsalp.commission_rate'));
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
            'seller_id',
            [
                'header' => __('Seller Id'),
                'type' => 'text',
                'index' => 'seller_id',
                'filter' => false,
                'header_css_class' => 'col-id',
                'column_css_class' => 'col-id'
            ]
        );

        $this->addColumn(
            'business_name',
            [
                'header' => __('Business Name'),
                'type' => 'text',
                'index' => 'business_name',
                'filter' => false,
                'header_css_class' => 'col-id',
                'column_css_class' => 'col-id'
            ]
        );

        $this->addColumn(
            'shop_title',
            [
                'header' => __('Shop Title'),
                'type' => 'text',
                'index' => 'shop_title',
                'filter' => false,
                'header_css_class' => 'col-id',
                'column_css_class' => 'col-id'
            ]
        );

        $this->addColumn(
            'store_locality_area',
            [
                'header' => __('Store Locality'),
                'type' => 'text',
                'index' => 'store_locality_area',
                'filter' => false,
                'header_css_class' => 'col-id',
                'column_css_class' => 'col-id'
            ]
        );

        $this->addColumn(
            'userdata_gstin_number',
            [
                'header' => __('GST Number'),
                'type' => 'text',
                'index' => 'userdata_gstin_number',
                'filter' => false,
                'header_css_class' => 'col-id',
                'column_css_class' => 'col-id'
            ]
        );

        $this->addColumn(
            'userdata_pan_number',
            [
                'header' => __('PAN Number'),
                'type' => 'text',
                'index' => 'userdata_pan_number',
                'filter' => false,
                'header_css_class' => 'col-id',
                'column_css_class' => 'col-id'
            ]
        );

        $this->addColumn(
            'store_owner_bank_registered_name',
            [
                'header' => __('Bank Registered Name'),
                'type' => 'text',
                'index' => 'store_owner_bank_registered_name',
                'filter' => false,
                'header_css_class' => 'col-id',
                'column_css_class' => 'col-id'
            ]
        );

        $this->addColumn(
            'store_owner_bank_account_number',
            [
                'header' => __('Bank Account Number'),
                'type' => 'text',
                'index' => 'store_owner_bank_account_number',
                'filter' => false,
                'header_css_class' => 'col-id',
                'column_css_class' => 'col-id'
            ]
        );

        $this->addColumn(
            'store_owner_bank_account_type',
            [
                'header' => __('Bank Account Type'),
                'type' => 'text',
                'index' => 'store_owner_bank_account_type',
                'filter' => false,
                'header_css_class' => 'col-id',
                'column_css_class' => 'col-id'
            ]
        );

        $this->addColumn(
            'store_owner_bank_ifsc',
            [
                'header' => __('Bank Account IFSC'),
                'type' => 'text',
                'index' => 'store_owner_bank_ifsc',
                'filter' => false,
                'header_css_class' => 'col-id',
                'column_css_class' => 'col-id'
            ]
        );

        $this->addColumn(
            'commission_rate',
            [
                'header' => __('Commission Rate'),
                'type' => 'text',
                'index' => 'commission_rate',
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