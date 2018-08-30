<?php
/**
 * Bakeway
 *
 * @category  Bakeway
 * @package   Bakeway_PayoutsCalculation
 * @author    Bakeway
 */

namespace Bakeway\PayoutsCalculation\Block\Adminhtml\Payouts\Export;

use Magento\Framework\Stdlib\DateTime\DateTime as StoreDateTime;

class Index extends \Magento\Backend\Block\Template
{
    /**
     * @var StoreDateTime
     */
    protected $date;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param StoreDateTime $date
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        StoreDateTime $date,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->setUseContainer(true);
        $this->date = $date;
    }

    public function getFile() {
        $fileName = $this->getRequest()->getParam('file');
        if (isset($fileName) && $fileName != '') {
            return $fileName;
        } else {
            return false;
        }

    }

    public function getDate() {
        return $this->date->gmtDate('d-m-Y');
    }
}
