<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace Bakeway\Brands\Controller\Adminhtml\Index;

/**
 * Description of Edit
 *
 * @author Admin
 */
class Edit extends \Magento\Backend\App\Action
{

    /** @var \Magento\Framework\View\Result\PageFactory */
    protected $resultPageFactory;
    
    /** @var \Bakeway\Brands\Model\BrandsFactory */
    protected $brands;
    
    /** @var \Magento\Framework\Registry */
    protected $registry;

    /*
     * Construct
     */

    public function __construct(\Magento\Backend\App\Action\Context $context,
            \Magento\Framework\View\Result\PageFactory $resultPageFactory,
            \Bakeway\Brands\Model\BrandsFactory $brands,
            \Magento\Framework\Registry $registry)
    {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->brands = $brands;
        $this->registry = $registry;
    }

    /**
     * Grid
     */
    public function execute()
    {
        $brandId = $this->getRequest()->getParam('entity_id');
        $model = $this->brands->create();

        if ($brandId) {
            $model->load($brandId);
            if (!$model->getId()) {
                $this->messageManager->addError(__('This brand no longer exists.'));
                $this->_redirect('*/*/');
                return;
            }
        }

        $data = $this->_session->getBrandData(true);
        if (!empty($data)) {
            $model->setData($data);
        }
        $this->registry->register('brands', $model);

        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Webkul_Marketplace::brands');
        $resultPage->getConfig()->getTitle()->prepend(__('Manage Brands'));

        return $resultPage;
    }

}
