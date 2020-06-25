<?php

namespace SMG\Api\Helper;

use Magento\Framework\App\ResourceConnection;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\ResourceModel\Order as OrderResource;
use Psr\Log\LoggerInterface;
use SMG\Sap\Model\SapOrderBatchFactory;
use SMG\Sap\Model\ResourceModel\SapOrderBatch as SapOrderBatchResource;

class InvoiceReconciliationSentHelper
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
     * OrdersHelper constructor.
     *
     * @param LoggerInterface $logger
     * @param ResourceConnection $resourceConnection
     * @param ResponseHelper $responseHelper
     * @param SapOrderBatchFactory $sapOrderBatchFactory
     * @param SapOrderBatchResource $sapOrderBatchResource
     * @param OrderFactory $orderFactory
     * @param OrderResource $orderResource
     */
    public function __construct(LoggerInterface $logger,
        ResourceConnection $resourceConnection,
        ResponseHelper $responseHelper,
        SapOrderBatchFactory $sapOrderBatchFactory,
        SapOrderBatchResource $sapOrderBatchResource,
        OrderFactory $orderFactory,
        OrderResource $orderResource)
    {
        $this->_logger = $logger;
        $this->_resourceConnection = $resourceConnection;
        $this->_responseHelper = $responseHelper;
        $this->_sapOrderBatchFactory = $sapOrderBatchFactory;
        $this->_sapOrderBatchResource = $sapOrderBatchResource;
        $this->_orderFactory = $orderFactory;
        $this->_orderResource = $orderResource;
    }

    /**
     * Update the orders to notify that the invoice
     * reconciliation was sent to SAP successfully
     *
     * @param $requestData
     * @return string
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    public function updateOrders($requestData)
    {
        // variables
        $invoiceReconciliationSentResponse = $this->_responseHelper->createResponse(true, "The invoice reconciliation  sent process completed successfully.");

        // make sure that we were given something from the request
        if (!empty($requestData))
        {
            // get the date for today
            $today = date('Y-m-d H:i:s');

            // loop through the orders that were sent via the JSON file
            foreach ($requestData as $inputOrder)
            {
                // get the values from the input JSON
                $orderIncrementId = $inputOrder['MagentoOrderNum'];

                if (!empty($orderIncrementId))
                {
                    // get the order from the increment id
                    $order = $this->_orderFactory->create();
                    $this->_orderResource->load($order, $orderIncrementId, 'increment_id');

                    // load the sap order batch
                    $sapOrderBatch = $this->_sapOrderBatchFactory->create();
                    $this->_sapOrderBatchResource->load($sapOrderBatch, $order->getId(), 'order_id');

                    // since the order response will be sending a record for each item that is in
                    // the orders file but only need one then check to see if the date was already set
                    if (empty($sapOrderBatch->getData('invoice_reconciliation_date')))
                    {
                        // set the process date for this order
                        $sapOrderBatch->setData('invoice_reconciliation_date', $today);

                        // save
                        $this->_sapOrderBatchResource->save($sapOrderBatch);
                    }
                }
                else
                {
                    // log the error
                    $this->_logger->error("SMG\Api\Helper\InvoiceReconciliationSentHelper - The order number " . $orderIncrementId . " was not found.");
                }
            }
        }
        else
        {
            // log the error
            $this->_logger->error("SMG\Api\Helper\InvoiceReconciliationSentHelper - Nothing was provided to process.");

            $invoiceReconciliationSentResponse = $this->_responseHelper->createResponse(false, 'Nothing was provided to process.');
        }

        // return
        return $invoiceReconciliationSentResponse;
    }
}