<?php

namespace SMG\Api\Model;

interface OrderInfoManagementInterface
{
    /**
     * Get the order status and tracking number
     *
     * @return string
     */
    public function getOrderInfo();
}