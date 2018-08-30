<?php
/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Bakeway\ReviewRating\Model\Rewrite;

use Magento\Catalog\Model\Product;
use Magento\Framework\DataObject\IdentityInterface;
use Magento\Review\Model\ResourceModel\Review\Product\Collection as ProductCollection;
use Magento\Review\Model\ResourceModel\Review\Status\Collection as StatusCollection;

/**
 * Review model
 *
 * @method string getCreatedAt()
 * @method \Magento\Review\Model\Review setCreatedAt(string $value)
 * @method \Magento\Review\Model\Review setEntityId(int $value)
 * @method int getEntityPkValue()
 * @method \Magento\Review\Model\Review setEntityPkValue(int $value)
 * @method int getStatusId()
 * @method \Magento\Review\Model\Review setStatusId(int $value)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Review extends  \Magento\Review\Model\Review
{
    /**
     * Event prefix for observer
     *
     * @var string
     */
    protected $_eventPrefix = 'review';

    /**
     * Cache tag
     */
    const CACHE_TAG = 'review_block';

    /**
     * Product entity review code
     */
    const ENTITY_PRODUCT_CODE = 'product';

    /**
     * Customer entity review code
     */
    const ENTITY_CUSTOMER_CODE = 'customer';

    /**
     * Category entity review code
     */
    const ENTITY_CATEGORY_CODE = 'category';

    /**
     * Approved review status code
     */
    const STATUS_APPROVED = 1;

    /**
     * Pending review status code
     */
    const STATUS_PENDING = 2;

    /**
     * Not Approved review status code
     */
    const STATUS_NOT_APPROVED = 3;

    /**
     * Review product collection factory
     *
     * @var \Magento\Review\Model\ResourceModel\Review\Product\CollectionFactory
     */
    protected $productCollectionFactory;

    /**
     * Review status collection factory
     *
     * @var \Magento\Review\Model\ResourceModel\Review\Status\CollectionFactory
     */
    protected $_statusFactory;

    /**
     * Review model summary factory
     *
     * @var \Magento\Review\Model\Review\SummaryFactory
     */
    protected $_summaryFactory;

    /**
     * Review model summary factory
     *
     * @var \Magento\Review\Model\Review\SummaryFactory
     */
    protected $_summaryModFactory;

    /**
     * Review model summary
     *
     * @var \Magento\Review\Model\Review\Summary
     */
    protected $_reviewSummary;

    /**
     * Core model store manager interface
     *
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * Url interface
     *
     * @var \Magento\Framework\UrlInterface
     */
    protected $_urlModel;

    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Review\Model\ResourceModel\Review\Product\CollectionFactory $productFactory
     * @param \Magento\Review\Model\ResourceModel\Review\Status\CollectionFactory $statusFactory
     * @param \Magento\Review\Model\ResourceModel\Review\Summary\CollectionFactory $summaryFactory
     * @param \Magento\Review\Model\Review\SummaryFactory $summaryModFactory
     * @param \Magento\Review\Model\Review\Summary $reviewSummary
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\UrlInterface $urlModel
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb $resourceCollection
     * @param array $data
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Review\Model\ResourceModel\Review\Product\CollectionFactory $productFactory,
        \Magento\Review\Model\ResourceModel\Review\Status\CollectionFactory $statusFactory,
        \Magento\Review\Model\ResourceModel\Review\Summary\CollectionFactory $summaryFactory,
        \Magento\Review\Model\Review\SummaryFactory $summaryModFactory,
        \Magento\Review\Model\Review\Summary $reviewSummary,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\UrlInterface $urlModel,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct($context, $registry ,$productFactory,$statusFactory, $summaryFactory,$summaryModFactory,
            $reviewSummary, $storeManager,$urlModel,$resource, $resourceCollection, $data);
    }


    /**
     * Validate review summary fields
     *
     * @return bool|string[]
     */
    public function validate()
    {
        $errors = [];

        if (!\Zend_Validate::is($this->getTitle(), 'NotEmpty')) {
            $errors[] = __('Please enter a review summary.');
        }

        if (!\Zend_Validate::is($this->getNickname(), 'NotEmpty')) {
            $errors[] = __('Please enter a nickname.');
        }


        if (empty($errors)) {
            return true;
        }
        return $errors;
    }


}
