<?php

namespace SMG\Api\Helper;

use ZaiusSDK\ZaiusException;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\ResourceModel\Order as OrderResource;
use Psr\Log\LoggerInterface;

class OrderInfoHelper
{
    // Input JSON File Constants
    const INPUT_SAP_ORDER_NUMBER = 'OrderNumber';

    /**
     * @var LoggerInterface
     */
    protected $_logger;

    /**
     * @var ResponseHelper
     */
    protected $_responseHelper;

    /**
     * @var OrderFactory
     */
    protected $_orderFactory;
	
    /**
     * OrderInfoHelper constructor.
     *
     * @param LoggerInterface $logger
     * @param ResponseHelper $responseHelper
     * @param OrderFactory $orderFactory
     * @param OrderResource $orderResource
     */
    public function __construct(
        LoggerInterface $logger,
        ResponseHelper $responseHelper,
        OrderFactory $orderFactory,
        OrderResource $orderResource)
    {
        $this->_logger = $logger;
        $this->_responseHelper = $responseHelper;
        $this->_orderFactory = $orderFactory;
        $this->_orderResource = $orderResource;
    }

    /**
     * Handles the order status and shipping request
     *
     * @param $requestData
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    public function getOrderInfo($requestData)
    {

        // make sure that we were given something from the request
        if (!empty($requestData))
        {
            $response = true;
            // loop through the orders that were sent via the JSON file
            foreach ($requestData as $inputOrder)
            {
                try
                {
                    // check to see if there is an order increment number
                    $orderIncrementId = $inputOrder[self::INPUT_SAP_ORDER_NUMBER];
                    if ($orderIncrementId)
                    {
                       $orderArray = [];
                        // create and load the Order
                       $order = $this->_orderFactory->create()->loadByIncrementId($orderIncrementId);
					   $orderArray['OrderNumber'] = $orderIncrementId;
					   $orderArray['OrderStatus'] = $order->getStatus();

					   // get shipment tracking array
					    $orderArray[] = $this->getShipmentTracking($order);

                        if (!empty($orderArray))
                        {
                          $ordersArray[] = $orderArray;
                        }

                    }
                    else
                    {
                        // log the error
                        $this->_logger->error("SMG\Api\Helper\OrderInfoHelper - Missing magento order number.");
                    }
                }
                catch (\Exception $e)
                {
                    $errorMsg = "An error has occurred for order status Info - " . $e->getMessage();
                    $this->_logger->error($errorMsg);
                }
            }

        }
        else
        {
            $response = false;
            // log the error
            $this->_logger->error("SMG\Api\Helper\OrderInfoHelper - Nothing was provided to process.");
        }

        if($response)
        {
            $orderInfoResponse = $this->_responseHelper->createResponse(true, $ordersArray);
        }
        else
        {
            $orderInfoResponse = $this->_responseHelper->createResponse(false, 'Nothing was provided to process.');
        }
        // return
        return $orderInfoResponse;
    }
	
	/**
     *
     * @param $order
     * @return array
     */
    private function getShipmentTracking($order)
    {
            
        $tracksCollection = $order->getTracksCollection();

        if($tracksCollection){
            
            $shipmentTracking = [];
            
            foreach ($tracksCollection->getItems() as $track) {

            $shipmentTracking['ShiptrackingNumber'][] = $track->getTrackNumber();

            }
        }
        else
        {
          $shipmentTracking = NULL; 
        }

        // return
        return $shipmentTracking;
    }
}