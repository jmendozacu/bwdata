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


use Magento\Eav\Model\ResourceModel\Entity\Attribute\OptionFactory;

/**
 * Used in creating options for getting product type value.
 */
class Options extends \Magento\Eav\Model\Entity\Attribute\Source\AbstractSource
{



    /**
     * Get all options
     *
     * @return array
     */
    public function getAllOptions()
    {
        $this->_options = [
            ['label'=>'', 'value'=>''],
            ['label'=>'Hours', 'value'=>'1'],
            ['label'=>'Minutes', 'value'=>'2'],
        ];
        return $this->_options;
    }

    /**
     * Get a text for option value
     *
     * @param string|integer $value
     * @return string|bool
     */
    public function getOptionText($value)
    {
        foreach ($this->getAllOptions() as $option) {
            if ($option['value'] == $value) {
                return $option['label'];
            }
        }
        return false;
    }
}

