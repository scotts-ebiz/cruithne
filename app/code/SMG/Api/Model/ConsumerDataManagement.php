<?php

namespace SMG\Api\Model;

use Magento\Framework\Webapi\Rest\Request;

use SMG\Api\Helper\ConsumerDataHelper;
use SMG\Api\Api\ConsumerDataManagementInterface;

class ConsumerDataManagement implements ConsumerDataManagementInterface
{
    /**
     * @var ConsumerDataHelper
     */
    protected $_consumerDataHelper;

    /**
     * @var Request
     */
    protected $_request;

    /**
     * ConsumerDataManagement constructor.
     *
     * @param ConsumerDataHelper $consumerDataHelper
     * @param Request $request
     */
    public function __construct(ConsumerDataHelper $consumerDataHelper, Request $request)
    {
        $this->_consumerDataHelper = $consumerDataHelper;
        $this->_request = $request;
    }

    /**
     * This function will get consumer data
     * to be used to upload to the consumer database
     *
     * @return string
     */
    public function getConsumerData()
    {
        return $this->_consumerDataHelper->getConsumerData();
    }
}