<?php

namespace SMG\Api\Api;

use Magento\Framework\Webapi\Rest\Request;

use SMG\Api\Helper\OrdersSentHelper;
use SMG\Api\Helper\RequestHelper;
use SMG\Api\Api\Interfaces\OrdersSentManagementInterface;

class OrdersSentManagement implements OrdersSentManagementInterface
{
    /**
     * @var OrdersSentHelper
     */
    protected $_ordersSentHelper;

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
     * @param OrdersSentHelper $ordersSentHelper
     * @param Request $request
     * @param RequestHelper $requestHelper
     */
    public function __construct(OrdersSentHelper $ordersSentHelper,
        Request $request,
        RequestHelper $requestHelper)
    {
        $this->_ordersSentHelper = $ordersSentHelper;
        $this->_request = $request;
        $this->_requestHelper = $requestHelper;
    }

    /**
     * Update the orders to notify that the order was
     * sent to SAP successfully
     *
     * @return string
     */
    public function updateOrders()
    {
        return $this->_ordersSentHelper->updateOrders($this->_requestHelper->getRequest($this->_request->getRequestData()));
    }
}