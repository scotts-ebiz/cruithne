<?php

namespace SMG\Api\Model\Api;

use Magento\Framework\Webapi\Rest\Request;

use SMG\Api\Helper\OrdersHelper;
use SMG\Api\Model\OrdersManagementInterface;

class OrdersManagement implements OrdersManagementInterface
{
    /**
     * @var OrdersHelper
     */
    protected $_ordersHelper;

    /**
     * OrdersManagement constructor.
     *
     * @param OrdersHelper $ordersHelper
     */
    public function __construct(OrdersHelper $ordersHelper)
    {
        $this->_ordersHelper = $ordersHelper;
    }

    /**
     * Get the List of Desired Orders
     *
     * @return string
     */
    public function getOrders()
    {
        return $this->_ordersHelper->getOrders();
    }
}