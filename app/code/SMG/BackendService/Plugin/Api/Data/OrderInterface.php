<?php

namespace SMG\BackendService\Plugin\Api\Data;

use \SMG\BackendService\Model\Service\Order;
use \Magento\Sales\Api\OrderManagementInterface;

class OrderInterface
{

    /**
     * @var Order $order
     */
    private $order;

    /**
     * OrderInterface constructor.
     * @param Order $order
     */
    public function __construct(
        Order $order
    ) {
        $this->order = $order;
    }

    /**
     * @param OrderManagementInterface $orderManagementInterface
     * @param $order
     * @return mixed
     */
    public function afterPlace(OrderManagementInterface $orderManagementInterface, $order)
    {
        $storeId = $order->getStoreId();
        if ($storeId != 1) {
            $this->order->postOrderService($order);
        }
        return $order;
    }

}
