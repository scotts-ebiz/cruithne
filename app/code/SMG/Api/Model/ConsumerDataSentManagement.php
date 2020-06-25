<?php

namespace SMG\Api\Model;

use Magento\Framework\Webapi\Rest\Request;

use SMG\Api\Helper\ConsumerDataSentHelper;
use SMG\Api\Helper\RequestHelper;
use SMG\Api\Api\ConsumerDataSentManagementInterface;

class ConsumerDataSentManagement implements ConsumerDataSentManagementInterface
{
    /**
     * @var ConsumerDataSentHelper
     */
    protected $_consumerDataSentHelper;

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
     * @param ConsumerDataSentHelper $consumerDataSentHelper
     * @param Request $request
     * @param RequestHelper $requestHelper
     */
    public function __construct(ConsumerDataSentHelper $consumerDataSentHelper,
        Request $request,
        RequestHelper $requestHelper)
    {
        $this->_consumerDataSentHelper = $consumerDataSentHelper;
        $this->_request = $request;
        $this->_requestHelper = $requestHelper;
    }

    /**
     * Update the orders to notify that the consumer Data was
     * sent successfully
     *
     * @return string
     */
    public function updateOrders()
    {
        return $this->_consumerDataSentHelper->updateOrders($this->_requestHelper->getRequest($this->_request->getRequestData()));
    }
}