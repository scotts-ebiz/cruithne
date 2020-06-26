<?php

namespace SMG\Api\Api\Interfaces;

interface OrderStatusManagementInterface
{
    /**
     * Update the order status
     *
     * @return string
     */
    public function setOrderStatus();
}