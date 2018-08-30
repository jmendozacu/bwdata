<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace Bakeway\Brands\Controller\Adminhtml\Index;

/**
 * Description of Delete
 *
 * @author Admin
 */
class Delete extends \Magento\Backend\App\Action
{

    /** @var \Bakeway\Brands\Model\BrandsFactory */
    protected $brandsFactory;

    /**
     * Construct
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Bakeway\Brands\Model\BrandsFactory $brandsFactory
     */
    public function __construct(\Magento\Backend\App\Action\Context $context,
            \Bakeway\Brands\Model\BrandsFactory $brandsFactory)
    {
        parent::__construct($context);
        $this->brandsFactory = $brandsFactory;
    }

    /**
     * Delete brand
     */
    public function execute()
    {
        $brandId = (int) $this->getRequest()->getParam('entity_id');
        if ($brandId) {
            $brandModel = $this->brandsFactory->create();
            $brandModel->load($brandId);

            if (!$brandModel->getEntityId()) {
                $this->messageManager->addError(__('This brand no longer exists.'));
            } else {
                try {
                    $brandModel->delete();
                    $this->messageManager->addSuccess(__('The brand has been deleted.'));

                    $this->_redirect('*/*/');
                    return;
                } catch (\Exception $e) {
                    $this->messageManager->addError($e->getMessage());
                    $this->_redirect('*/*/edit',
                            ['entity_id' => $brandModel->getEntityId()]);
                }
            }
        }
    }

}
