<?php
namespace Bakeway\Partnerlocations\Controller\Adminhtml\Locations;

use Bakeway\Partnerlocations\Model\ResourceModel\Partnerlocations\Collection as LocationsCollection;
use Bakeway\ProductApi\Helper\Data as ProductApiHelper;
use Bakeway\Partnerlocations\Helper\Data as PartnerlocationHelper;
use Bakeway\OrderstatusEmail\Model\Email as OrderstatusEmailModel;
use Symfony\Component\Config\Definition\Exception\Exception;

class Delete extends \Magento\Backend\App\Action
{
    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $resultPageFactory;
    protected $auth;


    /**
     * @var  \Webkul\Marketplace\Model\Seller
     */
    protected $sellermodel;

    /**
     * @var  \Webkul\Marketplace\Helper\Dat
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

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
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
        $this->locationCollection = $locationCollection;
        $this->sellermodel = $sellermodel;
        $this->marketplacehelper = $marketplacehelper;
        $this->productApiHelper = $productApiHelper;
        $this->partnerlocationHelper = $partnerlocationHelper;
        $this->auth = $context->getAuth();
        $this->orderstatusEmailModel = $orderstatusEmailModel;
    }

    public function execute()
    {
        $id = $this->getRequest()->getParam('id');
        try {
            $model = $this->_objectManager->create('Bakeway\Partnerlocations\Model\Partnerlocations')->load($id);

            $seller_id = $this->partnerlocationHelper->getSellerId($id);
            try{

                /**
                 * email triggring
                 */
                if(!empty($this->partnerlocationHelper->getUrlUpdateALertTriggerStatus())){
                    $content = $model['store_locality_area'];
                    $subject = "Store Location ".$model['store_locality_area']." Has Removed";
                    $this->emailTriggering($content,$subject,$seller_id);
                }


                $model->delete();

            }catch(Exception $e){
             echo $e->getMessage();
            }

            /**condition for store count specific start**/
            $collection = $this->_objectManager->create('Bakeway\Partnerlocations\Model\Partnerlocations')->getCollection()
            ->addFieldToFilter('seller_id', $seller_id);

            $storeCount =  count($collection);
            $sellerData = $this->marketplacehelper->getSellerEntityId( $seller_id);
            $oldIsConglomerate = $sellerData->getData('is_conglomerate');
            $oldStoreLocalityArea = $sellerData->getData('store_locality_area');
            $sellerEntityId = $sellerData->getData('entity_id');


            if($storeCount == 1):
                $sellerUpdate = $this->sellermodel->load($sellerEntityId);
                $newStoreLocalityArea = $this->partnerlocationHelper->getSingleLocality($seller_id);
                $sellerUpdate->setIsConglomerate(0);
                $newisCognglomarate = 0;
                $sellerUpdate->save();
            else:
                $sellerUpdate = $this->sellermodel->load($sellerEntityId);
                $sellerUpdate->setIsConglomerate(1);
                $newisCognglomarate = 1;
                $sellerUpdate->save();
            endif;
            
             $this->messageManager->addSuccess(__('Store Address Deleted successfully !.'));


            if($storeCount == 1){

                /**
                 * email triggring when new location add
                 */
                if(!empty($this->partnerlocationHelper->getUrlUpdateALertTriggerStatus())){
                    $content = null;
                    $subject = "Seller ".$seller_id." converted from Master login to Normal" ;
                    $this->emailTriggering($content,$subject,$seller_id);
                }
            }

            if($oldIsConglomerate !=  $newisCognglomarate)
            { 
                $locality = $this->partnerlocationHelper->getSingleLocality($seller_id);
                $this->productApiHelper->createVendorUrl($seller_id,$locality);
                $this->productApiHelper->createSellerAllProductUrls($seller_id,$locality);
                $this->_redirect('customer/index/edit/', array('id' => $seller_id,'_current' => true,'mode'=>'localtion_save_target'));
                $this->messageManager->addSuccess(__('The Information is Saved.'));
            }

            if ($this->getRequest()->getParam('back')) {
                 $this->_redirect('customer/index/edit/', array('id' => $seller_id,'_current' => true,'mode'=>'localtion_save_target'));
                 return;
             }
             $this->_redirect('customer/index/edit/', array('id' => $seller_id,'_current' => true,'mode'=>'localtion_save_target'));

            /**condition for store count specific end**/


        } catch (\Exception $e) {
            $this->messageManager->addError($e->getMessage());
        }
        $this->_redirect('customer/index/edit/', array('id' => $seller_id,'_current' => true,'mode'=>'localtion_save_target'));

    }

    public function getCurrentadminemail()
    {
        $loginUserEmail = $this->auth->getUser()->getEmail();
        if (!empty($loginUserEmail)) {
            return $loginUserEmail;
        }
        return;
    }

    /**
     * @param $storeLocationName
     * @param $content
     * @param $subject
     * @param $sellerId
     */
    function emailTriggering($content,$subject,$sellerid){
        $this->orderstatusEmailModel->sendUrlupdateAlertEmail($content, $subject ,$sellerid);
    }


}