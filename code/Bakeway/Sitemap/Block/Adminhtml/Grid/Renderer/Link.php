<?php
/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Sitemap grid link column renderer
 *
 */
namespace Bakeway\Sitemap\Block\Adminhtml\Grid\Renderer;

use Magento\Framework\App\Filesystem\DirectoryList;

class Link extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer
{
    /**
     * @var \Magento\Framework\Filesystem $filesystem
     */
    protected $_filesystem;

    /**
     * @var \Bakeway\Sitemap\Model\SitemapFactory
     */
    protected $_sitemapFactory;

    /**
     * @param \Magento\Backend\Block\Context $context
     * @param \Bakeway\Sitemap\Model\SitemapFactory $sitemapFactory
     * @param \Magento\Framework\Filesystem $filesystem
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Context $context,
        \Bakeway\Sitemap\Model\SitemapFactory $sitemapFactory,
        \Magento\Framework\Filesystem $filesystem,
        array $data = []
    ) {
        $this->_sitemapFactory = $sitemapFactory;
        $this->_filesystem = $filesystem;
        parent::__construct($context, $data);
    }

    /**
     * Prepare link to display in grid
     *
     * @param \Magento\Framework\DataObject $row
     * @return string
     */
    public function render(\Magento\Framework\DataObject $row)
    {
        /** @var $sitemap \Bakeway\Sitemap\Model\Sitemap */
        $sitemap = $this->_sitemapFactory->create();
        $url = $this->escapeHtml($sitemap->getSitemapUrl($row->getFilepath(), $row->getFilename()));

        $fileName = preg_replace('/^\//', '', $row->getFilepath() . $row->getFilename());
        $directory = $this->_filesystem->getDirectoryRead(DirectoryList::ROOT);
        if ($directory->isFile($fileName)) {
            return sprintf('<a href="%1$s">%1$s</a>', $url);
        }

        return $url;
    }
}
