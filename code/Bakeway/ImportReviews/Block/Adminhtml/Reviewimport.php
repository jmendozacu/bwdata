<?php
/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 24-04-2018
 * Time: 15:56
 */

namespace Bakeway\ImportReviews\Block\Adminhtml;


class Reviewimport extends \Magento\Backend\Block\Template
{
    /**
     * Reviewimport constructor.
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\UrlInterface $urlBuilder
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        array $data = []
    )
    {
        parent::__construct($context, $data);
    }

    /**
     * @return string
     */
    public function getFormAction()
    {
        return $this->_urlBuilder->getUrl('importreviews/index/review', $paramsHere = array());
    }

}