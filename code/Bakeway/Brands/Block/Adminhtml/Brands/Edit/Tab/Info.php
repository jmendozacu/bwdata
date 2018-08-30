<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace Bakeway\Brands\Block\Adminhtml\Brands\Edit\Tab;

/**
 * Description of Info
 *
 * @author Admin
 */
class Info extends \Magento\Backend\Block\Widget\Form\Generic implements \Magento\Backend\Block\Widget\Tab\TabInterface
{

    /** @var \Bakeway\Cities\Model\ResourceModel\Cities\CollectionFactory */
    protected $cityFactory;

    /**
     * Construct
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param \Bakeway\Cities\Model\ResourceModel\Cities\CollectionFactory $cityFactory
     * @param array $data
     */
    public function __construct(\Magento\Backend\Block\Template\Context $context,
            \Magento\Framework\Registry $registry,
            \Magento\Framework\Data\FormFactory $formFactory,
            \Bakeway\Cities\Model\ResourceModel\Cities\CollectionFactory $cityFactory,
            array $data = array())
    {
        parent::__construct($context, $registry, $formFactory, $data);
        $this->cityFactory = $cityFactory;
    }

    /**
     * Prepare form
     * @return array
     */
    public function _prepareForm()
    {
        $model = $this->_coreRegistry->registry('brands');
        $form = $this->_formFactory->create();
        $form->setHtmlIdPrefix('brand_');
        $form->setFieldNameSuffix('brand');

        $data = $model->getData();
        if (isset($data) && !empty($data)) {
            $optionArray = [
                'name' => 'brand_name',
                'label' => __('Brand Name'),
                'required' => true,
//                'readonly' => true,
            ];
        } else {
            $optionArray = [
                'name' => 'brand_name',
                'label' => __('Brand Name'),
                'required' => true
            ];
        }

        $fieldset = $form->addFieldset(
                'base_fieldset', ['legend' => __('General')]
        );

        if ($model->getId()) {
            $fieldset->addField(
                    'entity_id', 'hidden', ['name' => 'entity_id']
            );
        }

        $fieldset->addField('brand_name', 'text', $optionArray);

        $fieldset->addField(
                'city_id', 'select',
                [
            'name' => 'city_id',
            'label' => __('City'),
            'options' => $this->getCityOptionArray()
                ]
        );

        $fieldset->addField(
                'status', 'select',
                [
            'name' => 'status',
            'label' => __('Status'),
            'options' => [0 => __('In Active'), 1 => 'Active']
                ]
        );


        $form->setValues($data);
        $this->setForm($form);

        return parent::_prepareForm();
    }

    /**
     * Get Label
     * @return string
     */
    public function getTabLabel()
    {
        return __('Brand Info');
    }

    /**
     * Show tab
     * @return boolean
     */
    public function canShowTab()
    {
        return true;
    }

    /**
     * Get tab title
     * @return string
     */
    public function getTabTitle()
    {
        return __('Brand Info');
    }

    /**
     * Set is hidden
     * @return boolean
     */
    public function isHidden()
    {
        return false;
    }

    /**
     * Get cities array 
     * @return array
     */
    private function getCityOptionArray()
    {
        $cities = $this->cityFactory->create();
        $cityArray = array();
        foreach ($cities as $city) {
            $cityArray[$city->getId()] = $city->getName();
        }

        return $cityArray;
    }

}
