<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace Bakeway\Brands\Controller\Adminhtml\Index;

/**
 * Description of Addbrand
 *
 * @author Admin
 */
class Addbrand extends \Magento\Backend\App\Action
{

    /**
     * Construct
     * @param \Magento\Backend\App\Action\Context $context
     */
    public function __construct(\Magento\Backend\App\Action\Context $context)
    {
        parent::__construct($context);
    }

    /**
     * Execute
     */
    public function execute()
    {
        $this->_forward('edit');
    }

}
