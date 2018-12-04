<?php

namespace SMG\Api\Model\Api;

use Magento\Framework\Webapi\Rest\Request;

use SMG\Api\Helper\OrderStatusHelper;
use SMG\Api\Model\OrderStatusManagementInterface;

class OrderStatusManagement implements OrderStatusManagementInterface
{
    /**
     * @var OrderStatusHelper
     */
    protected $_orderStatusHelper;

    /**
     * @var Request
     */
    protected $_request;

    /**
     * OrdersManagement constructor.
     *
     * @param OrderStatusHelper $orderStatusHelper
     * @param Request $request
     */
    public function __construct(OrderStatusHelper $orderStatusHelper, Request $request)
    {
        $this->_orderStatusHelper = $orderStatusHelper;
        $this->_request = $request;
    }

    /**
     * Update the order status
     *
     * @return string
     */
    public function setOrderStatus()
    {
        return $this->_orderStatusHelper->setOrderStatus($this->_request->getRequestData());
    }
}