<?php

namespace SMG\Api\Helper;

use Magento\Sales\Api\OrderManagementInterface;
use Magento\Sales\Model\Order\Status\HistoryFactory;
use Magento\Sales\Model\ResourceModel\Order\Invoice\CollectionFactory as InvoiceCollectionFactory;
use Magento\Sales\Model\ResourceModel\Order\Payment\Transaction\CollectionFactory as TransactionCollectionFactory;
use Magento\Sales\Model\ResourceModel\Order\Payment\Transaction as TransactionResource;
use Magento\Sales\Model\ResourceModel\Order\Status\History as HistoryResource;
use Psr\Log\LoggerInterface;
use SMG\Sap\Model\SapOrderHistoryFactory;
use SMG\Sap\Model\ResourceModel\SapOrder as SapOrderResource;
use SMG\Sap\Model\ResourceModel\SapOrderBatch;
use SMG\Sap\Model\ResourceModel\SapOrderBatch\CollectionFactory as SapOrderBatchCollectionFactory;
use SMG\Sap\Model\ResourceModel\SapOrderHistory as SapOrderHistoryResource;

class AuthReversalHelper
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
     * @var SapOrderBatchCollectionFactory
     */
    protected $_sapOrderBatchCollectionFactory;

    /**
     * @var InvoiceCollectionFactory
     */
    protected $_invoiceCollectionFactory;

    /**
     * @var OrderManagementInterface
     */
    protected $_orderManagementInterface;

    /**
     * @var TransactionCollectionFactory
     */
    protected $_transactionCollectionFactory;

    /**
     * @var TransactionResource
     */
    protected $_transactionResource;

    /**
     * @var SapOrderBatch
     */
    protected $_sapOrderBatchResource;

    /**
     * @var HistoryFactory
     */
    protected $_historyFactory;

    /**
     * @var HistoryResource
     */
    protected $_historyResource;

    /**
     * @var SapOrderResource
     */
    protected $_sapOrderResource;

    /**
     * @var SapOrderHistoryFactory
     */
    protected $_sapOrderHistoryFactory;

    /**
     * @var SapOrderHistoryResource
     */
    protected $_sapOrderHistoryResource;

    /**
     * AuthReversalHelper constructor.
     *
     * @param LoggerInterface $logger
     * @param ResponseHelper $responseHelper
     * @param SapOrderBatchCollectionFactory $sapOrderBatchCollectionFactory
     * @param InvoiceCollectionFactory $invoiceCollectionFactory
     * @param OrderManagementInterface $orderManagementInterface
     * @param TransactionCollectionFactory $transactionCollectionFactory
     * @param TransactionResource $transactionResource
     * @param SapOrderBatch $sapOrderBatchResource
     * @param HistoryFactory $historyFactory
     * @param HistoryResource $historyResource
     * @param SapOrderResource $sapOrderResource
     * @param SapOrderHistoryFactory $sapOrderHistoryFactory
     * @param SapOrderHistoryResource $sapOrderHistoryResource
     */
    public function __construct(LoggerInterface $logger,
        ResponseHelper $responseHelper,
        SapOrderBatchCollectionFactory $sapOrderBatchCollectionFactory,
        InvoiceCollectionFactory $invoiceCollectionFactory,
        OrderManagementInterface $orderManagementInterface,
        TransactionCollectionFactory $transactionCollectionFactory,
        TransactionResource $transactionResource,
        SapOrderBatch $sapOrderBatchResource,
        HistoryFactory $historyFactory,
        HistoryResource $historyResource,
        SapOrderResource $sapOrderResource,
        SapOrderHistoryFactory $sapOrderHistoryFactory,
        SapOrderHistoryResource $sapOrderHistoryResource)
    {
        $this->_logger = $logger;
        $this->_responseHelper = $responseHelper;
        $this->_sapOrderBatchCollectionFactory = $sapOrderBatchCollectionFactory;
        $this->_invoiceCollectionFactory = $invoiceCollectionFactory;
        $this->_orderManagementInterface = $orderManagementInterface;
        $this->_transactionCollectionFactory = $transactionCollectionFactory;
        $this->_transactionResource = $transactionResource;
        $this->_sapOrderBatchResource = $sapOrderBatchResource;
        $this->_historyFactory = $historyFactory;
        $this->_historyResource = $historyResource;
        $this->_sapOrderResource = $sapOrderResource;
        $this->_sapOrderHistoryFactory = $sapOrderHistoryFactory;
        $this->_sapOrderHistoryResource = $sapOrderHistoryResource;
    }

    /**
     * This function will process all of the
     * unauthorization requests to the credit
     * card
     *
     * @return string
     */
    public function processAuthReversal()
    {
        // variables
        $orderStatusResponse = $this->_responseHelper->createResponse(true, "The auth reversal process completed successfully.");

        // get all of the records in the batch capture table
        // where the capture processed date is not null and the capture flag is set
        $sapBatchOrders = $this->_sapOrderBatchCollectionFactory->create();
        $sapBatchOrders->addFieldToFilter('is_unauthorized', ['eq' => true]);
        $sapBatchOrders->addFieldToFilter('unauthorized_process_date', ['null' => true]);

        // loop through all of the batch capture records that have not been processed
        foreach ($sapBatchOrders as $sapBatchOrder)
        {
            // get the order if for this order
            $orderId = $sapBatchOrder->getData('order_id');

            // check to see if the order was already captured.
            // if it was then we don't want to cancel the order if that is the case
            $invoices = $this->_invoiceCollectionFactory->create();
            $invoices->addFieldToFilter('order_id', ['eq' => $orderId]);

            // determine if there was an invoice created
            // if not then we can continue.  If it was created then
            // we will update the date for the authorization as
            // it might have been invoiced manually
            if ($invoices->count() > 0)
            {
                // update the sap order batch
                $this->updateSapBatch($sapBatchOrder);
            }
            else
            {
                // cancel and reverse the credit authorization
                $this->cancelAndUnAuthorize($orderId, $sapBatchOrder);
            }
        }

        // return
        return $orderStatusResponse;
    }

    /**
     * Update the Sap Batch Order table
     *
     * @param $sapBatchOrder \SMG\Sap\Model\SapOrderBatch
     */
    private function updateSapBatch($sapBatchOrder)
    {
        try
        {
            $today = date('Y-m-d H:i:s');

            // set the capture date
            $sapBatchOrder->setData('unauthorized_process_date', $today);

            // save the data
            $this->_sapOrderBatchResource->save($sapBatchOrder);
        }
        catch (\Exception $e)
        {
            $errorMsg = "An error has occurred during Batch Date Update for order - " . $sapBatchOrder->getData('order_id') . " - " . $e->getMessage();
            $this->_logger->error($errorMsg);
        }
    }

    /**
     * Cancel and Release Authorization from card
     *
     * @param $orderId
     * @param $sapBatchOrder
     */
    private function cancelAndUnAuthorize($orderId, $sapBatchOrder)
    {
        try
        {
            // cancel the request
            $this->_orderManagementInterface->cancel($orderId);

            // update the sap order batch
            $this->updateSapBatch($sapBatchOrder);
        }
        catch (\Exception $e)
        {
            $errorMsg = "An error has occurred during Reverse Authorization for order - " . $orderId . " - " . $e->getMessage();
            $this->_logger->error($errorMsg);

            // add to the order history so a message will display on the order
            $this->addOrderHistory($orderId);

            // update the transaction to close and then cancel
            $this->updateTransaction($orderId);
        }

        // update the status
        $this->updateStatus($orderId, 'order_canceled');
    }

    /**
     * This function will update the sales_order_status_history.
     * This table displays on the Order under the comments section.
     *
     * @param $orderId
     */
    private function addOrderHistory($orderId)
    {
        try
        {
            // get the date for today with time
            $today = date('Y-m-d H:i:s');

            // add the error to the history
            /**
             * @var \Magento\Sales\Model\Order\Status\History $orderHistory
             */
            $orderHistory = $this->_historyFactory->create();

            // set the desired values
            $orderHistory->setParentId($orderId);
            $orderHistory->setComment('Reverse Authorization has failed.');
            $orderHistory->setStatus('closed');
            $orderHistory->setCreatedAt($today);
            $orderHistory->setEntityName('order');

            // save the history for displaying on the order
            $this->_historyResource->save($orderHistory);
        }
        catch (\Exception $e)
        {
            $errorMsg = "Could not add to the order history for order - " . $orderId . " - " . $e->getMessage();
            $this->_logger->error($errorMsg);
        }
    }

    /**
     * This function updates the authorizaton to close so when we try to cancel it
     * will not try to reverse auth as there was an issue with the auth
     *
     * @param $orderId
     */
    private function updateTransaction($orderId)
    {
        try
        {
            // load the transaction data
            $transactions = $this->_transactionCollectionFactory->create();
            $transactions->addFieldToFilter('order_id', ['eq' => $orderId]);
            $transactions->addFieldToFilter('txn_type', ['eq' => 'authorization']);

            // loop through the transactions there should only be one
            /**
             * @var \Magento\Sales\Model\Order\Payment\Transaction $transaction
             */
            foreach ($transactions as $transaction)
            {
                // update the closed
                $transaction->close(true);
            }
        }
        catch (\Exception $e)
        {
            $errorMsg = "Could not update the authorization to closed for order - " . $orderId . " - " . $e->getMessage();
            $this->_logger->error($errorMsg);
        }
    }

    /**
     * Updates the order status for the desired order
     *
     * @param $orderId
     * @param $orderStatus
     */
    public function updateStatus($orderId, $orderStatus)
    {
        try
        {
            /**
             * @var \SMG\Sap\Model\SapOrder $sapOrder
             */
            $sapOrder = $this->_sapOrderResource->getSapOrderByOrderId($orderId);

            // get the current order status
            $previousOrderStatus = $sapOrder->getData('order_status');

            // change the status because the capture failure
            $sapOrder->setData('order_status', $orderStatus);

            // update the sap order
            $this->_sapOrderResource->save($sapOrder);

            // create a new history
            /**
             * @var \SMG\Sap\Model\SapOrderHistory $sapOrderHistory
             */
            $sapOrderHistory = $this->_sapOrderHistoryFactory->create();
            $sapOrderHistory->setData('order_sap_id', $sapOrder->getId());
            $sapOrderHistory->setData('order_status', $orderStatus);

            // create order status notes
            $orderStatusNotes = 'Order Status was ' . $previousOrderStatus . ' now ' . $orderStatus . '. ';
            $sapOrderHistory->setData('order_status_notes', $orderStatusNotes);

            $this->_sapOrderHistoryResource->save($sapOrderHistory);
        }
        catch (\Exception $e)
        {
            $errorMsg = "Could not update the order status for order - " . $orderId . " - " . $e->getMessage();
            $this->_logger->error($errorMsg);
        }
    }
}