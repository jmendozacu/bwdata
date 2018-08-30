<?php
/**
 * Bakeway
 *
 * @category  Bakeway
 * @package   Bakeway_Sitemap
 * @author    Bakeway
 */

namespace Bakeway\Sitemap\Helper;

/**
 * Bakeway CatalogSync Helper Data.
 */
class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @param \Magento\Framework\App\Helper\Context $context
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context
    ) {
        parent::__construct($context);
    }
}