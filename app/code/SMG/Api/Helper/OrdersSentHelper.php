<?php

namespace SMG\Api\Helper;

use Magento\Framework\App\ResourceConnection;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\ResourceModel\Order as OrderResource;
use Psr\Log\LoggerInterface;
use SMG\Sap\Model\SapOrderBatchFactory;
use SMG\Sap\Model\ResourceModel\SapOrderBatch as SapOrderBatchResource;
use SMG\Sap\Model\ResourceModel\SapOrderBatchCreditmemo as SapOrderBatchCreditmemoResource;
use SMG\Sap\Model\ResourceModel\SapOrderBatchRma as SapOrderBatchRmaResource;
use SMG\Sap\Model\ResourceModel\SapOrderBatchCreditmemo\CollectionFactory as SapOrderBatchCreditmemoCollectionFactory;
use SMG\Sap\Model\ResourceModel\SapOrderBatchRma\CollectionFactory as SapOrderBatchRmaCollectionFactory;

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
     * @var SapOrderBatchFactory
     */
    protected $_sapOrderBatchFactory;


    /**
     * @var SapOrderBatchRmaCollectionFactory
     */
    protected  $_sapOrderBatchRmaCollectionFactory;

    /**
     * @var SapOrderBatchResource
     */
    protected $_sapOrderBatchResource;

    /**
     * @var OrderFactory
     */
    protected $_orderFactory;

    /**
     * @var OrderResource
     */
    protected $_orderResource;

    /**
     * @var SapOrderBatchCreditmemoCollectionFactory
     */
    protected $_sapOrderBatchCreditmemoCollectionFactory;

    /**
     * @var SapOrderBatchCreditmemoResource
     */
    protected $_sapOrderBatchCreditmemoResource;

    /**
     * @var SapOrderBatchRmaResource
     */
    protected $_sapOrderBatchRmaResource;

    /**
     * OrdersHelper constructor.
     *
     * @param LoggerInterface $logger
     * @param ResourceConnection $resourceConnection
     * @param ResponseHelper $responseHelper
     * @param SapOrderBatchFactory $sapOrderBatchFactory
     * @param SapOrderBatchResource $sapOrderBatchResource
     * @param OrderFactory $orderFactory
     * @param OrderResource $orderResource
     * @param SapOrderBatchCreditmemoCollectionFactory $sapOrderBatchCreditmemoCollectionFactory
     * @param SapOrderBatchCreditmemoResource $sapOrderBatchCreditmemoResource
     */
    public function __construct(LoggerInterface $logger,
        ResourceConnection $resourceConnection,
        ResponseHelper $responseHelper,
        SapOrderBatchFactory $sapOrderBatchFactory,
        SapOrderBatchResource $sapOrderBatchResource,
        OrderFactory $orderFactory,
        OrderResource $orderResource,
        SapOrderBatchRmaCollectionFactory $sapOrderBatchRmaCollectionFactory,
        SapOrderBatchRmaResource $sapOrderBatchRmaResource,
        SapOrderBatchCreditmemoCollectionFactory $sapOrderBatchCreditmemoCollectionFactory,
        SapOrderBatchCreditmemoResource $sapOrderBatchCreditmemoResource)
    {
        $this->_logger = $logger;
        $this->_resourceConnection = $resourceConnection;
        $this->_responseHelper = $responseHelper;
        $this->_sapOrderBatchFactory = $sapOrderBatchFactory;
        $this->_sapOrderBatchResource = $sapOrderBatchResource;
        $this->_orderFactory = $orderFactory;
        $this->_orderResource = $orderResource;
        $this->_sapOrderBatchCreditmemoCollectionFactory = $sapOrderBatchCreditmemoCollectionFactory;
        $this->_sapOrderBatchCreditmemoResource = $sapOrderBatchCreditmemoResource;
        $this->_sapOrderBatchRmaCollectionFactory = $sapOrderBatchRmaCollectionFactory;
        $this->_sapOrderBatchRmaResource = $sapOrderBatchRmaResource;
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
            $today = date('Y-m-d H:i:s');

            // loop through the orders that were sent via the JSON file
            foreach ($requestData as $inputOrder)
            {
                // get the values from the input JSON
                $orderIncrementId = $inputOrder['OrderNumber'];
                $orderType = $inputOrder['CrDrReFlag'];

                // if both items have a value then update the flag
                if (!empty($orderIncrementId) && !empty($orderType))
                {
                    // get the order from the increment id
                    $order = $this->_orderFactory->create();
                    $this->_orderResource->load($order, $orderIncrementId, 'increment_id');

                    // determine the order type that is being processed
                    if ($orderType === 'DR')
                    {
                        // process debit orders
                        $this->debitOrders($order, $today);
                    }
                    else if ($orderType === 'RE') {
                        // get the sku value from the input JSON
                        $sku = $inputOrder['WebSku'];

                        // make sure that there was a sku provided
                        if (!empty($sku))
                        {
                            // process the credit orders
                            $this->rmaOrders($order, $sku, $today);
                        }
                        else
                        {
                            // log the error
                            $this->_logger->error("SMG\Api\Helper\OrdersSentHelper - The order number " . $orderIncrementId . " is missing the sku for the order type of " . $orderType);
                        }

                    }
                    else if ($orderType === 'CR')
                    {
                        // get the sku value from the input JSON
                        $sku = $inputOrder['WebSku'];

                        // make sure that there was a sku provided
                        if (!empty($sku))
                        {
                            // process the credit orders
                            $this->creditOrders($order, $sku, $today);
                        }
                        else
                        {
                            // log the error
                            $this->_logger->error("SMG\Api\Helper\OrdersSentHelper - The order number " . $orderIncrementId . " is missing the sku for the order type of " . $orderType);
                        }
                    }
                    else
                    {
                        // log the error
                        $this->_logger->error("SMG\Api\Helper\OrdersSentHelper - The order number " . $orderIncrementId . " has an invalid order type " . $orderType);
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
     * @param \Magento\Sales\Model\Order $order
     * @param $today
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    private function debitOrders($order, $today)
    {
        // load the sap order batch
        $sapOrderBatch = $this->_sapOrderBatchFactory->create();
        $this->_sapOrderBatchResource->load($sapOrderBatch, $order->getId(), 'order_id');

        // since the order response will be sending a record for each item that is in
        // the orders file but only need one then check to see if the date was already set
        if (empty($sapOrderBatch->getData('order_process_date')))
        {
            // set the process date for this order
            $sapOrderBatch->setData('order_process_date', $today);

            // save
            $this->_sapOrderBatchResource->save($sapOrderBatch);
        }
    }

    /**
     * Update the credit order process
     *
     * @param \Magento\Sales\Model\Order $order
     * @param $sku
     * @param $today
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    private function creditOrders($order, $sku, $today)
    {
        // get the sap order items collection
        $sapOrderBatchCreditmemos = $this->_sapOrderBatchCreditmemoCollectionFactory->create();
        $sapOrderBatchCreditmemos->addFieldToFilter('order_id', ['eq' => $order->getId()]);
        $sapOrderBatchCreditmemos->addFieldToFilter('sku', ['eq' => $sku]);

        // make sure that there is something provided
        // there should only be one
        if ($sapOrderBatchCreditmemos->count() > 0)
        {
            /**
             * @var \SMG\Sap\Model\SapOrderBatchCreditmemo $sapOrderBatchCreditmemo
             */
            foreach ($sapOrderBatchCreditmemos as $sapOrderBatchCreditmemo)
            {
                // update the process date
                $sapOrderBatchCreditmemo->setData('credit_process_date', $today);

                // save the changes
                $this->_sapOrderBatchCreditmemoResource->save($sapOrderBatchCreditmemo);
            }
        }
    }

    /**
     * Update the credit order process
     *
     * @param \Magento\Sales\Model\Order $order
     * @param $sku
     * @param $today
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    private function rmaOrders($order, $sku, $today)
    {
        // get the sap order items collection
        $sapOrderBatchRmas = $this->_sapOrderBatchRmaCollectionFactory->create();
        $sapOrderBatchRmas->addFieldToFilter('order_id', ['eq' => $order->getId()]);
        $sapOrderBatchRmas->addFieldToFilter('sku', ['eq' => $sku]);

        // make sure that there is something provided
        // there should only be one
        if ($sapOrderBatchRmas->count() > 0)
        {
            /**
             * @var \SMG\Sap\Model\SapOrderBatchItem $sapOrderBatchItem
             */
            foreach ($sapOrderBatchRmas as $sapOrderBatchRma)
            {
                // update the process date
                $sapOrderBatchRma->setData('return_process_date', $today);

                // save the changes
                $this->_sapOrderBatchRmaResource->save($sapOrderBatchRma);
            }
        }
    }
}