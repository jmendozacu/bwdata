<?php
namespace Bakeway\Partnerlocations\Controller\Adminhtml\Locations;

use Bakeway\Partnerlocations\Model\ResourceModel\Partnerlocations\Collection as LocationsCollection;
use Bakeway\ProductApi\Helper\Data as ProductApiHelper;
use Bakeway\Partnerlocations\Helper\Data as PartnerlocationHelper;

class Saveadmin  extends \Magento\Backend\App\Action
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
        ProductApiHelper $productApiHelper
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

    }

    public function execute()
    {
        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/checkerrorlog.log');
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);

        $data = $this->getRequest()->getParams();
        $created_at = $this->timezoneInterface->date()->format('m/d/y H:i:s');
        if ($data) {
            $model = $this->_objectManager->create('Bakeway\Partnerlocations\Model\Partnerlocations');

            $id = $this->getRequest()->getParam('id');

            $sellerid = $this->getRequest()->getParam('seller_id');

            if ($id) {
                $model->load($id);
            }

            $model->setData($data);
            $model->setCreatedAt($created_at);
            try {

                $oldStoreLocalityArea = $this->partnerlocationHelper->getSingleLocality($sellerid);
                /*
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
                $model->setStoreUniqueName($postUniqueNamereturn);
                $model->save();
                $newStoreLocalityArea = $data['store_locality_area'];

                /**condition for store count specific start**/
                $collection = $this->_objectManager->create('Bakeway\Partnerlocations\Model\Partnerlocations')->getCollection()
                    ->addFieldToFilter('seller_id', $sellerid);
                $storeCount =  count($collection);
                $sellerData = $this->marketplacehelper->getSellerEntityId( $sellerid);
                $oldIsConglomerate = $sellerData->getData('is_conglomerate');
                $sellerEntityId = $sellerData->getData('entity_id');


                if($storeCount == 1):
                    $sellerUpdate = $this->sellermodel->load($sellerEntityId);
                    $sellerUpdate->setIsConglomerate(0);
                    $newisCognglomarate = 0;
                    $sellerUpdate->save();
                else:
                    $sellerUpdate = $this->sellermodel->load($sellerEntityId);
                    $sellerUpdate->setIsConglomerate(1);
                    $newisCognglomarate = 1;
                    $sellerUpdate->save();
                endif;



               if($oldIsConglomerate !=  $newisCognglomarate)
                {
                    $locality = $this->partnerlocationHelper->getSingleLocality($sellerid);
                    $logger->info('111');
                    $logger->info($locality);
                    $this->productApiHelper->createVendorUrl($sellerid,$locality);
                    $this->productApiHelper->createSellerAllProductUrls($sellerid,$locality);
                    $this->_redirect('customer/index/edit/', array('id' => $sellerid,'_current' => true,'mode'=>'localtion_save_target'));
                    $this->messageManager->addSuccess(__('The Information is Saved.'));
                }
                if($storeCount == 1){

                    if($oldStoreLocalityArea != $newStoreLocalityArea || empty($oldStoreLocalityArea)){
                        $locality = $this->partnerlocationHelper->getSingleLocality($this->getCustomerId());
                        $this->productApiHelper->createVendorUrl($sellerid,$locality);
                        $this->productApiHelper->createSellerAllProductUrls($sellerid,$locality);
                        $this->_redirect('customer/index/edit/', array('id' => $sellerid,'_current' => true,'mode'=>'localtion_save_target'));
                        $this->messageManager->addSuccess(__('The Information is Saved.'));
                    }
                }



                if ($this->getRequest()->getParam('back')) {
                    $this->_redirect('customer/index/edit/', array('id' => $sellerid,'_current' => true,'mode'=>'localtion_save_target'));
                    return;
                }
                $this->messageManager->addSuccess(__('The Information is Saved.'));
                $this->_redirect('customer/index/edit/', array('id' => $sellerid,'_current' => true,'mode'=>'localtion_save_target'));

                /**condition for store count specific end**/

                $this->_objectManager->get('Magento\Backend\Model\Session')->setFormData(false);
             return;
            } catch (\Magento\Framework\Model\Exception $e) {
                $this->messageManager->addError($e->getMessage());
            } catch (\RuntimeException $e) {
                $this->messageManager->addError($e->getMessage());
            } catch (\Exception $e) {
                $this->messageManager->addException($e, __('Something went wrong while saving the banner.'));
            }

            $this->_getSession()->setFormData($data);
            return;
        }
    }

}
