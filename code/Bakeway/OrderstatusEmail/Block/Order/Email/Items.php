<?php

/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
/**
 * Sales Order Email order items
 *
 * @author     Magento Core Team <core@magentocommerce.com>
 */
namespace Bakeway\OrderstatusEmail\Block\Order\Email;

use Webkul\Marketplace\Model\ResourceModel\Orders\CollectionFactory as MarketplaceOrderFactory;
use Webkul\Marketplace\Helper\Data as MarketplaceHelper;
use Magento\Sales\Model\Order\Creditmemo\Item as CreditmemoItem;
use Magento\Sales\Model\Order\Invoice\Item as InvoiceItem;
use Magento\Sales\Model\Order\Item as OrderItem;
use Bakeway\Partnerlocations\Model\ResourceModel\Partnerlocations\CollectionFactory as LocationCollection;
use Magento\Sales\Model\Order\Address\Renderer as AddressRenderer;
use Magento\Sales\Model\OrderFactory as OrderFactory;

class Items
        extends \Magento\Sales\Block\Items\AbstractItems
{

    /**
     * @var MarketplaceOrderFactory
     */
    protected $marketplaceOrderFactory;

    /**
     * @var MarketplaceHelper
     */
    protected $marketplaceHelper;

    /**
     * @var LocationCollection
     */
    protected $locationCollection;

    /**
     * @var AddressRenderer
     */
    protected $addressRenderer;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Webkul\Marketplace\Model\ProductFactory
     */
    protected $productFactory;

    /**
     * @var OrderFactory
     */
    protected $orderFactory;
    
    /**
     * @param MarketplaceOrderFactory $marketplaceOrderFactory
     * @param MarketplaceHelper $marketplaceHelper
     * @param LocationCollection $locationCollection
     * @param AddressRenderer $addressRenderer
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param array $data
     */
    public function __construct(
    MarketplaceOrderFactory $marketplaceOrderFactory,
            MarketplaceHelper $marketplaceHelper,
            LocationCollection $locationCollection,
            AddressRenderer $addressRenderer,
            \Magento\Framework\View\Element\Template\Context $context,
            \Webkul\Marketplace\Model\ProductFactory $productFactory,
             OrderFactory $orderFactory,
            array $data = []
    )
    {
        $this->marketplaceOrderFactory = $marketplaceOrderFactory;
        $this->marketplaceHelper = $marketplaceHelper;
        $this->locationCollection = $locationCollection;
        $this->addressRenderer = $addressRenderer;
        $this->productFactory = $productFactory;
        $this->orderFactory = $orderFactory;
        parent::__construct($context, $data);
    }

    /**
     * @param mixed $item
     * @return mixed
     */
    public function getSku($item)
    {
        if ($item->getOrderItem()->getProductOptionByCode('simple_sku')) {
            return $item->getOrderItem()->getProductOptionByCode('simple_sku');
        } else {
            return $item->getSku();
        }
    }

    /**
     * Return product additional information block
     *
     * @return \Magento\Framework\View\Element\AbstractBlock
     */
    public function getProductAdditionalInformationBlock()
    {
        return $this->getLayout()->getBlock('additional.product.info');
    }

    /**
     * Get the html for item price
     *
     * @param OrderItem|InvoiceItem|CreditmemoItem $item
     * @return string
     */
    public function getItemPrice($item)
    {
        $block = $this->getLayout()->getBlock('item_price');
        $block->setItem($item);
        return $block->toHtml();
    }

    public function getSellerAddressDetails($item,
            $salesOrder = null)
    {
        $result = [];
        $sellerId = false;
        $storeUniqueName = false;
        $isConglomerate = false;
        $collection = $this->marketplaceOrderFactory->create();
        if ($salesOrder === null) {
            $collection->addFieldToFilter('order_id',
                    $item->getOrder()->getEntityId());
        } else {
            $collection->addFieldToFilter('order_id',
                    $salesOrder->getData('entity_id'));
        }

        foreach ($collection as $order) {
            $sellerId = $order->getData('seller_id');
            $isConglomerate = $this->marketplaceHelper->isConglomerate($sellerId);
            if ($isConglomerate === true) {
                if ($salesOrder === null) {
                    $storeUniqueName = $item->getOrder()->getData('store_unique_name');
                } else {
                    $storeUniqueName = $salesOrder->getData('store_unique_name');
                }
            }
        }

        if ($sellerId !== false) {
            $locationsColl = $this->locationCollection->create()
                    ->addFieldToFilter('seller_id', $sellerId);
            $sellerColl = $this->marketplaceHelper->getSellerDataBySellerId($sellerId);
            if ($sellerColl->count() > 0) {
                $sellerData = $sellerColl->getFirstItem();
                $result['business_name'] = $sellerData->getData('business_name');
                $result['phone_number'] = $sellerData->getData('store_manager_mobile_no');
                if ($isConglomerate !== false) {
                    if ($storeUniqueName !== false) {
                        $locationsColl->addFieldToFilter('store_unique_name',
                                $storeUniqueName);
                    }
                }
                if ($locationsColl->count() > 0) {
                    $locationData = $locationsColl->getFirstItem();
                    $result['street_address'] = $locationData->getData('store_street_address');
                }
                return $result;
            }
        }
        return false;
    }

    public function getDeliveryAddress($order)
    {
        $address = $order->getShippingAddress();
        return $this->addressRenderer->format($address, 'html');
    }

    public function getGuestTokenUrl()
    {
        return $this->_scopeConfig->getValue('react_site_settings/react_settings_general/guest_track_url',
                        \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    public function getCustomerLoginUrl()
    {
        return 'https://bakeway.com';
    }

    public function getSellerInfo($productId,
            $order)
    {
        $_collection = $this->productFactory->create()->getCollection();
        $_collection->getSelect()->joinLeft(['mp_userdata' => $_collection->getTable('marketplace_userdata')],
                        'main_table.seller_id = mp_userdata.seller_id',
                        ['store_city'])
                ->where('main_table.mageproduct_id=' . $productId);
        if (!empty($_collection->getSize())) {
            $sellerId = $_collection->getFirstItem()->getData('seller_id');
        } else {
            $sellerId = 0;
        }
        $isConglomerate = $this->marketplaceHelper->isConglomerate($sellerId);
        if ($isConglomerate === true) {
            $storeUniqueName = $order->getData('store_unique_name');
        }

        if ($sellerId !== false) {
            $locationsColl = $this->locationCollection->create()
                    ->addFieldToFilter('seller_id', $sellerId);
            $sellerColl = $this->marketplaceHelper->getSellerDataBySellerId($sellerId);
            if ($sellerColl->count() > 0) {
                $sellerData = $sellerColl->getFirstItem();
                $result['business_name'] = $sellerData->getData('business_name');
                $result['phone_number'] = $sellerData->getData('store_manager_mobile_no');
                if ($isConglomerate !== false) {
                    if ($storeUniqueName !== false) {
                        $locationsColl->addFieldToFilter('store_unique_name',
                                $storeUniqueName);
                    }
                }
                if ($locationsColl->count() > 0) {
                    $locationData = $locationsColl->getFirstItem();
                    $result['street_address'] = $locationData->getData('store_street_address');
                }
                return $result;
            }
        }
        return false;
    }

    public function getCustomMessage($order)
    {
        $customMessage = $customernotes = $messageOnCard = "";
        $customMessagearray = [];
        $orderItemData = $order->getAllVisibleItems();

        if (!empty($order->getCustomerNotes())):
            $customernotes = $order->getCustomerNotes();
        endif;

        foreach ($orderItemData as $orderItems) {
            $customMessage = $orderItems->getCustomMessage();
            $messageOnCard = $orderItems->getMessageOnCard();
            if (isset($customMessage) && !empty($customMessage)) {
                $customMessagearray['custom_message'] = $orderItems->getCustomMessage();
            }

            if (isset($messageOnCard) && !empty($messageOnCard)) {
                $customMessagearray['message_on_card'] = $orderItems->getMessageOnCard();
            }
        }
        /*         * firsi item custom message* */
        if (isset($customMessagearray['custom_message']) && !empty($customMessagearray['custom_message'])) {
            $customMessage = $customMessagearray['custom_message'];
        }

        if (!empty($customMessagearray['message_on_card'])):
            $messageOnCard = $customMessagearray['message_on_card'];
        endif;

        return array($customMessage, $customernotes, $messageOnCard);
    }
    
    /**
     * Fetch photo image from order
     * @param type $order
     */
    public function getPhotoCakeImage($order) 
    {
        $productOptions = $order->getProductOptions();
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $order = $objectManager->create('Magento\Sales\Model\Order')->load($order->getId());
        $orderItems = $order->getAllItems();
        $productOption = array();
        $photoImageUrl = '';
        foreach($orderItems as $item){
        $productOption = $item->getData('product_options');
            if (isset($productOption['info_buyRequest']['options'])
                && !empty($productOption['info_buyRequest']['options']))
            {   
                $options = $productOption['info_buyRequest']['options'];
                foreach($options as $option){
                    $photoImageUrl = $this->_storeManager
                            ->getStore()
                            ->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) .$option['order_path'];
                }
            }
        }
       
        return $photoImageUrl;
    }


    public function getStoreLocalityfromStoreUniqueName($storeUniqueName,$orderId)
    {
        $collection = $this->orderFactory->create()->getCollection();
        $collection->getSelect()->joinLeft(array('mo' => 'marketplace_orders'),
            'main_table.entity_id = mo.order_id',
            array('mo.seller_id'));
        $collection->getSelect()->joinLeft(array('bpl' => 'bakeway_partner_locations'),
            'mo.seller_id = bpl.seller_id',
            array('bpl.store_locality_area','bpl.store_unique_name'));
        $collection->addFieldToFilter("entity_id",["eq"=>$orderId]);
        $collection->getSelect()->group("main_table.entity_id");
        $collection->addFilterToMap('store_unique_name', 'bpl.store_unique_name');
        $collection->getSelect()->where('bpl.store_unique_name=?',$storeUniqueName);
        $storeLocalityName = "";
        $storeLocalityName = [];
        if(count($collection) > 0){
            foreach($collection as $colllectionData){
                $storeLocalityName = $colllectionData['store_locality_area'];
            }

        }
        if(isset($storeLocalityName) && !empty($storeLocalityName)){
            return $storeLocalityName;
        }
        return;


    }

}
