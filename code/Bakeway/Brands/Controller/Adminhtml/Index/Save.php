<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace Bakeway\Brands\Controller\Adminhtml\Index;

/**
 * Description of Save
 *
 * @author Admin
 */
class Save extends \Magento\Backend\App\Action
{

    /** @var \Bakeway\Brands\Model\BrandsFactory */
    protected $brandFactory;

    /**
     * Construct
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Bakeway\Brands\Model\BrandsFactory $brandFactory
     */
    public function __construct(\Magento\Backend\App\Action\Context $context,
            \Bakeway\Brands\Model\BrandsFactory $brandFactory)
    {
        parent::__construct($context);
        $this->brandFactory = $brandFactory;
    }

    /**
     * Save Brand
     */
    public function execute()
    {
        $postData = $this->getRequest()->getPost();

        if ($postData) {
            $brandModel = $this->brandFactory->create();
            $formData = $this->getRequest()->getParam('brand');
            $brandCollection = $brandModel->getCollection()->addFieldToFilter('brand_name',
                    ['like' => "%" . $formData['brand_name'] . "%"]);

            if (count($brandCollection) > 0) {
                $this->messageManager->addError("Brand name " . $formData['brand_name'] . " already exist.");
                $this->_redirect('*/*/');
                return;
            }

            $brandId = $this->getRequest()->getParam('entity_id');
            if ($brandId) {
                $brandModel->load($brandId);
            }

            $brandModel->setData($formData);

            try {
                // Save brand
                $brandModel->save();

                // Display success message
                $this->messageManager->addSuccess(__('The brand has been saved.'));

                // Check if 'Save and Continue'
                if ($this->getRequest()->getParam('back')) {
                    $this->_redirect('*/*/edit',
                            ['entity_id' => $brandModel->getEntityId(), '_current' => true]);
                    return;
                }

                // Go to grid page
                $this->_redirect('*/*/');
                return;
            } catch (\Exception $e) {
                $this->messageManager->addError($e->getMessage());
            }
            $this->_getSession()->setFormData($formData);
            $this->_redirect('*/*/edit', ['entity_id' => $brandId]);
        }
    }

}
