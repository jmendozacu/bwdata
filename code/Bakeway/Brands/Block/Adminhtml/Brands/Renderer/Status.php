<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace Bakeway\Brands\Block\Adminhtml\Brands\Renderer;

/**
 * Description of Status
 *
 * @author Admin
 */
class Status extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer
{

    /**
     * Construct
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
     */
    public function render(\Magento\Framework\DataObject $row)
    {
        $status = $row->getStatus();
        if ($status) {
            return 'Active';
        }

        return 'In Active';
    }

}
