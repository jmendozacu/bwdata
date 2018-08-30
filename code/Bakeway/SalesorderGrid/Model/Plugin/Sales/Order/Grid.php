<?php
namespace Bakeway\SalesorderGrid\Model\Plugin\Sales\Order;

class Grid
{

    public static $table = 'sales_order_grid';
    public static $leftJoinTable = 'marketplace_orders';


    public function afterSearch($intercepter, $collection)
    {
        if ($collection->getMainTable() === $collection->getConnection()->getTableName(self::$table)) {
            $collection->getSelect();
        } 
        return $collection;


    }

    public function getConfig($path)
    {
        return $this->_scopeConfig->getValue($path);
    }

    public function __construct(\Magento\Framework\View\Element\Context $context,
                                array $data = []
    )
    {
        $this->_scopeConfig = $context->getScopeConfig();
    }


}