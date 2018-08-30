<?php
/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Bakeway\Sitemap\Block\Adminhtml\Grid\Renderer;

/**
 * Sitemap grid action column renderer
 */
class Action extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\Action
{
    /**
     * @param \Magento\Framework\DataObject $row
     * @return string
     */
    public function render(\Magento\Framework\DataObject $row)
    {
        if ($row->getData('filepath') == "/") {
            $filename = $row->getData('filename');
        } else {
            $filename = $row->getData('filepath')."/".$row->getData('filename');
        }
        $filename = str_replace("/", "__-", $filename);
        $this->getColumn()->setActions(
            [
                [
                    'url' => $this->getUrl('*/*/download', ['filename' => rawurlencode($filename)]),
                    'caption' => __('Download'),
                ],
            ]
        );
        return parent::render($row);
    }
}
