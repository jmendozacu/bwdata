<?php

/**
 * Webkul Software.
 *
 * @category  Webkul
 * @package   Webkul_Marketplace
 * @author    Webkul
 * @copyright Copyright (c) 2010-2017 Webkul Software Private Limited (https://webkul.com)
 * @license   https://store.webkul.com/license.html
 */

namespace Webkul\Marketplace\Model\Seller\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Class Status
 */
class StatusPay implements OptionSourceInterface {

    /**
     * @var \Bakeway\PayoutsCalculation\Helper\Data
     */
    protected $payoutsCalculationhelper;

    public function __construct(
    \Bakeway\PayoutsCalculation\Helper\Data $payoutsCalculationhelper
    ) {
        $this->_payoutsCalculationhelper = $payoutsCalculationhelper;
    }

    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray() {
        $availableOptions = [""];
        $statusUp = $this->_payoutsCalculationhelper::TRANS_STATUS_UNPAID;
        $statusP = $this->_payoutsCalculationhelper::TRANS_STATUS_PAID;
        $statusPro = $this->_payoutsCalculationhelper::TRANS_STATUS_PROCESSING;
        $statusFailed = $this->_payoutsCalculationhelper::TRANS_STATUS_FAILED;
        $options = [];
        $availableOptions = [$statusUp => "unpaid", $statusP => "paid", $statusPro => "processing", $statusFailed => "failed"];
        foreach ($availableOptions as $key => $value) {
            $options[] = [
                'label' => $value,
                'value' => $key,
            ];
        }
        return $options;
    }

}
