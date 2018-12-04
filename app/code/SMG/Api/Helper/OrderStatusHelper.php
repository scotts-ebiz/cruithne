<?php

namespace SMG\Api\Helper;

use \Magento\Framework\App\ResourceConnection;
use \Psr\Log\LoggerInterface;

class OrderStatusHelper
{
    // Variables
    protected $_logger;
    protected $_resourceConnection;
    protected $_responseHelper;

    /**
     * OrdersHelper constructor.
     *
     * @param LoggerInterface $logger
     * @param ResourceConnection $resourceConnection
     * @param ResponseHelper $responseHelper
     */
    public function __construct(LoggerInterface $logger, ResourceConnection $resourceConnection, ResponseHelper $responseHelper)
    {
        $this->_logger = $logger;
        $this->_resourceConnection = $resourceConnection;
        $this->_responseHelper = $responseHelper;
    }

    /**
     * @param $requestData
     * @return string
     */
    public function setOrderStatus($requestData)
    {
        $response = '{}';

        $this->_logger->debug("I am here");
        // check if the dates are provided
        if (!empty($requestData))
        {
            foreach ($requestData as $key => $value)
            {
                $this->_logger->debug($key . ': ' . $value);
            }

//            if (isset($ordersDate))
//            {
//
//                $response = $this->_responseHelper->createResponse(true, 'Success');
//            }
//            else
//            {
//                // log the error
//                $this->_logger->error("SMG\Api\Helper\OrderStatusHelper - Nothing was provided to process.");
//
//                $response = $this->_responseHelper->createResponse(false, 'Nothing was provided to process.');
//            }
        }
        else
        {
            // log the error
            $this->_logger->error("SMG\Api\Helper\OrderStatusHelper - Nothing was provided to process.");

            $response = $this->_responseHelper->createResponse(false, 'Nothing was provided to process.');
        }

        // return
        return $response;
    }
}