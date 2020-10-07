<?php

namespace SMG\OrderService\Plugin\Api\Data;

use \SMG\OrderService\Model\Service\Order;
use \Magento\Sales\Api\OrderManagementInterface;

class OrderInterface
{

    private $order;

    public function __construct(
        Order $order
    ) {
        $this->order = $order;
    }

    public function afterPlace(OrderManagementInterface $orderManagementInterface, $order)
    {
        $storeId = $order->getStoreId();
        if ($storeId != 1) {
            $this->order->postOrderService($order);
        }
        return $order;
    }

}
