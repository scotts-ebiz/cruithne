<?php

namespace SMG\Api\Model;

use Magento\Framework\Webapi\Rest\Request;

use SMG\Api\Helper\OrderStatusHelper;
use SMG\Api\Helper\RequestHelper;
use SMG\Api\Api\OrderStatusManagementInterface;

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
     * @var RequestHelper
     */
    protected $_requestHelper;

    /**
     * OrdersManagement constructor.
     *
     * @param OrderStatusHelper $orderStatusHelper
     * @param Request $request
     * @param RequestHelper $requestHelper
     */
    public function __construct(OrderStatusHelper $orderStatusHelper,
        Request $request,
        RequestHelper $requestHelper)
    {
        $this->_orderStatusHelper = $orderStatusHelper;
        $this->_request = $request;
        $this->_requestHelper = $requestHelper;
    }

    /**
     * Update the order status
     *
     * @return string
     */
    public function setOrderStatus()
    {
        return $this->_orderStatusHelper->setOrderStatus($this->_requestHelper->getRequest($this->_request->getRequestData()));
    }
}