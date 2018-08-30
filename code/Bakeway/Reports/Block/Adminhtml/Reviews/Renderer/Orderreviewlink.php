<?php

namespace Bakeway\Reports\Block\Adminhtml\Reviews\Renderer;

use Magento\Framework\DataObject;
use Magento\Sales\Api\OrderRepositoryInterface as OrderRepositoryInterface;
use Bakeway\OrderstatusEmail\Model\Email as OrderStatusModel;

class Orderreviewlink extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer
{

    /**
     * @var \Magento\Catalog\Model\CategoryFactory
     */
    protected $orderRepositoryInterface;

    /**
     * @var OrderStatusModel
     */
    protected $orderStatusModel;

    /**
     * Orderreviewlink constructor.
     * @param OrderRepositoryInterface $orderRepositoryInterface
     * @param OrderStatusModel $orderStatusModel
     */
    public function __construct(
        OrderRepositoryInterface $orderRepositoryInterface,
        OrderStatusModel $orderStatusModel
    ) {
        $this->orderRepositoryInterface = $orderRepositoryInterface;
        $this->orderStatusModel = $orderStatusModel;
    }

    /**
     * get category name
     * @param  DataObject $row
     * @return string
     */
    public function render(DataObject $row)
    {
        $orderReviewToken = $row->getOrderReviewToken();
        $ratingReviewUrl = $this->orderStatusModel->getReactFeedbackUrl();
        $ratingReviewUrl = $ratingReviewUrl."?token=".$orderReviewToken;
        return $ratingReviewUrl;
    }
}