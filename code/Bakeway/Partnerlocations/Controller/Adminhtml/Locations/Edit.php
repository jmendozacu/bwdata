<?php
namespace Bakeway\Partnerlocations\Controller\Adminhtml\Locations;

class Edit extends \Magento\Backend\App\Action
{
    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */

    protected $resultPageFactory;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory

    )
    {
        parent::__construct($context);

        $this->resultPageFactory = $resultPageFactory;


    }

    public function execute()
    {
        $this->resultPage = $this->resultPageFactory->create();
        $this->resultPage->getConfig()->getTitle()->set('Store Addresses');

        if(isset($data['page_sub_loc_id'])){
            $data['sub_loc_id'] =  $data['page_sub_loc_id'];
        }else{
            $data['sub_loc_id'] =  '';
        }

        // 1. Get ID and create model
        $id = $this->getRequest()->getParam('id');

        $model = $this->_objectManager->create('Bakeway\Partnerlocations\Model\Partnerlocations');

        $registryObject = $this->_objectManager->get('Magento\Framework\Registry');

        // 2. Initial checking
        if ($id) {
            $model->load($id);
            if (!$model->getId()) {
                $this->messageManager->addError(__('This row no longer exists.'));
                $this->_redirect('*/*/');
                return;
            }
        }


        // 3. Set entered data if was error when we do save
        $data = $this->_objectManager->get('Magento\Backend\Model\Session')->getFormData(true);
        if (!empty($data)) {
            $model->setData($data);
        }

        $registryObject->register('partnerlocations_locations', $model);
        $this->_view->loadLayout();
        $this->_view->getLayout()->initMessages();
        $this->_view->renderLayout();


    }
}
