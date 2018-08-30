<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Bakeway\Import\Model\ResourceModel\Catalogfeed;


class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * Initialize resource collection
     *
     * @return void
     */
    public function _construct()
    {
        $this->_init('Bakeway\Import\Model\Catalogfeed', 'Bakeway\Import\Model\ResourceModel\Catalogfeed');
    }
}
