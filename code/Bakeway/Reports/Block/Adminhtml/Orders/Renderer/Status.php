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
class Status extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer
{

    /**
     * Constructor
     * @param \Magento\Backend\Block\Context $context
     * @param array $data
     */
    public function __construct(\Magento\Backend\Block\Context $context,
            array $data = array())
    {
        parent::__construct($context, $data);
    }

    /**
     * Renderer
     * @param \Magento\Framework\DataObject $row
     * @return type
     */
    public function render(\Magento\Framework\DataObject $row)
    {
        $status = $row->getLabel();
        if ($status === 'Complete') {
            return date('M d, Y h:i A', strtotime($row->getUpdatedAt()));
        } else {
            return '';
        }
    }

}
