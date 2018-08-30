<?php
/**
 * Bakeway
 *
 * @category  Bakeway
 * @package   Bakeway_CustomFee
 * @author    Bakeway
 */
namespace Bakeway\ReviewRating\Observer;

use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Order as Orderobject;
use Magento\Sales\Model\ResourceModel\Order;
use Bakeway\ReviewRating\Helper\Data as Reviewratinghelper;
use Symfony\Component\Config\Definition\Exception\Exception;

class SalesOrderPlaceAfter implements ObserverInterface
{

    /**
     * Orderobject
     * @var Orderobject;
     */
    protected $orderobject;
    /**
     * @var Reviewratinghelper
     */
    protected $reviewhelper;

    /**
     * SalesOrderPlaceAfter constructor.
     * @param Orderobject $orderobject
     * @param Reviewratinghelper $reviewhelper
     */
    public function  __construct(
        Orderobject $orderobject,
        Reviewratinghelper $reviewhelper
    )
    {
        $this->orderobject = $orderobject;
        $this->reviewhelper = $reviewhelper;
    }

    /**
     * Return mixed
     * EventObserver $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $order =  $observer->getEvent()->getOrder();
        $token = "";
        $token = $this->reviewhelper->generateToken();
        if(!empty($token))
        {
            try{
                $order->setOrderReviewToken($token);

            }catch (Exception $e)
            {
                echo $e->getMessage();
            }
        }
    }
}
