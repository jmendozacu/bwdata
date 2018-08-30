<?php

namespace Bakeway\Reports\Block\Adminhtml\Orders\Renderer;

use Magento\Framework\DataObject;
use Magento\Sales\Api\OrderRepositoryInterface as OrderRepositoryInterface;
use Magento\Sales\Model\Order\Status\History as OrderStatushistory;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface As TimezoneInterface;

class OrderAccRejectTime extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer
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
        $enityId = $row->getEntityId();
        $orderdDate = $row->getData('created_at');
        $orderdDate=  $this->timezoneInterface->date($orderdDate)->format('m/d/y H:i:s');
        //$orderdDate = date("m/d/y H:i:s",strtotime("+30 minutes",strtotime($orderdDate)));
        $collection = $this->orderStatushistory->getCollection()
            ->addFieldToSelect(['status','created_at','comment'])
            ->addFieldToFilter(
                'status',
                ['in' => [\Bakeway\Vendorapi\Model\OrderStatus::STATUS_PARTNER_ACCEPTED,
                    \Bakeway\Vendorapi\Model\OrderStatus::STATUS_PARTNER_REJECTED]
                ]
            )
            ->addFieldToFilter('entity_name',['eq'=>'order'])
            ->addFieldToFilter('parent_id',['eq'=>$enityId])
            ->getFirstItem();

        $fromDate =  $this->timezoneInterface->date($collection['created_at'])->format('m/d/y H:i:s');

        $fromDate = new \DateTime($fromDate);
        $orderdDate = new \DateTime($orderdDate);

        $interval = $fromDate->diff($orderdDate);

        if($interval->d == 0){
            $timeDiff = $interval->format('%h hours %I minutes ');
        }
        else{
            $timeDiff = $interval->format('%d days %h hours %I minutes ');
        }



        if(isset($timeDiff)){
            return $timeDiff;
        }
    }
}