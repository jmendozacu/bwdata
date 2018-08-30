<?php
/**
 *
 * Copyright © 2015 Bakewaycommerce. All rights reserved.
 */
namespace Bakeway\Import\Controller\Import;

use \Magento\Framework\App\Action\Context;
use \Magento\Framework\View\Result\PageFactory;
use \Magento\Framework\App\RequestInterface;
use  \Magento\Catalog\Model\ProductFactory as ProductFactory;
use Symfony\Component\Config\Definition\Exception\Exception;

class Attribute extends \Magento\Framework\App\Action\Action
{

    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var  \Magento\Framework\ObjectManagerInterface
     */
    protected $_storeManager;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $_date;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory
     */
    protected $productCollection;

    /**
     * @var ProductFactory
     */
    protected $productFactory;
    /**
     * Save constructor.
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $date
     */

    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollection,
         ProductFactory $productFactory
    ){
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->_storeManager = $storeManager;
        $this->_date = $date;
        $this->productCollection = $productCollection;
        $this->productFactory = $productFactory;
    }

    /**
     * @return get base url
     */
    public function getBaseUrl()
    {
        return $this->_storeManager->getStore()->getBaseUrl();
    }

    /**
     * Index action
     *
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {
        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/updateattributes.log');
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $getProducts = $this->productCollection->create()->getAllIds();

        if(count($getProducts) > 0){
            $logger->info("process start ".date("d m Y H:i:s"));

            $array_product = $getProducts; //product Ids
            $value = 1; //amount
            $productActionObject = $objectManager->create('Magento\Catalog\Model\Product\Action');
            $productActionObject->updateAttributes($array_product, array('advance_order_intimation_unit' => $value), 0);

            $logger->info("process end ".date("d m Y H:i:s"));
        }
    }
}
