<?php

/**
 * Created by PhpStorm.
 * User: kushagra
 * Date: 19/4/18
 * Time: 4:43 PM
 */
namespace Bakeway\VendorNotification\Model\Config\Source;

class Channelslist implements \Magento\Framework\Option\ArrayInterface
{


    public $arr = array (
        1 => "Notification",
        2 => "SMS",
        3 => "Email",
        4 => "NA"
    );

    public function toOptionArray()
    {
        $ret = [];
        foreach ($this->arr as $key => $value) {
            $ret[] = [
                'value' => $key,
                'label' => $value
            ];
        }

        return $ret;
    }

}