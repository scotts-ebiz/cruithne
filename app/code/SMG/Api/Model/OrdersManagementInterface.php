<?php

namespace SMG\Api\Model;

interface OrdersManagementInterface
{
    /**
     * This function will get the orders in the following type
     * format.
     *
     * XML
     * JSON
     * CSV
     *
     * @return mixed
     */
    public function getOrders();
}