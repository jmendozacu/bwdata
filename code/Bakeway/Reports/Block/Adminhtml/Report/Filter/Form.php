<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace Bakeway\Reports\Block\Adminhtml\Report\Filter;

/**
 * Description of Form
 *
 * @author Admin
 */
class Form
        extends \Magento\Sales\Block\Adminhtml\Report\Filter\Form\Coupon
{

    /**
     *
     * @var \Bakeway\Cities\Model\Cities 
     */
    protected $cities;

    /**
     * Constructor
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param \Magento\Sales\Model\Order\ConfigFactory $orderConfig
     * @param \Magento\SalesRule\Model\ResourceModel\Report\RuleFactory $reportRule
     * @param \Bakeway\Cities\Model\Cities $cities
     * @param array $data
     */
    public function __construct(\Magento\Backend\Block\Template\Context $context,
            \Magento\Framework\Registry $registry,
            \Magento\Framework\Data\FormFactory $formFactory,
            \Magento\Sales\Model\Order\ConfigFactory $orderConfig,
            \Magento\SalesRule\Model\ResourceModel\Report\RuleFactory $reportRule,
            \Bakeway\Cities\Model\Cities $cities,
            array $data = array())
    {
        $this->cities = $cities;
        parent::__construct($context, $registry, $formFactory, $orderConfig,
                $reportRule, $data);
    }

    /**
     * 
     * @return $this
     */
    protected function _prepareForm()
    {
        parent::_prepareForm();

        /** @var \Magento\Framework\Data\Form\Element\Fieldset $fieldset */
        $fieldset = $this->getForm()->getElement('base_fieldset');

        if (is_object($fieldset) && $fieldset instanceof \Magento\Framework\Data\Form\Element\Fieldset) {
            $collection = $this->cities->getCollection();
            $values = [];
            foreach ($collection as $city) {
                $values[$city->getId()] = $city->getName();
            }
            $fieldset->addField(
                    'cities', 'select',
                    [
                'name' => 'cities',
                'label' => 'City',
                'values' => $values
                    ], 'cities'
            );
        }

        return $this;
    }

}
