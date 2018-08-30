<?php
/**
 * Copyright © 2015 Bakeway. All rights reserved.
 */
namespace Bakeway\OrderUpdate\Model\ResourceModel;

/**
 * Rangeprice resource
 */
class Orderupdate extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Initialize resource
     *
     * @return void
     */
    public function _construct()
    {
        $this->_init('bakeway_order_update', 'entity_id');
    }


}
