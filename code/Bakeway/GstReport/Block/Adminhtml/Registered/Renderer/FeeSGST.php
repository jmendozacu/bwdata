<?php
/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 03-05-2018
 * Time: 15:29
 */

namespace Bakeway\GstReport\Block\Adminhtml\Registered\Renderer;

use Magento\Framework\DataObject;

class FeeSGST extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer
{
    /**
     * @param DataObject $row
     * @return float|int|string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function render(DataObject $row)
    {
        $totalFeeSgst = $row->getTotalFee() * 0.09;
        $row->setFeeSgst($totalFeeSgst);

        return $totalFeeSgst;
    }
}