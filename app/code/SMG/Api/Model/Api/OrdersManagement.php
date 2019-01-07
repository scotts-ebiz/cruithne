<?php

namespace SMG\Api\Model\Api;

use Magento\Framework\Webapi\Rest\Request;

use SMG\Api\Helper\OrdersHelper;
use SMG\Api\Helper\RequestHelper;
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
     * @var RequestHelper
     */
    protected $_requestHelper;

    /**
     * OrdersManagement constructor.
     *
     * @param OrdersHelper $ordersHelper
     * @param Request $request
     * @param RequestHelper $requestHelper
     */
    public function __construct(OrdersHelper $ordersHelper, Request $request, RequestHelper $requestHelper)
    {
        $this->_ordersHelper = $ordersHelper;
        $this->_request = $request;
        $this->_requestHelper = $requestHelper;
    }

    /**
     * Get the List of Desired Orders
     *
     * @return string
     */
    public function getOrders()
    {
        return $this->_ordersHelper->getOrders($this->_requestHelper->getRequest($this->_request->getRequestData()));
    }
}