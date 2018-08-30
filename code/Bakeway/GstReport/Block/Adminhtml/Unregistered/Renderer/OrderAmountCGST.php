<?php
/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 04-05-2018
 * Time: 12:31
 */

namespace Bakeway\GstReport\Block\Adminhtml\Unregistered\Renderer;

use Magento\Framework\DataObject;

class OrderAmountCGST extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer
{
    /**
     * @var \Webkul\Marketplace\Model\ResourceModel\Saleslist\CollectionFactory
     */
    protected $sellerFactory;

    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\Tax\CollectionFactory
     */
    protected $taxFactory;

    /**
     * OrderAmount constructor.
     * @param \Webkul\Marketplace\Model\ResourceModel\Saleslist\CollectionFactory $sellerFactory
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
     */
    public function __construct(
        \Webkul\Marketplace\Model\ResourceModel\Saleslist\CollectionFactory $sellerFactory,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Sales\Model\ResourceModel\Order\Tax\CollectionFactory $taxFactory)
    {
        $this->taxFactory = $taxFactory;
        $this->orderRepository = $orderRepository;
        $this->sellerFactory = $sellerFactory;
    }

    /**
     * @param DataObject $row
     * @return int|string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function render(DataObject $row)
    {
        $totalCGST = $row->getTotalAmount() * 0.09;
        $row->setOrderAmountCSGT($totalCGST);

        return $totalCGST;
    }
}