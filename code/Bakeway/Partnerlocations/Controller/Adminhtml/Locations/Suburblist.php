<?php
namespace Bakeway\Partnerlocations\Controller\Adminhtml\Locations;

use \Bakeway\Cities\Model\Cities as CitiesCollection;
use \Bakeway\Partnerlocations\Model\ResourceModel\Partnerlocations\Collection as Locationcollection;
use \Bakeway\SubLocations\Model\ResourceModel\SubLocations\Collection as Sublocationscollection;

class Suburblist extends \Magento\Backend\App\Action
{



    protected $resultPageFactory;

    /**
     * @var Locationcollection
     */
    protected $locationCollection;

    /**
     * @var  CitiesCollection
     */
    protected $citiesCollection;

    /**
     * @var Sublocationscollection
     */
    protected  $sublocationscollection;


    protected  $resultJsonFactory;
    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    /**
     * Suburblist constructor.
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     * @param Locationcollection $locationCollection
     * @param CitiesCollection $citiesCollection
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        Locationcollection $locationCollection,
        CitiesCollection $citiesCollection,
        Sublocationscollection $sublocationscollection,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory

    )
    {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->locationCollection = $locationCollection;
        $this->citiesCollection = $citiesCollection;
        $this->sublocationscollection = $sublocationscollection;
        $this->resultJsonFactory = $resultJsonFactory;

    }

    public function execute()
    {
        $result = $this->resultJsonFactory->create();
        $post = $this->getRequest()->getParams();
        $cityid = $post['city_id'];
        $suburbList = [];
        $suburbCollection = $this->sublocationscollection
            ->addFieldToFilter('city_id',$cityid);
        $suburbTot = $suburbCollection->count();
        if($suburbTot > 0)
        {
            foreach ($suburbCollection as $suburbData)
            {
               // echo   $suburbData['id'];
                $suburbList[] =     "<option value=".$suburbData['id'].">".$suburbData['area_name']."</option>";
            }

        }

       return $this->getResponse()->representJson(
            $this->_objectManager->get(
                'Magento\Framework\Json\Helper\Data'
            )->jsonEncode($suburbList)
        );
    }

}