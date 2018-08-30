<?php

namespace Bakeway\ReviewRating\Model;


use Bakeway\ReviewRating\Api\ReviewRepositoryInterface;
use Magento\Framework\Exception\NotFoundException;
use Bakeway\ReviewRating\Helper\Data as Reviewhelper;
use Symfony\Component\Config\Definition\Exception\Exception;
use Magento\Sales\Api\OrderRepositoryInterface as OrderRepositoryInterface;
use Magento\Sales\Model\Order\Address\Renderer as AddressRenderer;
use Magento\Catalog\Api\ProductRepositoryInterface as ProductRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface as StoreManagerInterface;
use Bakeway\HomeDeliveryshipping\Helper\Data as Homedeliveryhelper;
use Magento\Framework\Exception\InputException;
use Magento\Review\Model\Review as Review;

class ReviewRepository implements ReviewRepositoryInterface {

    /**
     * @var Reviewhelper
     */
    protected $reviewhelper;

    /**
     * @var OrderRepositoryInterface
     */
    protected  $orderRepositoryInterface;

    /**
     * @var AddressRenderer
     */
    protected $addressRenderer;

    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepositoryInterface;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManagerInterface;

    /**
     * @var Homedeliveryhelper
     */
    protected $homedeliveryhelper;

    /**
     * Core date model
     *
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $_date;
    /**
     * Rating model
     *
     * @var \Magento\Review\Model\RatingFactory
     */
    protected $_ratingFactory;
    /**
     * Review model
     *
     * @var \Magento\Review\Model\ReviewFactory
     */
    protected $_reviewFactory;
    /**
     * Rating resource model
     *
     * @var \Magento\Review\Model\ResourceModel\Rating\Option
     */
    protected $_ratingOptions;

    /**
     * @var Review
     */
    protected $review;


    public  function __construct(
        Reviewhelper $reviewhelper,
        OrderRepositoryInterface $orderRepositoryInterface,
        AddressRenderer $addressRenderer,
        ProductRepositoryInterface $productRepositoryInterface,
        StoreManagerInterface $storeManagerInterface,
        Homedeliveryhelper $homedeliveryhelper,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        \Magento\Review\Model\RatingFactory $ratingFactory,
        \Magento\Review\Model\ReviewFactory $reviewFactory,
        \Magento\Review\Model\ResourceModel\Review\CollectionFactory $collectionFactory,
        \Magento\Review\Model\Rating\Option $ratingOptions,
         Review $review
    )
    {
        $this->reviewhelper = $reviewhelper;
        $this->orderRepositoryInterface = $orderRepositoryInterface;
        $this->addressRenderer = $addressRenderer;
        $this->productRepositoryInterface = $productRepositoryInterface;
        $this->storeManagerInterface = $storeManagerInterface;
        $this->homedeliveryhelper = $homedeliveryhelper;
        $this->_date = $date;
        $this->_reviewsColFactory = $collectionFactory;
        $this->_ratingFactory = $ratingFactory;
        $this->_reviewFactory = $reviewFactory;
        $this->_ratingOptions = $ratingOptions;
        $this->review = $review;

    }

    /**
     * Get review details from token
     * @api
     * @param string $token
     * @return array
     * @throws \Magento\Framework\Exception\NotFoundException
     */

    public function getTokenReviewdetails($token)
    {
          $orderId = $this->reviewhelper->getOrderIdfromToken($token);

          if(isset($orderId)){

               $reviewStatus = Reviewhelper::PENDING_STATUS_STRING;
               $status = $this->reviewhelper->getOrderReviewStatus($orderId);
               $message ="Feedback is already submitted for this order.";

               if(!empty($status))
               {
                   $reviewStatus = Reviewhelper ::SUBMITTED_STATUS_STRING;
               }

               $orderDetails =  $this->orderRepositoryInterface->get($orderId);
               $deliveryAddressObj = $orderDetails->getShippingAddress();
               $deliveryAddress = $this->addressRenderer->format($deliveryAddressObj, 'text');

              /**
               * order items details
               */
               $items = $orderDetails->getAllItems();
               foreach ($items as $item) {
                  if ($item->getParentItem()) {
                      $itemsku[] =  $item->getSku();
                      continue;
                  }else{
                     $itemsku[] =  $item->getSku();

                  }

              }

              $mediaUrl = $this->storeManagerInterface->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
              if(count($itemsku) > 0)
              {
                  $items = [];
               foreach($itemsku as $sku)
                 {
                     $product =  $this->productRepositoryInterface->get($sku);
                     $_sellerid = $this->homedeliveryhelper ->getSelleridFSku($sku);
                     $sellerName = $this->reviewhelper->getSellerData($_sellerid);
                     $items[] = [
                         "product_name" => $product->getName(),
                         "product_image_url" =>  $mediaUrl."catalog/product".$product->getSmallImage(),
                         "sku" =>  $product->getSku(),
                         "seller_id" => $_sellerid,
                         "seller_name" => $sellerName
                     ];
                 }

              }

              /**
               * Rating question array
               */

               $ratingData = $this->reviewhelper->getRatingQuestions();
               $question = [];
               foreach($ratingData as $ratingQuestion)
               {
                  $question[] =[
                        "id" => $ratingQuestion['rating_id'],
                        "rating_code" => $ratingQuestion['rating_code'],
                        "for" => $this->reviewhelper->getEntityName($ratingQuestion['rating_type']),
                        "qType" => $ratingQuestion['q_type']
                      ];

               }
               $apiResponse = array(
                    'status' => $reviewStatus,
                    'message' => $message,
                    'order_id' => $orderDetails['increment_id'],
                    'products' =>  $items,
                    "shipping_address" => $deliveryAddress,
                    "delivery_date"=> $orderDetails->getData('delivery_time'),
                    "delivery_type"=> $orderDetails->getData('delivery_type'),
                    "questions" => $question
              );




              return json_decode(json_encode($apiResponse),false);


           }else{
               throw  new NotFoundException(__('Order Not Found'));

           }

    }


    /**
     * @param string $token
     * @param string $text
     * @param mixed $ratingData
     * @return mixed
     */
    public function saveReview($token, $text, $ratingData)
    {

        $orderId = $this->reviewhelper->getOrderIdfromToken($token);

        $storeId = 1; //to do this will come from api

        $message =$firstName =$lastName  =""; //definded varrible

        $status = $this->reviewhelper->getCheckDuplicateReviewforOrder($orderId);
        if($status === false ) {
            $ratingentityId = "";
            if (isset($orderId)){
                $orderDetails = $this->orderRepositoryInterface->get($orderId);

                $firstName =  $orderDetails->getBillingAddress()->getFirstName();

                $lastName =  $orderDetails->getBillingAddress()->getLastName();

                if($orderDetails['status'] == \Bakeway\Vendorapi\Model\OrderStatus::STATUS_ORDER_COMPLETE) {

                 $data = [
                       "nickname" => $firstName . " " . $lastName,
                       "title" => 'feedback',
                       "detail" => $text
                   ];


                   $ratings = [];

                   //map option id with star rating
                   foreach ($ratingData as $rating) {

                       $ratings[$rating['rating_id']] = $this->getVoteOption($rating['rating_id'], $rating['rating_value']);

                   }

                   /**
                    * order items details
                    */
                   $items = $orderDetails->getAllItems();
                   foreach ($items as $item) {
                       $itemProductId[] = $item->getProductId();
                       $itemsku[] = $item->getSku();
                       /*
                       if ($item->getParentItem()) {
                           $itemsku[] = $item->getSku();
                           continue;
                       } else {
                           $itemsku[] = $item->getSku();

                       }*/

                   }

                   $productId = current($itemProductId);
                   $product = $this->productRepositoryInterface->getById($productId);

                    /**
                     * seller id
                     */

                    $sellerid = $this->homedeliveryhelper->getSelleridFSku($product->getSku());
                   if (empty($product->getEntityId())) {
                       throw  new InputException(__('Product Not Found'));
                   }

                   $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
                   $product = $objectManager
                       ->get('Magento\Catalog\Model\Product')->load($product->getEntityId());

                   /**
                    * entity id from code
                    */
                   $entity = Review::ENTITY_PRODUCT_CODE;
                   switch ($entity) {
                       case 'seller':
                           $ratingentityId = Reviewhelper::REVIEW_ENTITY_SELLER;
                           break;
                       case 'product':
                           $ratingentityId = Review::ENTITY_PRODUCT_CODE;
                           break;
                       case 'order':
                           $ratingentityId = Reviewhelper::REVIEW_ENTITY_ORDER;
                           break;
                       case 'bakeway':
                           $ratingentityId = Reviewhelper::REVIEW_ENTITY_BKAEWAY;
                           break;

                   }

                   if (($product) && !empty($data)) {
                       $review = $this->_reviewFactory->create()->setData($data);
                       $review->unsetData('review_id');

                       $validate = $review->validate();

                       if ($validate === true) {
                           try {
                               $review->setEntityId($review
                                   ->getEntityIdByCode($ratingentityId))
                                   ->setEntityPkValue($product->getId())
                                   ->setStatusId(Review::STATUS_PENDING)
                                   ->setStoreId($storeId)
                                   ->setStores([$storeId])
                                   ->save();
                               if (count($ratings)) {
                                   foreach ($ratings as $ratingId => $optionId) {
                                       $this->_ratingFactory->create()
                                           ->setRatingId($ratingId)
                                           ->setReviewId($review->getId())
                                           ->addOptionVote($optionId, $product->getId());
                                   }
                               }

                               $review->aggregate();
                               $status = true;
                               $message = 'You submitted your review for moderation.';
                               /**
                                * adding order id for review table.
                                */

                               $reviewCollection = $this->review->getCollection()
                                   ->setOrder('review_id', 'DESC')
                                   ->getFirstItem();
                               $lastreviewId = $reviewCollection['review_id'];

                               if (isset($lastreviewId)) {
                                   $reviewLoad = $this->review->load($lastreviewId);
                                   $reviewLoad->setOrderId($orderId);
                                   $reviewLoad->setOrderReviewStatus(Reviewhelper::SUBMITTED);
                                   $reviewLoad->setSellerId($sellerid);
                                   try {
                                       $reviewLoad->save();

                                   } catch (Exception $e) {
                                       echo $e->getMessage();
                                   }

                               }


                           } catch (\Exception $e) {
                               $message = 'We can\'t post your review right now. ' . $e->getMessage();
                               $status = false;
                           }
                       }
                   }
                   $response[] = [
                       "status" => $status,
                       "message" => $message
                   ];

                   return $response;


               }else{
                 throw  new NotFoundException(__('Before Completing the order you cant send feedback'));
               }
            } else {
                throw  new NotFoundException(__('Order Not Found'));

            }
        }
        else {
            throw  new NotFoundException(__('Feedback is already submitted for this order.'));

        }
    }

    /**
     * @param $ratingId
     * @param $value
     * @return int
     */
    public function getVoteOption($ratingId, $value){
        $optionId = 0;
        $ratingOptionCollection = $this->_ratingOptions->getCollection()
            ->addFieldToFilter('rating_id', $ratingId)
            ->addFieldToFilter('value', $value);
        if(count($ratingOptionCollection)){
            foreach ($ratingOptionCollection as $row) {
                $optionId = $row->getOptionId();
            }
        }
        return $optionId;
    }


}
