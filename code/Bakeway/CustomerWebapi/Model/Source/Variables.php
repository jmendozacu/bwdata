<?php

namespace Bakeway\CustomerWebapi\Model\Source;

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Variables
 *
 * @author Admin
 */
class Variables
        extends \Magento\Email\Model\Source\Variables
{
    public function __construct()
    {
        parent::__construct();
        $this->_configVariables[] = ['value' => 'react_site_settings/react_settings_general/frontend_url', 'label' => __('Frontend URL')];
    }
}
