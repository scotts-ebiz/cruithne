<?php

namespace SMG\Api\Model\Api;

use Magento\Framework\Webapi\Rest\Request;

use SMG\Api\Helper\OrdersCreditMemoHelper;
use SMG\Api\Helper\OrdersHelper;
use SMG\Api\Helper\OrdersLawnSubscriptionHelper;
use SMG\Api\Helper\OrdersMainHelper;
use SMG\Api\Helper\RequestHelper;
use SMG\Api\Model\OrdersManagementInterface;

use Psr\Log\LoggerInterface;

class OrdersManagement implements OrdersManagementInterface
{
    /**
     * @var LoggerInterface
     */
    protected $_logger;

    /**
     * @var OrdersHelper
     */
    protected $_ordersHelper;

    /**
     * @var OrdersCreditMemoHelper
     */
    protected $_ordersCreditMemoHelper;

    /**
     * @var OrdersLawnSubscriptionHelper
     */
    protected $_ordersLawnSubscriptionHelper;

    /**
     * @var OrdersMainHelper
     */
    protected $_ordersMainHelper;

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
     * @param LoggerInterface $logger
     * @param OrdersHelper $ordersHelper
     * @param OrdersCreditMemoHelper $ordersCreditMemoHelper
     * @param OrdersLawnSubscriptionHelper $ordersLawnSubscriptionHelper
     * @param OrdersMainHelper $ordersMainHelper
     * @param Request $request
     * @param RequestHelper $requestHelper
     */
    public function __construct(LoggerInterface $logger, OrdersHelper $ordersHelper,
        OrdersCreditMemoHelper $ordersCreditMemoHelper,
        OrdersLawnSubscriptionHelper $ordersLawnSubscriptionHelper,
        OrdersMainHelper $ordersMainHelper,
        Request $request,
        RequestHelper $requestHelper)
    {
        $this->_logger = $logger;
        $this->_ordersHelper = $ordersHelper;
        $this->_ordersCreditMemoHelper = $ordersCreditMemoHelper;
        $this->_ordersLawnSubscriptionHelper = $ordersLawnSubscriptionHelper;
        $this->_ordersMainHelper = $ordersMainHelper;
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
        $this->_logger->debug("I am in OrdersManagement->getOrders()");
        return $this->_ordersHelper->getOrders();
    }

    /**
     * Get the List of Desired Credit Memo Orders
     *
     * @return string
     */
    public function getCreditMemoOrders()
    {
        $this->_logger->debug("I am in OrdersManagement->getCreditMemoOrders()");
        return $this->_ordersCreditMemoHelper->getOrders($this->_requestHelper->getRequest($this->_request->getRequestData()));
    }

    /**
     * Get the List of Desired Lawn Subscription Orders
     *
     * @return string
     */
    public function getLawnSubscriptionOrders()
    {
        $this->_logger->debug("I am in OrdersManagement->getLawnSubscrptionOrders()");
        return $this->_ordersLawnSubscriptionHelper->getOrders($this->_requestHelper->getRequest($this->_request->getRequestData()));
    }

    /**
     * Get the List of Desired Orders
     *
     * @return string
     */
    public function getMainOrders()
    {
        $this->_logger->debug("I am in OrdersManagement->getMainOrders()");
        return $this->_ordersMainHelper->getOrders($this->_requestHelper->getRequest($this->_request->getRequestData()));
    }
}