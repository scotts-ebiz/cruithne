<?php

namespace SMG\Api\Model\Api;

use \SMG\Api\Model\OrdersManagementInterface;

class OrdersManagement implements OrdersManagementInterface
{
    protected $_ordersHelper;

    public function __construct(\SMG\Api\Helper\OrdersHelper $ordersHelper)
    {
        $this->_ordersHelper = $ordersHelper;
    }

    public function getOrders()
    {
        // TODO: Implement getOrders() method.

        return $this->_ordersHelper->getOrders('2018-11-07 18:05:00', '2018-11-07 19:06:00', 1);
    }
}