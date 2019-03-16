<?php

namespace SMG\Api\Helper;

use Magento\Sales\Api\OrderManagementInterface;
use Magento\Sales\Model\ResourceModel\Order\Invoice\CollectionFactory as InvoiceCollectionFactory;
use Magento\Sales\Model\ResourceModel\Order\Payment\Transaction\CollectionFactory as TransactionCollectionFactory;
use Psr\Log\LoggerInterface;
use SMG\Sap\Model\ResourceModel\SapOrderBatch;
use SMG\Sap\Model\ResourceModel\SapOrderBatch\CollectionFactory as SapOrderBatchCollectionFactory;

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
     * @var SapOrderBatch
     */
    protected $_sapOrderBatchResource;

    /**
     * AuthReversalHelper constructor.
     *
     * @param LoggerInterface $logger
     * @param ResponseHelper $responseHelper
     * @param SapOrderBatchCollectionFactory $sapOrderBatchCollectionFactory
     * @param InvoiceCollectionFactory $invoiceCollectionFactory
     * @param OrderManagementInterface $orderManagementInterface
     * @param TransactionCollectionFactory $transactionCollectionFactory
     * @param SapOrderBatch $sapOrderBatchResource
     */
    public function __construct(LoggerInterface $logger,
        ResponseHelper $responseHelper,
        SapOrderBatchCollectionFactory $sapOrderBatchCollectionFactory,
        InvoiceCollectionFactory $invoiceCollectionFactory,
        OrderManagementInterface $orderManagementInterface,
        TransactionCollectionFactory $transactionCollectionFactory,
        SapOrderBatch $sapOrderBatchResource)
    {
        $this->_logger = $logger;
        $this->_responseHelper = $responseHelper;
        $this->_sapOrderBatchCollectionFactory = $sapOrderBatchCollectionFactory;
        $this->_invoiceCollectionFactory = $invoiceCollectionFactory;
        $this->_orderManagementInterface = $orderManagementInterface;
        $this->_transactionCollectionFactory = $transactionCollectionFactory;
        $this->_sapOrderBatchResource = $sapOrderBatchResource;
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

            // was this order invoiced
            $wasInvoiced = false;

            // determine if there was an invoice created
            // if not then we can continue.  If it was created then
            // we will update the date for the authorization as
            // it might have been invoiced manually
            if ($invoices->count() > 0)
            {
                $wasInvoiced = true;
            }
            else
            {
                // cancel and reverse the credit authorization
                $this->cancelAndUnAuthorize($orderId);
            }

            // update the sap order batch
            $this->updateSapBatch($sapBatchOrder, $wasInvoiced);
        }

        // return
        return $orderStatusResponse;
    }

    /**
     * Determine if the cancel and authorization reversal was successful
     *
     * @param $orderId
     * @return bool
     */
    private function wasCancelAndUnAuthorizeSuccessful($orderId)
    {
        // set the success flag
        $isCancelAndUnAuthorizeSuccess = false;

        // load the transaction data
        $transactions = $this->_transactionCollectionFactory->create();
        $transactions->addFieldToFilter('order_id', ['eq' => $orderId]);
        $transactions->addFieldToFilter('txn_type', ['eq' => 'void']);

        // loop through the transactions but there should only be one
        foreach ($transactions as $transaction)
        {
            $additionalInformation = $transaction->getData('additional_information');
            if (!empty($additionalInformation))
            {
                if (in_array($additionalInformation['raw_details_info']['response'], self::CAPTURE_APPROVED_STATUS))
                {
                    $isCancelAndUnAuthorizeSuccess = true;
                }
            }
        }

        // return
        return $isCancelAndUnAuthorizeSuccess;
    }

    /**
     * Update the Sap Batch Order table
     *
     * @param $sapBatchOrder \SMG\Sap\Model\SapOrderBatch
     * @param $wasInvoiced bool
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    private function updateSapBatch($sapBatchOrder, $wasInvoiced)
    {
        // get the order if for this order
        $orderId = $sapBatchOrder->getData('order_id');

        // check the status of the cancel
        if ($wasInvoiced || $this->wasCancelAndUnAuthorizeSuccessful($orderId))
        {
            $today = date('Y-m-d H:i:s');

            // set the capture date
            $sapBatchOrder->setData('unauthorized_process_date', $today);

            // save the data
            $this->_sapOrderBatchResource->save($sapBatchOrder);
        }
    }

    /**
     * Cancel and Release Authorization from card
     *
     * @param $orderId
     */
    private function cancelAndUnAuthorize($orderId)
    {
        // cancel the request
        $this->_orderManagementInterface->cancel($orderId);
    }
}