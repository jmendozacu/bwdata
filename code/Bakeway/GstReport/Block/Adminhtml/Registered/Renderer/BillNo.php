<?php
/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 03-05-2018
 * Time: 13:53
 */

namespace Bakeway\GstReport\Block\Adminhtml\Registered\Renderer;

use Magento\Framework\DataObject;

class BillNo extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer
{
    /**
     * @param DataObject $row
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function render(DataObject $row)
    {
        $transaction_invoice_number = $row->getTransactionInvoiceNumber();
        if (!empty($transaction_invoice_number)) {
            if (date('m') < 3) {
                $financialYear = (date('y') - 1) . '-' . date('y');
            } else {
                $financialYear = date('y') . '-' . (date('y') + 1);
            }
            $transaction_invoice_number = 'RLFR/' . $financialYear . '/' . $transaction_invoice_number;
        } else {
            $transaction_invoice_number = 'N/A';
        }

        return $transaction_invoice_number;
    }

}