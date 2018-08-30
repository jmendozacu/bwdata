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

namespace Webkul\Marketplace\Model\Config\Source;

/**
 * Used in creating options for getting product type value.
 */
class Bakerytype implements \Magento\Framework\Option\ArrayInterface
{

    /**
     * Options getter.
     *
     * @return array
     */
    public function toOptionArray()
    {

        $data = [
            ['value' => '1', 'label' => __('Franchise')],
            ['value' => '2', 'label' => __('Boutique Shop')],
            ['value' => '3', 'label' => __('Home Baker')],
        ];

        return $data;
    }
}

