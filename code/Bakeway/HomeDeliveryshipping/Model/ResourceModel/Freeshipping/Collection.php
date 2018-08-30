<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Bakeway\HomeDeliveryshipping\Model\ResourceModel\Freeshipping;

/**
 * Commisons Collection
 *
 * @author Bakeway
 */
class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * Initialize resource collection
     *
     * @return void
     */
    public function _construct()
    {
        $this->_init('Bakeway\HomeDeliveryshipping\Model\Freeshipping', 'Bakeway\HomeDeliveryshipping\Model\ResourceModel\Freeshipping');
    }
}
