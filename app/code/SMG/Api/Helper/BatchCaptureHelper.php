<?php

namespace SMG\Api\Helper;

use Magento\Sales\Api\InvoiceOrderInterface;
use Magento\Sales\Api\Data\InvoiceItemCreationInterfaceFactory;
use Magento\Sales\Model\Order\InvoiceFactory;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Order\Item;
use Magento\Sales\Model\Order;
use Magento\Sales\Api\Data\OrderInterfaceFactory;
use Magento\Sales\Model\ResourceModel\Order\Invoice as InvoiceResource;
use Magento\Sales\Model\ResourceModel\Order\Invoice\CollectionFactory as InvoiceCollectionFactory;
use Magento\Sales\Model\ResourceModel\Order\Payment\Transaction\CollectionFactory as TransactionCollectionFactory;
use Magento\Sales\Model\Spi\OrderResourceInterface;
use \Psr\Log\LoggerInterface;
use SMG\Sap\Model\SapOrder;
use SMG\Sap\Model\SapOrderBatchFactory;
use SMG\Sap\Model\ResourceModel\SapOrderBatch;
use SMG\Sap\Model\ResourceModel\SapOrder as SapOrderResource;
use SMG\Sap\Model\ResourceModel\SapOrderBatch\CollectionFactory as SapOrderBatchCollectionFactory;
use SMG\Sap\Model\ResourceModel\SapOrderItem\CollectionFactory as SapOrderItemCollectionFactory;

class BatchCaptureHelper
{
    const CAPTURE_APPROVED_STATUS = array (
        "000",
        "010",
        "011",
        "013",
        "470",
        "473"
    );

    /**
     * @var LoggerInterface
     */
    protected $_logger;

    /**
     * @var ResponseHelper
     */
    protected $_responseHelper;

    /**
     * @var SapOrderBatchFactory
     */
    protected $_sapOrderBatchFactory;

    /**
     * @var SapOrderBatch
     */
    protected $_sapOrderBatchResource;

    /**
     * @var SapOrderBatchCollectionFactory
     */
    protected $_sapOrderBatchCollectionFactory;

    /**
     * @var InvoiceOrderInterface
     */
    protected $_invoiceOrder;

    /**
     * @var SapOrderResource
     */
    protected $_sapOrderResource;

    /**
     * @var SapOrderItemCollectionFactory
     */
    protected $_sapOrderItemCollectionFactory;

    /**
     * @var InvoiceResource
     */
    protected $_invoiceResource;

    /**
     * @var InvoiceFactory
     */
    protected $_invoiceFactory;

    /**
     * @var TransactionCollectionFactory
     */
    protected $_transactionCollectionFactory;

    /**
     * @var InvoiceItemCreationInterfaceFactory
     */
    protected $_invoiceItemCreationInterfaceFactory;

    /**
     * @var InvoiceCollectionFactory
     */
    protected $_invoiceCollectionFactory;

    /**
     * @var OrderInterfaceFactory
     */
    protected $_orderFactory;

    /**
     * @var OrderResourceInterface
     */
    protected $_orderResource;

    /**
     * BatchCaptureHelper constructor.
     *
     * @param LoggerInterface $logger
     * @param ResponseHelper $responseHelper
     * @param SapOrderBatchFactory $sapOrderBatchFactory
     * @param SapOrderBatch $sapOrderBatchResource
     * @param SapOrderBatchCollectionFactory $sapOrderBatchCollectionFactory
     * @param InvoiceOrderInterface $invoiceOrder
     * @param SapOrderResource $sapOrderResource
     * @param SapOrderItemCollectionFactory $sapOrderItemCollectionFactory
     * @param InvoiceResource $invoiceResource
     * @param InvoiceFactory $invoiceFactory
     * @param TransactionCollectionFactory $transactionCollectionFactory
     * @param InvoiceItemCreationInterfaceFactory $invoiceItemCreationInterfaceFactory
     * @param InvoiceCollectionFactory $invoiceCollectionFactory
     * @param OrderInterfaceFactory $orderFactory
     * @param OrderResourceInterface $orderResource
     */
    public function __construct(LoggerInterface $logger,
        ResponseHelper $responseHelper,
        SapOrderBatchFactory $sapOrderBatchFactory,
        SapOrderBatch $sapOrderBatchResource,
        SapOrderBatchCollectionFactory $sapOrderBatchCollectionFactory,
        InvoiceOrderInterface $invoiceOrder,
        SapOrderResource $sapOrderResource,
        SapOrderItemCollectionFactory $sapOrderItemCollectionFactory,
        InvoiceResource $invoiceResource,
        InvoiceFactory $invoiceFactory,
        TransactionCollectionFactory $transactionCollectionFactory,
        InvoiceItemCreationInterfaceFactory $invoiceItemCreationInterfaceFactory,
        InvoiceCollectionFactory $invoiceCollectionFactory,
        OrderInterfaceFactory $orderFactory,
        OrderResourceInterface $orderResource)
    {
        $this->_logger = $logger;
        $this->_responseHelper = $responseHelper;
        $this->_sapOrderBatchFactory = $sapOrderBatchFactory;
        $this->_sapOrderBatchResource = $sapOrderBatchResource;
        $this->_sapOrderBatchCollectionFactory = $sapOrderBatchCollectionFactory;
        $this->_invoiceOrder = $invoiceOrder;
        $this->_sapOrderResource = $sapOrderResource;
        $this->_sapOrderItemCollectionFactory = $sapOrderItemCollectionFactory;
        $this->_invoiceResource = $invoiceResource;
        $this->_invoiceFactory = $invoiceFactory;
        $this->_transactionCollectionFactory = $transactionCollectionFactory;
        $this->_invoiceItemCreationInterfaceFactory = $invoiceItemCreationInterfaceFactory;
        $this->_invoiceCollectionFactory = $invoiceCollectionFactory;
        $this->_orderFactory = $orderFactory;
        $this->_orderResource = $orderResource;
    }

    /**
     * This function will capture the credit cards
     * for orders that have been properly processed
     * at SAP
     *
     * @return string
     */
    public function processBatchCapture()
    {
        // variables
        $orderStatusResponse = $this->_responseHelper->createResponse(true, "The batch capture process completed successfully.");

        // get all of the records in the batch capture table
        // where the capture processed date is not null and the capture flag is set
        $sapBatchOrders = $this->_sapOrderBatchCollectionFactory->create();
        $sapBatchOrders->addFieldToFilter('is_capture', ['eq' => true]);
        $sapBatchOrders->addFieldToFilter('capture_process_date', ['null' => true]);

        // loop through all of the batch capture records that have not been processed
        foreach ($sapBatchOrders as $sapBatchOrder)
        {
            // get the order if for this order
            $orderId = $sapBatchOrder->getData('order_id');

            // check to see if the order was already captured
            // and the flag was not set.  This could happen
            // if manually captured through the admin portal
            $invoices = $this->_invoiceCollectionFactory->create();
            $invoices->addFieldToFilter('order_id', ['eq' => $orderId]);

            // determine if there was an invoice created
            // if not then we can continue.  If it was created then
            // check if the capture can be done
            if ($invoices->count() > 0)
            {
                /**
                 * @var Invoice $invoice
                 */
                foreach ($invoices as $invoice)
                {
                    // check to see if this invoice can be captured
                    // if so then lets capture
                    if ($invoice->canCapture())
                    {
                        // capture the invoice
                        // this is when the order was invoiced but not capture
                        // which can only occur in the admin portal
                        $invoice->capture();

                        // update the sap order batch
                        $this->updateSapBatch($sapBatchOrder, $invoice);
                    }
                    else
                    {
                        // this must have been done on the admin so we need
                        // to update the date for capture
                        $this->updateSapBatch($sapBatchOrder, $invoice);
                    }
                }
            }
            else
            {
                $invoice = $this->createInvoiceAndCapture($sapBatchOrder);

                // update the sap order batch
                $this->updateSapBatch($sapBatchOrder, $invoice);
            }
        }

        // return
        return $orderStatusResponse;
    }

    /**
     * Determine if the capture was successful
     *
     * @param $invoice Invoice
     * @return bool
     */
    private function wasCaptureSuccessful($invoice)
    {
        // set the success flag
        $isBatchCaptureSuccess = true;

        // load the transaction data
        $transactions = $this->_transactionCollectionFactory->create();
        $transactions->addFieldToFilter('order_id', ['eq' => $invoice->getData('order_id')]);
        $transactions->addFieldToFilter('txn_id', ['eq' => $invoice->getData('transaction_id')]);

        // loop through the transactions but there should only be one
        foreach ($transactions as $transaction)
        {
            $additionalInformation = $transaction->getData('additional_information');
            if (!empty($additionalInformation))
            {
                if (!in_array($additionalInformation['raw_details_info']['response'], self::CAPTURE_APPROVED_STATUS))
                {
                    $isBatchCaptureSuccess = false;
                }
            }
        }

        // return
        return $isBatchCaptureSuccess;
    }

    /**
     * Update the Sap Batch Order table
     *
     * @param $sapBatchOrder \SMG\Sap\Model\SapOrderBatch
     * @param $invoice
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    private function updateSapBatch($sapBatchOrder, $invoice)
    {
        // check to see if the $invoice has been set
        if (isset($invoice))
        {
            $today = date('Y-m-d H:i:s');

            // set the capture date
            $sapBatchOrder->setData('capture_process_date', $today);

            // check the status of the invoice
            if ($this->wasCaptureSuccessful($invoice))
            {
                // check if the ship flag has been set
                // if it hasn't see if it can be set
                if (!$sapBatchOrder->getData('is_shipment'))
                {
                    // get the items from the sap item table to check
                    // to see if the order is able to be shipped
                    /**
                     * @var SapOrder $sapOrder
                     */
                    $sapOrder = $this->_sapOrderResource->getSapOrderByOrderId($sapBatchOrder->getData("order_id"));

                    // get the items
                    $sapOrderItems = $this->_sapOrderItemCollectionFactory->create();
                    $sapOrderItems->addFieldToFilter('order_sap_id', ['eq' => $sapOrder->getId()]);
                    $sapOrderItems->addFieldToFilter('ship_tracking_number', ['notnull' => true]);

                    // if there is a ship track number then we can set the flag
                    if ($sapOrderItems->count() > 0)
                    {
                        $sapBatchOrder->setData('is_shipment', true);
                    }
                }
            }
            else
            {
                // there was an issue with capturing the payment
                // so set to unauthorized flag
                $sapBatchOrder->setData("is_unauthorized", true);
            }

            // save the data
            $this->_sapOrderBatchResource->save($sapBatchOrder);
        }
    }

    /**
     * Invoice and Capture the order
     *
     * @param $sapBatchOrder
     * @return null
     */
    private function createInvoiceAndCapture($sapBatchOrder)
    {
        // initialize the invoice response
        $invoice = null;

        // get the order Id
        $orderId = $sapBatchOrder->getData("order_id");

        // get the order to determine if an invoice can be created
        // and to get the items for creating the invoice
        /**
         * @var Order $order
         */
        $order = $this->_orderFactory->create();
        $this->_orderResource->load($order, $orderId);

        if ($order->canInvoice())
        {
            // initialize the items array
            $items = [];

            /**
             * @var Item $orderItem
             */
            foreach ($order->getAllItems() as $orderItem)
            {
                /**
                 * @var InvoiceItemCreationInterfaceFactory $invoiceItemCreation
                 */
                $invoiceItemCreation = $this->_invoiceItemCreationInterfaceFactory->create();
                $invoiceItemCreation->setOrderItemId($orderItem->getItemId());
                $invoiceItemCreation->setQty($orderItem->getQtyOrdered());
                $items[] = $invoiceItemCreation;
            }

            // call the invoice API to create the invoice and capture the credit card request
            $invoiceId = $this->_invoiceOrder->execute($orderId, true, $items);
            if (!empty($invoiceId))
            {
                // load the invoice data
                $invoice = $this->_invoiceFactory->create();
                $this->_invoiceResource->load($invoice, $invoiceId);
            }
        }
        else
        {
            $this->_logger->error("The order Id " . $orderId . " can not be invoiced.  The order status currently is " . $order->getStatus());
        }

        // return
        return $invoice;
    }
}