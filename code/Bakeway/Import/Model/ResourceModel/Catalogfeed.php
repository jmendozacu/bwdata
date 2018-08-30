<?php
/**
 * Copyright © 2015 Bakeway. All rights reserved.
 */
namespace Bakeway\Import\Model\ResourceModel;

/**
 * Rangeprice resource
 */
class Catalogfeed extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Initialize resource
     *
     * @return void
     */
    public function _construct()
    {
        $this->_init('bakeway_catalog_feed', 'entity_id');
    }


}
