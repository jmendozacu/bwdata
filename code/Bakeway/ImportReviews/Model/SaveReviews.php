<?php
/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 25-04-2018
 * Time: 18:49
 */

namespace Bakeway\ImportReviews\Model;

use Bakeway\ReviewRating\Api\ReviewRepositoryInterface;
use Magento\Framework\App\ObjectManager;
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

class SaveReviews
{
    /**
     * @var Reviewhelper
     */
    protected $reviewhelper;

    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepositoryInterface;

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

    /**
     * @var $orderId
     */
    private $orderId;

    /**
     * @var
     */
    private $ratingFor;

    /**
     * @var
     */
    private $ratingID;

    /**
     * @var
     */
    private $ratingValue;

    /**
     * @var
     */
    private $ratingMessage;

    /**
     * @var
     */
    private $orderDetails;

    /**
     * @var
     */
    private $data;

    /**
     * SaveReviews constructor.
     * @param Reviewhelper $reviewhelper
     * @param OrderRepositoryInterface $orderRepositoryInterface
     * @param AddressRenderer $addressRenderer
     * @param ProductRepositoryInterface $productRepositoryInterface
     * @param StoreManagerInterface $storeManagerInterface
     * @param Homedeliveryhelper $homedeliveryhelper
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $date
     * @param \Magento\Review\Model\RatingFactory $ratingFactory
     * @param \Magento\Review\Model\ReviewFactory $reviewFactory
     * @param \Magento\Review\Model\ResourceModel\Review\CollectionFactory $collectionFactory
     * @param \Magento\Review\Model\Rating\Option $ratingOptions
     * @param Review $review
     */
    public function __construct(
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
     * Function to save views
     */
    public function saveReviews(array $review)
    {
        if (isset($review) && !empty($review)) {
            $storeId = 1;
            foreach ($review as $key => $value) {
                if ($key > 0) {
                    $ratingEntityId = "";
                    $this->setValues($value)->getOrderDetails();


                    if ($this->orderDetails['status'] == \Bakeway\Vendorapi\Model\OrderStatus::STATUS_ORDER_COMPLETE) {
                        $this->getCustomerData();
                        $ratings = [];
                        $ratings[$this->ratingID] = $this->getVoteOption($this->ratingID, $this->ratingValue);

                        $items = $this->orderDetails->getAllItems();
                        foreach ($items as $item) {
                            $itemProductId[] = $item->getProductId();
                            $itemsku[] = $item->getSku();
                        }

                        $productId = current($itemProductId);
                        $product = $this->productRepositoryInterface->getById($productId);
                        $sellerId = $this->homedeliveryhelper->getSelleridFSku($product->getSku());

                        if (empty($product->getEntityId())) {
                            return false;
                        }

                        $objectManager = ObjectManager::getInstance();
                        $product = $objectManager
                            ->get('Magento\Catalog\Model\Product')->load($product->getEntityId());

                        $entity = Review::ENTITY_PRODUCT_CODE;
                        switch ($entity) {
                            case 'seller':
                                $ratingEntityId = Reviewhelper::REVIEW_ENTITY_SELLER;
                                break;
                            case 'product':
                                $ratingEntityId = Review::ENTITY_PRODUCT_CODE;
                                break;
                            case 'order':
                                $ratingEntityId = Reviewhelper::REVIEW_ENTITY_ORDER;
                                break;
                            case 'bakeway':
                                $ratingEntityId = Reviewhelper::REVIEW_ENTITY_BKAEWAY;
                                break;
                        }

                        if (($product) && !empty($this->data)) {
                            $review = $this->_reviewFactory->create()->setData($this->data);
                            $review->unsetData('review_id');
                            $validate = $review->validate();

                            if ($validate === true) {
                                try {
                                    $review->setEntityId($review
                                        ->getEntityIdByCode($ratingEntityId))
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
                                    $reviewCollection = $this->review->getCollection()
                                        ->setOrder('review_id', 'DESC')
                                        ->getFirstItem();
                                    $lastreviewId = $reviewCollection['review_id'];

                                    if (isset($lastreviewId)) {
                                        $reviewLoad = $this->review->load($lastreviewId);
                                        $reviewLoad->setOrderId($this->orderId);
                                        $reviewLoad->setOrderReviewStatus(Reviewhelper::SUBMITTED);
                                        $reviewLoad->setSellerId($sellerId);
                                        try {
                                            $reviewLoad->save();
                                        } catch (Exception $e) {
                                            echo $e->getMessage();
                                        }
                                    }

                                } catch (\Exception $e) {
                                    return false;
                                }
                            } else {
                                return false;
                            }
                        } else {
                            return false;
                        }
                        return true;
                    } else {
                        return false;
                    }
                }
            }
        } else {
            return false;
        }
    }

    /**
     * @param $ratingId
     * @param $value
     * @return int
     */
    public function getVoteOption($ratingId, $value)
    {
        $optionId = 0;
        $ratingOptionCollection = $this->_ratingOptions->getCollection()
            ->addFieldToFilter('rating_id', $ratingId)
            ->addFieldToFilter('value', $value);
        if (count($ratingOptionCollection)) {
            foreach ($ratingOptionCollection as $row) {
                $optionId = $row->getOptionId();
            }
        }
        return $optionId;
    }

    /**
     * Function to set values
     * of CSV data
     * @param $values
     * @return $this
     */
    private function setValues($values)
    {
        $this->orderId = $values['0'];
        $this->ratingFor = $values['1'];
        $this->ratingID = $values['2'];
        $this->ratingValue = $values['3'];
        $this->ratingMessage = $values['4'];

        return $this;
    }

    /**
     * Get order details
     * @return $this
     */
    private function getOrderDetails()
    {
        $this->orderDetails = $this->orderRepositoryInterface->get($this->orderId);
        return $this;
    }

    /**
     * Set data for review to save
     * @return $this
     */
    private function getCustomerData()
    {
        $firstName = $this->orderDetails->getBillingAddress()->getFirstName();
        $lastName = $this->orderDetails->getBillingAddress()->getLastName();

        $this->data = [
            "nickname" => $firstName . " " . $lastName,
            "title" => 'feedback',
            "detail" => $this->ratingMessage
        ];

        return $this;
    }
}