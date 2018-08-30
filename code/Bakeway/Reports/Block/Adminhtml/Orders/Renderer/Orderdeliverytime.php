<?php

namespace Bakeway\Reports\Block\Adminhtml\Orders\Renderer;

use Magento\Framework\DataObject;
use Magento\Sales\Api\OrderRepositoryInterface as OrderRepositoryInterface;

class Orderdeliverytime extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer
{

    /**
     * @var \Magento\Catalog\Model\CategoryFactory
     */
    protected $orderRepositoryInterface;
    /**
     * @param \Magento\Catalog\Model\CategoryFactory $categoryFactory
     */
    public function __construct(
        OrderRepositoryInterface $orderRepositoryInterface
    ) {
        $this->orderRepositoryInterface = $orderRepositoryInterface;
    }

    /**
     * get category name
     * @param  DataObject $row
     * @return string
     */
    public function render(DataObject $row)
    {
        $deliveryTime = $row->getDeliveryTime();
        $deliveryTime = date('M d, Y h:i A', strtotime($deliveryTime));
        return $deliveryTime;
    }
}