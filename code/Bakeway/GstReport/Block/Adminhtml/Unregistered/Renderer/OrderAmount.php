<?php
/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 04-05-2018
 * Time: 12:31
 */

namespace Bakeway\GstReport\Block\Adminhtml\Unregistered\Renderer;

use Magento\Framework\DataObject;

class OrderAmount extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer
{
    /**
     * @var \Webkul\Marketplace\Model\ResourceModel\Saleslist\CollectionFactory
     */
    protected $sellerFactory;

    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    protected $order;

    /**
     * OrderAmount constructor.
     * @param \Webkul\Marketplace\Model\ResourceModel\Saleslist\CollectionFactory $sellerFactory
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
     */
    public function __construct(
        \Webkul\Marketplace\Model\ResourceModel\Saleslist\CollectionFactory $sellerFactory,
        \Magento\Sales\Api\Data\OrderInterface $order)
    {
        $this->orderRepository = $order;
        $this->sellerFactory = $sellerFactory;
    }

    /**
     * @param DataObject $row
     * @return int|string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function render(DataObject $row)
    {
        $transId = $row->getEntityId();
        $sellerFactory = $this->sellerFactory->create();

        $sellerFactory = $sellerFactory->addFieldToFilter('trans_id', $transId);
        $sellerLists = $sellerFactory->getData();
        $totalAmount = 0;
        foreach ($sellerLists as $list) {
            $order = $this->orderRepository->loadByIncrementId($list['magerealorder_id']);
            $totalAmount += $order->getGrandTotal();
        }

        $row->setTotalAmount($totalAmount);

        return $totalAmount;
    }
}