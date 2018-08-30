<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace Bakeway\Brands\Block\Adminhtml\Brands;

/**
 * Description of Edit
 *
 * @author Admin
 */
class Edit extends \Magento\Backend\Block\Widget\Form\Container
{

    /** @var \Magento\Framework\Registry */
    protected $registry;

    /**
     * Constructor
     * @param \Magento\Backend\Block\Widget\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param array $data
     */
    public function __construct(\Magento\Backend\Block\Widget\Context $context,
            \Magento\Framework\Registry $registry,
            array $data = array())
    {
        parent::__construct($context, $data);
        $this->registry = $registry;
    }

    /**
     * Construct
     */
    public function _construct()
    {
        $this->_objectId = 'id';
        $this->_controller = 'adminhtml_brands';
        $this->_blockGroup = 'Bakeway_Brands';

        parent::_construct();
        $this->buttonList->update('delete', 'label', __('Delete'));
        $this->buttonList->update('save', 'label', __('Save Brand'));
        $this->buttonList->add(
                'saveandcontinue',
                [
            'label' => __('Save and Continue Edit'),
            'class' => 'save',
            'data_attribute' => [
                'mage-init' => [
                    'button' => [
                        'event' => 'saveAndContinueEdit',
                        'target' => '#edit_form'
                    ]
                ]
            ]
                ], -100
        );
    }

    /**
     * Set header text
     * @return string
     */
    public function getHeaderText()
    {
        $brandRegistry = $this->registry->registry('brands');
        if ($brandRegistry->getId()) {
            $brandTitle = $this->escapeHtml($brandRegistry->getBrandName());
            return __("Edit Brand '%1'", $brandTitle);
        } else {
            return __('Add Brand');
        }
    }

}
