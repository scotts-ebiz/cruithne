<?php

namespace SMG\Api\Model;

interface OrdersManagementInterface
{
    /**
     * This function will get the orders in a JSON format.
     *
     * @return string
     */
    public function getOrders();
}