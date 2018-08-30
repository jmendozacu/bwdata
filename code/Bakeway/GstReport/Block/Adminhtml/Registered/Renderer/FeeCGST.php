<?php
/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 03-05-2018
 * Time: 15:29
 */

namespace Bakeway\GstReport\Block\Adminhtml\Registered\Renderer;

use Magento\Framework\DataObject;

class FeeCGST extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer
{
    /**
     * @param DataObject $row
     * @return float|int|string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function render(DataObject $row)
    {
        $totalFeeCgst = $row->getTotalFee() * 0.09;
        $row->setFeeCgst($totalFeeCgst);

        return $totalFeeCgst;
    }
}