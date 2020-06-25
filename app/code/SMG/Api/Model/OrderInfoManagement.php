<?php

namespace SMG\Api\Model;

use Magento\Framework\Webapi\Rest\Request;

use SMG\Api\Helper\OrderInfoHelper;
use SMG\Api\Helper\RequestHelper;
use SMG\Api\Api\OrderInfoManagementInterface;

class OrderInfoManagement implements OrderInfoManagementInterface
{
    /**
     * @var OrderInfoHelper
     */
    protected $_orderInfoHelper;

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
     * @param OrderInfoHelper $orderInfoHelper
     * @param Request $request
     * @param RequestHelper $requestHelper
     */
    public function __construct(OrderInfoHelper $orderInfoHelper,
        Request $request,
        RequestHelper $requestHelper)
    {
        $this->_orderInfoHelper = $orderInfoHelper;
        $this->_request = $request;
        $this->_requestHelper = $requestHelper;
    }

    /**
     * get the order status and tracking number
     *
     * @return string
     */
    public function getOrderInfo()
    {
        return $this->_orderInfoHelper->getOrderInfo($this->_requestHelper->getRequest($this->_request->getRequestData()));
    }
}