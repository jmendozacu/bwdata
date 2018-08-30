<?php

namespace Bakeway\Reports\Block\Adminhtml\Orders\Renderer;

use Magento\Framework\DataObject;
use Magento\Sales\Api\OrderRepositoryInterface as OrderRepositoryInterface;
use Magento\Sales\Model\Order\Status\History as OrderStatushistory;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface As TimezoneInterface;

class RejectorderStatus extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer
{

    /**
     * @var \Magento\Catalog\Model\CategoryFactory
     */
    protected $orderRepositoryInterface;

    /**
     * @var OrderStatushistory
     */
    protected $orderStatushistory;

    /**
     * @var TimezoneInterface
     */
    protected $timezoneInterface;

    /**
     * OrderAccRejectTime constructor.
     * @param OrderRepositoryInterface $orderRepositoryInterface
     * @param OrderStatushistory $orderStatushistory
     */
    public function __construct(
        OrderRepositoryInterface $orderRepositoryInterface,
        OrderStatushistory $orderStatushistory,
        TimezoneInterface $timezoneInterface
    ) {
        $this->orderRepositoryInterface = $orderRepositoryInterface;
        $this->orderStatushistory = $orderStatushistory;
        $this->timezoneInterface = $timezoneInterface;
    }

    /**
     * get category name
     * @param  DataObject $row
     * @return string
     */
    public function render(DataObject $row)
    {
        $entityId = $row->getEntityId();
        $collection = $this->orderStatushistory->getCollection()
            ->addFieldToSelect(['status','created_at','comment'])
            ->addFieldToFilter(
                'status',
                ['in' => [
                    \Bakeway\Vendorapi\Model\OrderStatus::STATUS_PARTNER_REJECTED]
                ]
            )
            ->addFieldToFilter('entity_name',['eq'=>'order'])
            ->addFieldToFilter('parent_id',['eq'=>$entityId])
            ->getFirstItem();
        if(!empty($collection->getData('comment'))){
            return  $collection->getData('comment');
        }
        return;
    }
}