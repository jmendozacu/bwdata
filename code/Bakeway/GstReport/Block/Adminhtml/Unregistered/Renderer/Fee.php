<?php
/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 03-05-2018
 * Time: 15:29
 */

namespace Bakeway\GstReport\Block\Adminhtml\Unregistered\Renderer;

use Magento\Framework\DataObject;

class Fee extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer
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
     * Fee constructor.
     * @param \Webkul\Marketplace\Model\ResourceModel\Saleslist\CollectionFactory $sellerFactory
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     */
    public function __construct(
        \Webkul\Marketplace\Model\ResourceModel\Saleslist\CollectionFactory $sellerFactory,
        \Magento\Sales\Api\Data\OrderInterface $order)
    {
        $this->order = $order;
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
        $totalFee = 0;
        foreach ($sellerLists as $list) {
            $order = $this->order->loadByIncrementId($list['magerealorder_id']);
            $totalFee += $order->getFee();
        }
        $row->setTotalFee($totalFee);

        return $totalFee;
    }
}