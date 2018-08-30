<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace Bakeway\Reports\Block\Adminhtml\Orders\Renderer;

/**
 * Description of GST
 *
 * @author Admin
 */
class GST
        extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer
{

    /**
     * Order Collection Factory
     * @var \Magento\Sales\Model\ResourceModel\Order\CollectionFactory 
     */
    protected $orderModel;

    /**
     * Constructor
     * @param \Magento\Backend\Block\Context $context
     * @param \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $collectionFactory
     * @param array $data
     */
    public function __construct(\Magento\Backend\Block\Context $context,
            \Magento\Sales\Model\Order $orderModel,
            array $data = array())
    {
        $this->orderModel = $orderModel;
        parent::__construct($context, $data);
    }

    /**
     * Renderer
     * @param \Magento\Framework\DataObject $row
     * @return type
     */
    public function render(\Magento\Framework\DataObject $row)
    {
        $gstNumber = $row->getUserdataGstinNumber();
        if (!isset($gstNumber) && empty($gstNumber)) {
            $enityId = $row->getEntityId();
            $Order = $this->orderModel->load($enityId);
            $orderItems = $Order->getAllItems();
            $gst = 0;
            foreach ($orderItems as $item) {
                $gst += $item->getTaxAmount();
            }
            return $gst;
        }
    }

}
