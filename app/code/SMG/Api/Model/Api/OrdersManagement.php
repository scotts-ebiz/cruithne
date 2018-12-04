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
     * @var Request
     */
    protected $_request;

    /**
     * OrdersManagement constructor.
     *
     * @param OrdersHelper $ordersHelper
     * @param Request $request
     */
    public function __construct(OrdersHelper $ordersHelper, Request $request)
    {
        $this->_ordersHelper = $ordersHelper;
        $this->_request = $request;
    }

    /**
     * Get the List of Desired Orders
     *
     * @return string
     */
    public function getOrders()
    {
        return $this->_ordersHelper->getOrders($this->_request->getRequestData());
    }
}