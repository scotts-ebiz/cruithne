<?php

namespace SMG\Api\Helper;

use Magento\Framework\App\ResourceConnection;
use Psr\Log\LoggerInterface;
use SMG\Sap\Model\SapOrderBatchFactory;
use SMG\Sap\Model\ResourceModel\SapOrderBatch as SapOrderBatchResource;

class OrdersSentHelper
{
    /**
     * @var LoggerInterface
     */
    protected $_logger;

    /**
     * @var ResourceConnection
     */
    protected $_resourceConnection;

    /**
     * @var ResponseHelper
     */
    protected $_responseHelper;

    /**
     * @var SapOrderBatchFactory\
     */
    protected $_sapOrderBatchFactory;

    /**
     * @var SapOrderBatchResource
     */
    protected $_sapOrderBatchResource;

    /**
     * OrdersHelper constructor.
     *
     * @param LoggerInterface $logger
     * @param ResourceConnection $resourceConnection
     * @param ResponseHelper $responseHelper
     * @param SapOrderBatchFactory $sapOrderBatchFactory
     * @param SapOrderBatchResource $sapOrderBatchResource
     */
    public function __construct(LoggerInterface $logger,
        ResourceConnection $resourceConnection,
        ResponseHelper $responseHelper,
        SapOrderBatchFactory $sapOrderBatchFactory,
        SapOrderBatchResource $sapOrderBatchResource)
    {
        $this->_logger = $logger;
        $this->_resourceConnection = $resourceConnection;
        $this->_responseHelper = $responseHelper;
        $this->_sapOrderBatchFactory = $sapOrderBatchFactory;
        $this->_sapOrderBatchResource = $sapOrderBatchResource;
    }

    /**
     * Update the orders to notify that the order was
     * sent to SAP successfully
     *
     * @param $requestData
     *
     * @return string
     */
    public function updateOrders($requestData)
    {
        // variables
        $ordersSentResponse = $this->_responseHelper->createResponse(true, "The order sent process completed successfully.");

        // make sure that we were given something from the request
        if (!empty($requestData))
        {
            // get the date for today

            // loop through the orders that were sent via the JSON file
            foreach ($requestData as $inputOrder)
            {
                // get the values from the input JSON
                $orderId = $inputOrder['orderId'];
                $orderType = $inputOrder['orderType'];
                $sku = $inputOrder['sku'];

                // if both items have a value then update the flag
                if (!empty($orderId) && !empty($orderType))
                {
                    // determine the order type that is being processed
                    if ($orderType === 'DR' || $orderType === 'RE')
                    {
                        // process debit orders
                        $this->debitOrders($orderId, $today);
                    }
                    else if ($orderType === 'CR')
                    {
                        // process the credit orders
                        $this->creditOrders($orderId, $sku, $today);
                    }
                    else
                    {
                        // log the error
                        $this->_logger->error("SMG\Api\Helper\OrdersSentHelper - The order id - " . $orderId . " - has an invalid order type of CR, DR, or RE - " . $orderType);
                    }
                }
            }
        }
        else
        {
            // log the error
            $this->_logger->error("SMG\Api\Helper\OrdersSentHelper - Nothing was provided to process.");

            $ordersSentResponse = $this->_responseHelper->createResponse(false, 'Nothing was provided to process.');
        }

        // return
        return $ordersSentResponse;
    }

    /**
     * Update the order process date
     *
     * @param $orderId
     * @param $today
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    private function debitOrders($orderId, $today)
    {
        // load the sap order batch
        $sapOrderBatch = $this->_sapOrderBatchFactory->create();
        $this->_sapOrderBatchResource->load($sapOrderBatch, $orderId, 'order_id');

        // set the process date for this order
        $sapOrderBatch->setData('order_process_date', $today);

        // save
        $this->_sapOrderBatchResource->save($sapOrderBatch);
    }

    private function creditOrders($orderId, $sku, $today)
    {
        
    }
}