<?php

namespace SMG\Api\Api;

use Magento\Framework\Webapi\Rest\Request;
use SMG\Api\Api\Interfaces\CoreServicesInterface;
use SMG\Api\Helper\OrdersCreditMemoHelper;
use SMG\Api\Helper\OrdersHelper;
use SMG\Api\Helper\OrdersLawnSubscriptionHelper;
use SMG\Api\Helper\CoreServicesHelper;
use SMG\Api\Helper\OrdersMainHelper;
use SMG\Api\Helper\RequestHelper;
use SMG\Api\Api\Interfaces\OrdersManagementInterface;

class CoreServices implements CoreServicesInterface
{

    /**
     * @var CoreServicesHelper
     */
    protected $_coreServicesHelper;

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
     * @param OrdersHelper $ordersHelper
     * @param OrdersCreditMemoHelper $ordersCreditMemoHelper
     * @param OrdersLawnSubscriptionHelper $ordersLawnSubscriptionHelper
     * @param OrdersMainHelper $ordersMainHelper
     * @param Request $request
     * @param RequestHelper $requestHelper
     */
    public function __construct(
        CoreServicesHelper $coreServicesHelper,
        OrdersHelper $ordersHelper,
        OrdersCreditMemoHelper $ordersCreditMemoHelper,
        OrdersLawnSubscriptionHelper $ordersLawnSubscriptionHelper,
        OrdersMainHelper $ordersMainHelper,
        Request $request,
        RequestHelper $requestHelper)
    {
        $this->_coreServicesHelper = $coreServicesHelper;
        $this->_ordersHelper = $ordersHelper;
        $this->_ordersCreditMemoHelper = $ordersCreditMemoHelper;
        $this->_ordersLawnSubscriptionHelper = $ordersLawnSubscriptionHelper;
        $this->_ordersMainHelper = $ordersMainHelper;
        $this->_request = $request;
        $this->_requestHelper = $requestHelper;
    }

    /**
     * Create an order.
     *
     * @return string
     */
    public function createOrder()
    {
        return $this->_coreServicesHelper->createOrder($this->_requestHelper->getRequest($this->_request->getRequestData()));
    }

    /**
     * Get an order.
     *
     * @return string
     */
    public function getOrder()
    {
        return $this->_coreServicesHelper->getOrder($this->_request->getRequestData());
    }

    /**
     * Update the order subscription status
     *
     * @return string
     */
    public function updateOrderSubscriptionStatus()
    {
        return $this->_coreServicesHelper->updateOrderSubscriptionStatus($this->_request->getRequestData());
    }

    /**
     * Gets an array of products by their sku(s).
     *
     * @return string
     */
    public function getProducts()
    {
        return $this->_coreServicesHelper->getProducts($this->_request->getRequestData());
    }

    /**
     * Creates a shipment given order/shipment details.
     *
     * @return string
     */
    public function createShipment()
    {
        return $this->_coreServicesHelper->createShipment($this->_request->getRequestData());
    }

    /**
     * Update the order billing address
     *
     * @return string
     */
    public function UpdateBillingAddress()
    {
        return $this->_coreServicesHelper->updateBillingAddress($this->_request->getRequestData());
    }
	
	 /**
     * Update the orders customer email
     *
     * @return string
     */
    public function updateEmailAddress()
    {
        return $this->_coreServicesHelper->updateEmailAddress($this->_request->getRequestData());
    }
}
