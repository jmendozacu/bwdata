<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace Bakeway\Brands\Block\Adminhtml\Brands\Edit;

/**
 * Description of Form
 *
 * @author Admin
 */
class Form extends \Magento\Backend\Block\Widget\Form\Generic
{

    /**
     * Construct
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param array $data
     */
    public function __construct(\Magento\Backend\Block\Template\Context $context,
            \Magento\Framework\Registry $registry,
            \Magento\Framework\Data\FormFactory $formFactory,
            array $data = array())
    {
        parent::__construct($context, $registry, $formFactory, $data);
    }

    /**
     * Prepare form
     * @return array
     */
    protected function _prepareForm()
    {
        $form = $this->_formFactory->create(
                [
                    'data' => [
                        'id' => 'edit_form',
                        'action' => $this->getData('action'),
                        'method' => 'post'
                    ]
                ]
        );
        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }

}
