<?php
/**
 * Copyright © 2015 Bakeway. All rights reserved.
 */
namespace Bakeway\HomeDeliveryshipping\Model\ResourceModel;

/**
 * Commison resource
 */
class Freeshipping extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Initialize resource
     *
     * @return void
     */
    public function _construct()
    {
        $this->_init('bakeway_freeshipping_log', 'id');
    }


}
