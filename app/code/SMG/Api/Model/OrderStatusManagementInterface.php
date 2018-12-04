<?php

namespace SMG\Api\Model;

interface OrderStatusManagementInterface
{
    /**
     * Update the order status
     *
     * @return string
     */
    public function setOrderStatus();
}