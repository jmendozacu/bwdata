<?php

namespace Bakeway\SalesorderGrid\Model;


class OrderHistory extends \Magento\Framework\Model\AbstractModel
{
    const TYPE_IN_RABBITMQ = 'in_queue';
    const TYPE_ERROR_ADDING_TO_RABBITMQ = 'error';
    const TYPE_IN_RICS = 'in_rics';
    const VALIDATION_ERROR_IN_BRIDGE = 'error';
    const BRIDGE_IS_UNAVAILABLE = 'error';
    const ERROR_IN_RICS = 'error';
    const ERROR = 'error';
    const TYPE_IN_BRIDGE = 'in_bridge';
    const INVALID_VALIDATION_IN_RICS = 'invalid_validation_in_rics';
    const SUCCESS_SET_IN_RICS = 'success_set_in_rics';
    const ERROR_ADDING_TO_BRIDGE = 'error';
    const NOT_IN_QUEUE = 'not_in_queue';

    public function getGridColumn($id)
    {
        return $id; 
    }


}