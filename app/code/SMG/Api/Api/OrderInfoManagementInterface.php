<?php

namespace SMG\Api\Api;

interface OrderInfoManagementInterface
{
    /**
     * Get the order status and tracking number
     *
     * @return string
     */
    public function getOrderInfo();
}