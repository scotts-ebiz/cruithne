<?php

namespace SMG\Api\Helper;

use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Sales\Model\Order\Payment\Transaction\Repository as TransactionRepository;
use Psr\Log\LoggerInterface;
use SMG\Sap\Model\ResourceModel\SapOrderBatch\CollectionFactory as SapOrderBatchCollectionFactory;

class InvoiceReconciliationHelper
{
    // Output JSON file constants
    const TRANSACTION_NUMBER = 'TransactionNum';
    const TRANSACTION_TOTAL_AMOUNT = 'TransTotalAmt';
    const TRANSACTION_DATE = 'TransactionDate';
    const CREDIT_OR_DEBIT_FLAG = 'CreditOrDebit';
    const ORDER_NUMBER = 'MagentoOrderNum';
    const SAP_ORDER_NUMBER = 'SAPOrderNum';
    const ORDER_TOTAL_AMOUNT = 'OrderTotalAmount';
    const SAP_PAYER = 'SAPPayer';
    const WEBSITE_URL = 'WebsiteURL';

    /**
     * @var LoggerInterface
     */
    protected $_logger;

    /**
     * @var SapOrderBatchCollectionFactory
     */
    protected $_sapOrderBatchCollectionFactory;

    /**
     * @var FilterBuilder
     */
    protected $_filterBuilder;

    /**
     * @var SearchCriteriaBuilder
     */
    protected $_searchCriteriaBuilder;

    /**
     * @var TransactionRepository
     */
    protected $_transactionRepository;

    /**
     * @var ResponseHelper
     */
    protected $_responseHelper;

    /**
     * OrdersHelper constructor.
     *
     * @param LoggerInterface $logger
     * @param SapOrderBatchCollectionFactory $sapOrderBatchCollectionFactory
     * @param FilterBuilder $filterBuilder
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param TransactionRepository $transactionRepository
     * @param ResponseHelper $responseHelper
     */
    public function __construct(LoggerInterface $logger,
        SapOrderBatchCollectionFactory $sapOrderBatchCollectionFactory,
        FilterBuilder $filterBuilder,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        TransactionRepository $transactionRepository,
        ResponseHelper $responseHelper)
    {
        $this->_logger = $logger;
        $this->_sapOrderBatchCollectionFactory = $sapOrderBatchCollectionFactory;
        $this->_filterBuilder = $filterBuilder;
        $this->_searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->_transactionRepository = $transactionRepository;
        $this->_responseHelper = $responseHelper;
    }

    /**
     * Get the sales orders in the desired format
     *
     * @return string
     */
    public function getOrders()
    {
        // get the orders that are ready for invoice reconciliation
        $sapOrderBatches = $this->_sapOrderBatchCollectionFactory->create();
        $sapOrderBatches->addFieldToFilter('is_invoice_reconciliation', ['eq' => true]);
        $sapOrderBatches->addFieldToFilter('invoice_reconciliation_date', ['null' => true]);

        // check if there are orders to process
        if ($sapOrderBatches->count() > 0)
        {
            /**
             * @var \SMG\Sap\Model\SapOrderBatch $sapOrderBatch
             */
            foreach ($sapOrderBatches as $sapOrderBatch)
            {
                // get the order id
                $orderId = $sapOrderBatch->getData('order_id');

                // Get the sales order
                /**
                 * @var \Magento\Sales\Model\Order $order
                 */
                $order = $sapOrderBatch->getOrder();

                // split the base url into different parts for later use
                $urlParts = parse_url($order->getStore()->getBaseUrl());

                // Get the sales order sap
                /**
                 * @var \SMG\Sap\Model\SapOrder $sapOrder
                 */
                $sapOrder = $sapOrderBatch->getOrderSap();

                // create the necessary filters for the transaction repository
                $filters[] = $this->_filterBuilder->setField('order_id')
                    ->setValue($orderId)
                    ->create();
                $searchCriteria = $this->_searchCriteriaBuilder->addFilters($filters)
                    ->create();

                // get the list of transactions for the order
                $transactions = $this->_transactionRepository->getList($searchCriteria);
                if(!empty($transactions))
                {
                    /**
                     * @var \Magento\Sales\Api\Data\TransactionInterface $transaction
                     */
                    foreach ($transactions as $transaction)
                    {
                        // set the add flag to false
                        $isAddOrder = false;

                        // default the credit/debit flag to debit
                        $creditOrDebitFlag = 'DR';

                        // determine what type of transaction this is
                        if($transaction->getTxnType() == "refund")
                        {
                            // set the add flag to false
                            $isAddOrder = true;

                            // default the credit/debit flag to credit
                            $creditOrDebitFlag = 'CR';

                            // create a filter for the credit memos
                            $creditmemoFilters[] = $this->_filterBuilder->setField('transaction_id')
                                ->setValue($transaction->getTxnId())
                                ->create();

                            $searchCriteria = $this->_searchCriteriaBuilder->addFilters($creditmemoFilters)
                                ->create();

                            /**
                             * @var \Magento\Sales\Model\ResourceModel\Order\Creditmemo\Collection $creditMemos
                             */
                            $creditMemos = $order->getCreditmemosCollection()->getFiltered($searchCriteria);

                            // there should only be one credit memo so get the first one
                            /**
                             * @var \Magento\Sales\Model\Order\Creditmemo $creditMemo
                             */
                            $creditMemo = $creditMemos->getFirstItem();

                            // get the transaction amount from the credit memo
                            $transactionAmount = $creditMemo->getGrandTotal();
                        }
                        else if($transaction->getTxnType() == "capture")
                        {
                            // set the add flag to false
                            $isAddOrder = true;

                            // get the transaction amount from the invoice
                            $transactionAmount = $order->getData('total_invoiced');
                        }

                        // add to the return array
                        if ($isAddOrder)
                        {
                            $ordersArray[] = array(
                                self::TRANSACTION_NUMBER => $transaction->getTxnId(),
                                self::TRANSACTION_TOTAL_AMOUNT => $transactionAmount,
                                self::TRANSACTION_DATE => $transaction->getCreatedAt(),
                                self::CREDIT_OR_DEBIT_FLAG => $creditOrDebitFlag,
                                self::ORDER_NUMBER => $order->getIncrementId(),
                                self::SAP_ORDER_NUMBER => $sapOrder->getData('sap_order_id'),
                                self::ORDER_TOTAL_AMOUNT => $order->getGrandTotal(),
                                self::SAP_PAYER => $sapOrder->getData('sap_payer_id'),
                                self::WEBSITE_URL => $urlParts['host']
                            );
                        }
                    }
                }
            }
        }

        // determine if there is anything there to send
        if (empty($ordersArray))
        {
            // log that there were no records found.
            $this->_logger->info("SMG\Api\Helper\InvoiceReconciliationHelper - No Orders were found for processing.");

            $orders = $this->_responseHelper->createResponse(true, 'No Orders where found for processing.');
        }
        else
        {
            $orders = $this->_responseHelper->createResponse(true, $ordersArray);
        }

        // return
        return $orders;
    }
}
