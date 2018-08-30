<?php
/**
 * Created by PhpStorm.
 * User: kushagra
 * Date: 15/2/18
 * Time: 11:31 AM
 */

namespace Bakeway\ReviewRating\Helper;

use Magento\Framework\ObjectManagerInterface as ObjectManager;
use Magento\Review\Model\Rating\Entity as Ratingentity;
use Magento\Review\Model\Review as ReviewCollection;
use Magento\Sales\Model\Order as OrderCollection;
use Magento\Sales\Api\OrderRepositoryInterface as OrderRepositoryInterface;
use Magento\Review\Model\Rating as Rating;
use Bakeway\HomeDeliveryshipping\Helper\Data as Homedeliveryhelper;
use Magento\Review\Model\ResourceModel\Rating\Option\Collection as Votecollection;

class Data extends \Magento\Framework\App\Helper\AbstractHelper {


    /**
     * #@+
     * Lengths of token fields
     */
    CONST LENGTH_TOKEN = 48;

    CONST ORDER_ENTITY_CODE = 'order';

    CONST SELLER_ENTITY_CODE = 'seller';

    CONST PRODUCT_ENTITY_CODE = 'product';

    CONST BAKEWAY_ENTITY_CODE = 'bakeway';

    /**
     * order review status
     */

    CONST SUBMITTED = '1';

    CONST PENDING = 0;

    CONST SUBMITTED_STATUS_STRING = 'SUBMITTED';
    CONST PENDING_STATUS_STRING = 'PENDING';

    /**
     * constant for review_entity table
     */
    CONST REVIEW_ENTITY_SELLER = 'seller';

    CONST REVIEW_ENTITY_ORDER = 'order';

    CONST REVIEW_ENTITY_BKAEWAY = 'bakeway';

    /**
     * Object Manager interface
     * @var ObjectManager
     */
    protected $objectManager;

    /**
     * @var \Magento\Framework\Math\Random
     */
    protected $_mathRandom;

    /**
     * @var Ratingentity
     */
    protected $ratingentity;

    /**
     * ReviewCollection
     */
     protected $reviewcollection;

    /**
     * OrderCollection
     */
    protected $orderCollection;

    /**
     * @var OrderRepositoryInterface
     */
    protected  $orderRepositoryInterface;

    /**
     * @var Rating
     */
    protected $rating;
    /**
     * @var Homedeliveryhelper
     */
    protected $homedeliveryhelper;
    /**
     * @var Votecollection
     */
    protected $votecollection;
    /**
     * Data constructor.
     * @param \Magento\Framework\App\Helper\Context $context
     * @param ObjectManager $objectManager
     * @param \Magento\Framework\Math\Random $mathRandom
     * @param Ratingentity $ratingentity
     * @param ReviewCollection $reviewcollection
     * @param OrderCollection $orderCollection
     * @param OrderRepositoryInterface $orderRepositoryInterface
     * @param Rating $rating
     * @param Homedeliveryhelper $homedeliveryhelper
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        ObjectManager $objectManager,
        \Magento\Framework\Math\Random $mathRandom,
        Ratingentity $ratingentity,
        ReviewCollection $reviewcollection,
        OrderCollection $orderCollection,
        OrderRepositoryInterface $orderRepositoryInterface,
        Rating $rating,
        Homedeliveryhelper $homedeliveryhelper,
        Votecollection $votecollection
    )
    {
        $this->objectManager = $objectManager;
        $this->_mathRandom = $mathRandom;
        $this->ratingentity = $ratingentity;
        $this->reviewcollection = $reviewcollection;
        $this->orderCollection = $orderCollection;
        $this->orderRepositoryInterface = $orderRepositoryInterface;
        $this->rating = $rating;
        $this->homedeliveryhelper = $homedeliveryhelper;
        $this->votecollection = $votecollection;
    }



    /**
     * Generate random string for token or secret or verifier
     *
     * @param int $length String length
     * @return string
     */
    public function generateRandomString($length)
    {
        return $this->_mathRandom->getRandomString(
            $length,
            \Magento\Framework\Math\Random::CHARS_DIGITS . \Magento\Framework\Math\Random::CHARS_LOWERS
        );
    }

    /**
     * Generate random string for token
     *
     * @return string
     */
    public function generateToken()
    {
        return $this->generateRandomString(self::LENGTH_TOKEN);
    }

    /**
     * @return string
     */
    public function getRatingType()
    {

        $ratingEntityObj = $this->ratingentity;
        return [
            ['label' => __('---Select Type---'), 'value' => ''],
            ['label' => __('Seller'), 'value' =>  $getRatingIndex = $ratingEntityObj->getIdByCode(self::SELLER_ENTITY_CODE)],
            ['label' => __('Product'), 'value' => $getRatingIndex = $ratingEntityObj->getIdByCode(self::PRODUCT_ENTITY_CODE)],
            ['label' => __('Order'), 'value' =>  $getRatingIndex = $ratingEntityObj->getIdByCode(self::ORDER_ENTITY_CODE)],
            ['label' => __('Bakeway'), 'value' =>  $getRatingIndex = $ratingEntityObj->getIdByCode(self::BAKEWAY_ENTITY_CODE)],

        ];

    }


    /**
     * @return string
     */
    public function getRatingQuestionTypes()
    {

        return [
            ['label' => __('---Select Type---'), 'value' => ''],
            ['label' => __('Single select'), 'value' => 'single_select' ],
            ['label' => __('Multi select'), 'value' => 'multi_select'],
            ['label' => __('Text'), 'value' =>  'text' ],
            ['label' => __('rating'), 'value' =>'rating'],
            ['label' => __('Yes/No'), 'value' =>'yes_no'],
        ];

    }

    /**
     * @return mixed
     */
    public function getRatingCode($code)
    {
           $ratingEntityObj = $this->ratingentity;
     return $ratingEntityObj->getIdByCode($code);

    }

    /**
     * @param $orderId
     */
    public function getOrderReviewStatus($orderId)
    {

        $reviewCollection = $this->reviewcollection->getCollection()
                           ->addFieldToFilter('order_id',$orderId)
                           ->getFirstItem();

        if(!empty($reviewCollection->getData('order_review_status')))
        {
            return $reviewCollection['order_review_status'];
        } else {
            return;
        }


    }

    /**
     * @return entity_id
     * @param $token
     */
    public function getOrderIdfromToken($token)
    {
         if(isset($token))
         {
             $orderDetail = $this->orderCollection->getCollection()
                   ->addFieldToFilter('order_review_token',array('eq'=>$token))
                   ->getFirstItem();
             return $orderDetail['entity_id'];

         }else {
            return;
          }

    }

    /**
     * Return the seller Data.
     *
     * @return \Webkul\Marketplace\Model\ResourceModel\Seller\Collection
     */
    public function getSellerData($sellerId) {
        $model = $this->objectManager->create('Webkul\Marketplace\Model\Seller')->getCollection()
            ->addFieldToFilter('seller_id',$sellerId)->getFirstItem();
        return $model->getData('business_name');

    }

    /**
     * @return rating collection
     */
    public function getRatingQuestions()
    {
        $ratingQuestions = [];
        $collection =  $this->objectManager->create('Magento\Review\Model\Rating')->getCollection()
                      ->setOrder("position",'ASC');
        if(count($collection) > 0){

            return $collection->getData();
        } else {
          return;
        }
    }

    /**
     * @param $id
     */
    public function getEntityName($id)
    {
        $resource =  $this->objectManager->get('Magento\Framework\App\ResourceConnection');
        $connection = $resource->getConnection();
        $tableName = $resource->getTableName('rating_entity'); //gives table name with prefix
        $sql = "select entity_code FROM " . $tableName." where entity_id=".$id;

        $result = $connection->fetchOne($sql); // gives associated array, table fields as key in array.

        if(isset($result)){
            return $result;
        }else{
            return ;
        }

    }

    /**
     * @param $id
     */
    public function getRatingSummeryForOrder($id)
    {
        $resource =  $this->objectManager->get('Magento\Framework\App\ResourceConnection');
        $connection = $resource->getConnection();
        $tableName = $resource->getTableName('rating_option_vote');
        $sql = "select rating_id,review_id,percent FROM " . $tableName." where review_id=".$id;

        $result = $connection->fetchAll($sql); //gives all rating array percent value

        if(isset($result)){
            return $result;
        }else{
            return ;
        }

    }

    /**
     * @param $id
     */
    public function getRatingSummeryForOrderForApi($id,$reviewId)
    {
        $resource =  $this->objectManager->get('Magento\Framework\App\ResourceConnection');
        $connection = $resource->getConnection();
        $tableName = $resource->getTableName('rating_option_vote');
        $sql = "select percent FROM " . $tableName." where rating_id=".$id." AND review_id=".$reviewId."";

        $result = $connection->fetchOne($sql); //gives all rating array percent value

        if(isset($result)){
            return $result;
        }else{
            return ;
        }

    }

    /**
     * @param $ratingId
     */
    public function getratingTitle($ratingId)
    {
        $resource =  $this->objectManager->get('Magento\Framework\App\ResourceConnection');
        $connection = $resource->getConnection();
        $tableName = $resource->getTableName('rating');
        $sql = "select entity_id FROM " . $tableName." where rating_id=".$ratingId;

        $result = $connection->fetchOne($sql); //gives all rating array percent value

        if(isset($result)){
            return $result;
        }else{
            return ;
        }
    }

    /**
     * @param $ratingId
     */
    public function getratingQuestion($ratingId)
    {
        $resource =  $this->objectManager->get('Magento\Framework\App\ResourceConnection');
        $connection = $resource->getConnection();
        $tableName = $resource->getTableName('rating');
        $sql = "select rating_code FROM " . $tableName." where rating_id=".$ratingId;

        $result = $connection->fetchOne($sql); //gives all rating array percent value

        if(isset($result)){
            return $result;
        }else{
            return ;
        }
    }

    /**
     * @param $orderId
     */
    public function getCheckDuplicateReviewforOrder($orderId)
    {

        $reviewCollection = $this->reviewcollection->getCollection()
            ->addFieldToFilter('order_id',$orderId);

        if(count($reviewCollection) > 0)
        {
            return true;
        } else {
            return false;
        }


    }

    /**
     * @param $id
     * @return bool|void
     */
    public function getSingleSelectRatingType($id)
    {
        $ratingQuestions = [];
        $collection =  $this->objectManager->create('Magento\Review\Model\Rating')->getCollection()
            ->addFieldToFilter("rating_id",$id)
            ->addFieldToFilter("q_type",'yes_no');
        if(count($collection) > 0){

            return true;
        } else {
            return;
        }
    }

    /**
     * @param $Sku
     * @return \Magento\Framework\Cache\ConfigInterface
     */
    public function getSellerProductRating($id,$sellerId)
    {
      $reviewCollection = $this->reviewcollection->getCollection()
            ->addFieldToFilter('entity_pk_value',$id)
            ->addFieldToSelect('review_id')
            ->addFieldToFilter('seller_id',$sellerId);
        $percent = $totRat = [];


        if(count($reviewCollection) > 0){
            foreach($reviewCollection as $item){
                // $totRat = [];
                $productEntity = $this->getRatingCode(self::PRODUCT_ENTITY_CODE);
                $ratingCollection = $this->rating->getCollection()
                    ->addFieldToSelect('rating_id')
                    ->addFieldToFilter('entity_id',$productEntity);
                $reviewId = $item['review_id'];

                $totRat[] =  count($ratingCollection);

                if(!empty($ratingCollection)){
                    foreach($ratingCollection as $item){

                        $percent[] =  $this->getRatingSummeryForOrderForApi($item['rating_id'],$reviewId);
                    }
                }

            }
        }
        $divendVal = count($totRat) * 100; //selller rating total percent
        $ratCountval = array_sum($percent);
        if(!empty($ratCountval)){
            $finalRating = $ratCountval / $divendVal;
            $finalRating = $finalRating * 100 / 20;
            return round($finalRating,1);

        }else{
            return;
        }

    }


    public function getSellerRating($sellerId)
    {
        $reviewCollection = $this->reviewcollection->getCollection()
            ->addFieldToSelect('review_id')
            ->addFieldToFilter('seller_id',$sellerId);
        $percent = $totRat = [];
        if(count($reviewCollection) > 0){
            foreach($reviewCollection as $item){
               // $totRat = [];
                $sellerEntity = $this->getRatingCode(self::SELLER_ENTITY_CODE);
                $ratingCollection = $this->rating->getCollection()
                                    ->addFieldToSelect('rating_id')
                                    ->addFieldToFilter('entity_id',$sellerEntity);
                $reviewId = $item['review_id'];

                $totRat[] =  count($ratingCollection);

                 if(!empty($ratingCollection)){
                     foreach($ratingCollection as $item){

                        $percent[] =  $this->getRatingSummeryForOrderForApi($item['rating_id'],$reviewId);
                     }
                 }

            }
        }
        $divendVal = count($totRat) * 200; //selller rating total percent
        $ratCountval = array_sum($percent);
        if(!empty($ratCountval)){
            $finalRating = $ratCountval / $divendVal;
            $finalRating = $finalRating * 100 / 20;
            return round($finalRating,1);
        }else{
            return;
        }
    }

}