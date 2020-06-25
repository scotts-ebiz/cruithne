<?php

namespace SMG\Api\Api;

interface OrderStatusManagementInterface
{
    /**
     * Update the order status
     *
     * @return string
     */
    public function setOrderStatus();
}