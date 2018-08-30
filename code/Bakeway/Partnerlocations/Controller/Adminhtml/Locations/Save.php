<?php
namespace Bakeway\Partnerlocations\Controller\Adminhtml\Locations;

use Magento\Framework\App\Filesystem\DirectoryList;
use Bakeway\Partnerlocations\Model\ResourceModel\Partnerlocations\Collection as LocationsCollection;
use Bakeway\ProductApi\Helper\Data as ProductApiHelper;
use Bakeway\Partnerlocations\Helper\Data as PartnerlocationHelper;
use Bakeway\OrderstatusEmail\Model\Email as OrderstatusEmailModel;

class Save extends \Magento\Backend\App\Action
{
    protected $resultPageFactory;

    /*
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */

    protected $timezoneInterface;
    /**
     * @var  \Webkul\Marketplace\Model\Seller
     */
    protected $sellermodel;

    /**
     * @var  \Webkul\Marketplace\Helper\Data
     */
    protected $marketplacehelper;

    /**
     * @var ProductApiHelper
     */
    protected $productApiHelper;
    /**
     * @var PartnerlocationHelper
     */
    protected $partnerlocationHelper;
    /**
     * @var LocationsCollection
     */
    protected $locationCollection;

    /**
     * @var OrderstatusEmailModel
     */
    protected  $orderstatusEmailModel;
    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezoneInterface,
        LocationsCollection $locationCollection,
        PartnerlocationHelper $partnerlocationHelper,
        \Webkul\Marketplace\Model\Seller $sellermodel,
        \Webkul\Marketplace\Helper\Data $marketplacehelper,
        ProductApiHelper $productApiHelper,
        OrderstatusEmailModel $orderstatusEmailModel

    )
    {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->timezoneInterface = $timezoneInterface;
        $this->locationCollection = $locationCollection;
        $this->sellermodel = $sellermodel;
        $this->marketplacehelper = $marketplacehelper;
        $this->productApiHelper = $productApiHelper;
        $this->partnerlocationHelper = $partnerlocationHelper;
        $this->orderstatusEmailModel = $orderstatusEmailModel;

    }

    public function execute()
    {

        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/locationdebugging.log');
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);
        $data = $this->getRequest()->getParams();
        $savedStoreUniqueName= "";

        if(isset($data['page_sub_loc_id'])){
            $data['sub_loc_id'] =  $data['page_sub_loc_id'];
        }elseif(isset($data['sub_loc_id'])){
            $data['sub_loc_id'] =  $data['sub_loc_id'];
        }else{
            $data['sub_loc_id'] =  '';
        }

        $updated_at = $this->timezoneInterface->date()->format('m/d/y H:i:s');

        if ($data) {
            $model = $this->_objectManager->create('Bakeway\Partnerlocations\Model\Partnerlocations');

            $id = $this->getRequest()->getParam('id');

            $sellerid = $this->getRequest()->getParam('seller_id');

            if ($id) {
                $model->load($id);
                $oldLocality = $model['store_locality_area'];
            }

            $model->setData($data);
            $model->setUpdatedAt($updated_at);
            try {

                $oldStoreLocalityArea = $this->partnerlocationHelper->getSingleLocality($sellerid);
                /*
                *
                * store unique name
                */
                $postUniqueName = $data['store_locality_area'];
                $postUniqueNamereturn =  $this->partnerlocationHelper->checkStoreUniqueName($postUniqueName, $sellerid);

                if(!empty($postUniqueNamereturn))
                {
                    $postUniqueNamereturn = $postUniqueNamereturn;
                }else{
                    $postUniqueNamereturn = $postUniqueName;
                }


                if(isset($data['store_unique_name'])){
                    $savedStoreUniqueName = $data['store_unique_name'];
                }


                if(!empty($savedStoreUniqueName))
                {
                    $postUniqueNamereturn = $savedStoreUniqueName;
                }else{
                    $postUniqueNamereturn = $postUniqueNamereturn;
                }

                if(empty($id)){
                    /**
                     * email triggring when new location add
                     */
                    if(!empty($this->partnerlocationHelper->getUrlUpdateALertTriggerStatus())){
                        $content = $postUniqueName;
                        $subject = "New Store Location ".$postUniqueName. " added";
                        $this->emailTriggering($content,$subject,$sellerid);
                    }

                }else{
                    /**
                     * editing store
                     * email is getting trigger when store locality chages
                     */

                    $newLocality = $postUniqueName;
                    if($oldLocality != $newLocality){
                        $content = $postUniqueName;
                        $subject = "Store Location ".$oldLocality." has renamed ".$newLocality;
                        $this->emailTriggering($content,$subject,$sellerid);
                    }

                }

                //echo $postUniqueNamereturn;die;
               // $checkStoreName = $this->partnerlocationHelper->checkStoreName($postUniqueNamereturn,$sellerid);

                $model->setStoreUniqueName($postUniqueNamereturn);
                $model->save();
                $this->messageManager->addSuccess(__('The Information is Saved.'));
                $this->_objectManager->get('Magento\Backend\Model\Session')->setFormData(false);
                $newStoreLocalityArea = $data['store_locality_area'];
                /**condition for store count specific start**/
                $collection = $this->_objectManager->create('Bakeway\Partnerlocations\Model\Partnerlocations')->getCollection()
                    ->addFieldToFilter('seller_id', $sellerid);
                 $storeCount =  count($collection);
                 $sellerData = $this->marketplacehelper->getSellerEntityId($sellerid);
                 $oldIsConglomerate = $sellerData->getData('is_conglomerate');
                 $sellerEntityId = $sellerData->getData('entity_id');

                 if($storeCount == 1):
                     $sellerUpdate = $this->sellermodel->load($sellerEntityId);
                     $newStoreLocalityArea = $newStoreLocalityArea;
                     $sellerUpdate->setIsConglomerate(0);
                     $newisCognglomarate = 0;
                     $sellerUpdate->save();
                 else:
                     $sellerUpdate = $this->sellermodel->load($sellerEntityId);
                     $sellerUpdate->setIsConglomerate(1);
                     $newisCognglomarate = 1;
                     $sellerUpdate->save();
                 endif;

                if($storeCount == 1){

                    /**
                     * email triggring when new location add
                     */


                    $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/storealert.log');
                    $logger = new \Zend\Log\Logger();
                    $logger->addWriter($writer);
                    $logger->info("congvalue".$newisCognglomarate);
                    if(!empty($this->partnerlocationHelper->getUrlUpdateALertTriggerStatus()) && !empty($newisCognglomarate)){
                        $content = $postUniqueName;
                        $subject = "Seller ".$sellerid." converted from Master login to Normal" ;
                        $this->emailTriggering($content,$subject ,$sellerid);
                    }
                }else{

                    /**
                     * email triggring when new location add
                     */
                    if($storeCount==2){
                        if(!empty($this->partnerlocationHelper->getUrlUpdateALertTriggerStatus())){
                            $content = $postUniqueName;
                            $subject = "Seller ".$sellerid." converted to Master login" ;
                            if($oldIsConglomerate != 1){
                                $this->emailTriggering($content,$subject ,$sellerid);
                            }

                        }
                    }

                }

                if($oldIsConglomerate !=  $newisCognglomarate)
                {
                    //$locality = $this->partnerlocationHelper->getSingleLocality($sellerid);
                    $locality =  $newStoreLocalityArea;
                    $this->productApiHelper->createVendorUrl($sellerid,$locality);
                    $this->productApiHelper->createSellerAllProductUrls($sellerid,$locality);

                    /**
                     * email triggring when new location add
                     */
                    if(!empty($this->partnerlocationHelper->getUrlUpdateALertTriggerStatus())){
                        $content = $postUniqueName;
                        $subject = "New Store Location ".$postUniqueName. " added";
                        $this->emailTriggering($content,$subject,$sellerid);
                    }

                    $this->_redirect('customer/index/edit/', array('id' => $sellerid,'_current' => true,'mode'=>'localtion_save_target'));
                }
                if($storeCount == 1){
                    if($oldStoreLocalityArea != $newStoreLocalityArea || empty($oldStoreLocalityArea)){
                        //$locality = $this->partnerlocationHelper->getSingleLocality($sellerid);
                        $locality =  $newStoreLocalityArea;
                        $this->productApiHelper->createVendorUrl($sellerid,$locality);
                        $this->productApiHelper->createSellerAllProductUrls($sellerid,$locality);
                        $this->_redirect('customer/index/edit/', array('id' => $sellerid,'_current' => true,'mode'=>'localtion_save_target'));
                    }
                }





                if ($this->getRequest()->getParam('back')) {
                    $this->_redirect('customer/index/edit/', array('id' => $sellerid,'_current' => true,'mode'=>'localtion_save_target'));
                    return;
                }
                $this->_redirect('customer/index/edit/', array('id' => $sellerid,'_current' => true,'mode'=>'localtion_save_target'));

                /**condition for store count specific end**/
                return;
            } catch (\Magento\Framework\Model\Exception $e) {
                $this->messageManager->addError($e->getMessage());
            } catch (\RuntimeException $e) {
                $this->messageManager->addError($e->getMessage());
            } catch (\Exception $e) {
                $this->messageManager->addException($e, __('Something went wrong while saving the Store.'));
            }

            $this->_getSession()->setFormData($data);
            $this->_redirect('customer/index/edit/', array('id' => $sellerid,'_current' => true,'mode'=>'localtion_save_target'));
            return;
        }
        $this->_redirect('*/*/');
    }


    /**
     * @param $storeLocationName
     * @param $content
     * @param $subject
     * @param $sellerid
     */
    function emailTriggering($content,$subject,$sellerid){
        $this->orderstatusEmailModel->sendUrlupdateAlertEmail($content ,$subject ,$sellerid);
    }

}
