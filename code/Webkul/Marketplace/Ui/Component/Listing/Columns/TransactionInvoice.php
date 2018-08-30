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

namespace Webkul\Marketplace\Ui\Component\Listing\Columns;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Bakeway\PayoutsCalculation\Helper\Data as PayoutsHelper;

/**
 * Class ViewAction.
 */
class TransactionInvoice extends Column
{
    /**
     * Url path
     */
    const TRANSACTION_URL_PATH_INVOICE = 'marketplace/transaction/invoice';
    /**
     * @var UrlInterface
     */
    protected $_urlBuilder;

    /**
     * @var string
     */
    private $editUrl;

    /**
     * Constructor.
     *
     * @param ContextInterface   $context
     * @param UiComponentFactory $uiComponentFactory
     * @param UrlInterface       $urlBuilder
     * @param array              $components
     * @param array              $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        UrlInterface $urlBuilder,
        array $components = [],
        array $data = []

    ) {
        $this->_urlBuilder = $urlBuilder;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * Prepare Data Source
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                $name = $this->getData('name');

                if (isset($item['transaction_id'])) {
                    if ($item['transaction_status'] == PayoutsHelper::TRANS_STATUS_PAID) {
                        $item[$name]['complete'] = [
                            'href' => $this->_urlBuilder->getUrl(self::TRANSACTION_URL_PATH_INVOICE,
                                ['trans_id' => $item['transaction_id']]),
                            'label' => __('Invoice'),
                            "html" => "<button class='button'><span>Invoice</span></button>"
                        ];
                    }
                }
            }
        }

        return $dataSource;
    }
}
