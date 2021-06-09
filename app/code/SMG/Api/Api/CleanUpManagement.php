<?php

namespace SMG\Api\Api;

use Magento\Framework\Webapi\Rest\Request;
use SMG\Api\Api\Interfaces\CleanUpManagementInterface;

use SMG\Api\Helper\CleanUpManagementHelper;
use SMG\Api\Helper\OrdersCreditMemoHelper;
use SMG\Api\Helper\OrdersHelper;
use SMG\Api\Helper\OrdersLawnSubscriptionHelper;

use SMG\Api\Helper\OrdersMainHelper;
use SMG\Api\Helper\RequestHelper;


class CleanUpManagement implements CleanUpManagementInterface
{

    /**
     * @var CleanUpManagementHelper
     */
    protected $_cleanUpManagementHelper;


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
     * @param CleanUpManagementHelper $cleanUpManagementHelper
     * @param Request $request
     * @param RequestHelper $requestHelper
     */
    public function __construct(
        CleanUpManagementHelper $cleanUpManagementHelper,
        Request $request,
        RequestHelper $requestHelper)
    {
        $this->_cleanUpManagementHelper = $cleanUpManagementHelper;
        $this->_request = $request;
        $this->_requestHelper = $requestHelper;
    }

    /**
     * Create an order.
     *
     * @return string
     */
    public function cleanupDuplicateSubscriptions()
    {
        return $this->_cleanUpManagementHelper->cleanupDuplicateSubscriptions($this->_request->getRequestData());
    }

}
