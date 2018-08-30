<?php

namespace Bakeway\ReviewRating\Block\Adminhtml\Rating\Edit\Tab;

use Bakeway\ReviewRating\Helper\Data as ReviewRatinghelper;

class Form extends \Magento\Review\Block\Adminhtml\Rating\Edit\Tab\Form
{
    /**
     * System store
     *
     * @var \Magento\Store\Model\System\Store
     */
    protected $systemStore;

    /**
     * @var string
     */
    protected $_template = 'rating/form.phtml';

    /**
     * Session
     *
     * @var \Magento\Framework\Session\SessionManagerInterface
     */
    protected $session;

    /**
     * Option factory
     *
     * @var \Magento\Review\Model\Rating\OptionFactory
     */
    protected $optionFactory;

    /**
     * @var \Magento\Framework\Data\Form\Element\Fieldset
     */
    protected $fieldset;

    /**
     * @var ReviewRatinghelper
     */
    protected $reviewRatinghelper;

    /**
     * @var \Magento\Review\Model\RatingFactory
     */
    protected $ratingFactory;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param \Magento\Review\Model\Rating\OptionFactory $optionFactory
     * @param \Magento\Framework\Session\SessionManagerInterface $session
     * @param \Magento\Store\Model\System\Store $systemStore
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\Review\Model\Rating\OptionFactory $optionFactory,
        \Magento\Framework\Session\SessionManagerInterface $session,
        \Magento\Store\Model\System\Store $systemStore,
        ReviewRatinghelper $reviewRatinghelper,
        \Magento\Review\Model\RatingFactory $ratingFactory,
        array $data = []
    ) {
        $this->optionFactory = $optionFactory;
        $this->session = $session;
        $this->systemStore = $systemStore;
        $this->reviewRatinghelper = $reviewRatinghelper;
        $this->ratingFactory = $ratingFactory;
        parent::__construct($context, $registry, $formFactory, $optionFactory,$session,$systemStore,$data);
    }
    /**
     * Add rating fieldset to form
     *
     * @return void
     */
    protected function addRatingFieldset()
    {
        $this->initFieldset('rating_form', ['legend' => __('Rating Title')]);
        $ratingData = $this->ratingFactory->create()->load($this->getRequest()->getParam('id'));
        $this->getFieldset('rating_form')->addField(
            'rating_code',
            'text',
            [
                'name' => 'rating_code',
                'label' => __('Default Value'),
                'class' => 'required-entry',
                'required' => true
            ]
        );

        /**
         * adding rating type field to rating form
         */
        $this->getFieldset('rating_form')->addField(
            'rating_type',
            'select',
            [
                'name' => 'rating_type',
                'label' => __('Rating Type'),
                'class' => 'required-entry',
                'required' => true,
                'values' => $this->reviewRatinghelper->getRatingType()
            ]
        );

        /**
         * adding question type field to rating form
         */
        $this->getFieldset('rating_form')->addField(
            'q_type',
            'select',
            [
                'name' => 'q_type',
                'label' => __('Question Type'),
                'class' => 'required-entry',
                'required' => true,
                'values' => $this->reviewRatinghelper->getRatingQuestionTypes()
            ]
        );

        foreach ($this->systemStore->getStoreCollection() as $store) {
            $this->getFieldset('rating_form')->addField(
                'rating_code_' . $store->getId(),
                'text',
                ['label' => $store->getName(), 'name' => 'rating_codes[' . $store->getId() . ']']
            );
        }
        $this->setRatingData();
    }

}