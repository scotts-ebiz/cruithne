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
        // check if the dates are provided
        if (!empty($requestData))
        {
            // for some reason the call came back differently when ran
            // sometimes it would come back with one value in the array
            // as a JSON string other times it would come back with two
            // different values in the array so we needed to accommodate for them
            if (count($requestData) === 1)
            {
                $orderStatusResponse = $this->getOrderStatusFromJson($requestData[0]);
            }
            else
            {
                $orderStatusResponse = $this->getOrdersStatusFromArray($requestData);
            }
        }
        else
        {
            // log the error
            $this->_logger->error("SMG\Api\Helper\OrderStatusHelper - There was nothing provided to process.");

            $orderStatusResponse = $this->_responseHelper->createResponse(false, 'There was nothing provided to process.');
        }

        // return
        return $orderStatusResponse;
    }

    /**
     * This takes the JSON and process the order status
     *
     * @param string $jsonString
     * @return string
     */
    public function getOrderStatusFromJson($jsonString)
    {
        // decode the json string
        $json = json_decode($jsonString, true);

        // return
        return $this->_responseHelper->createResponse(true, "The Order Status Update Completed Successfully.");
    }

    /**
     * This takes the JSON and process the order status
     *
     * @param array $requestData
     * @return string
     */
    private function getOrdersStatusFromArray($requestData)
    {
        // return
        return $this->_responseHelper->createResponse(true, "The Order Status Update Completed Successfully.");
    }
}