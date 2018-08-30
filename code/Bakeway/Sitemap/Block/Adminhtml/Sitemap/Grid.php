<?php
/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Bakeway\Sitemap\Block\Adminhtml\Sitemap;

/**
 * Adminhtml catalog (google) sitemaps block
 */
class Grid extends \Magento\Backend\Block\Widget\Grid\Container
{
    /**
     * Block constructor
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_controller = 'adminhtml_sitemap';
        $this->_blockGroup = 'Bakeway_Sitemap';
        parent::_construct();
        $this->removeButton('add');
    }
}
