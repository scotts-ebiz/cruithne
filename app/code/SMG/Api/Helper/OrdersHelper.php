<?php

namespace SMG\Api\Helper;

use Magento\Framework\App\ResourceConnection;
use Magento\Sales\Api\CreditmemoRepositoryInterface;
use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Api\Data\CreditmemoItemInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\Order\Item;
use Magento\Sales\Model\Order\ItemFactory;
use Magento\Sales\Model\ResourceModel\Order as OrderResource;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Magento\Sales\Model\ResourceModel\Order\Item as ItemResource;
use Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory as OrderItemCollectionFactory;
use Psr\Log\LoggerInterface;
use SMG\OfflineShipping\Model\ShippingConditionCodeFactory;
use SMG\OfflineShipping\Model\ResourceModel\ShippingConditionCode as ShippingConditionCodeResource;
use SMG\Sap\Model\SapOrderFactory;
use SMG\Sap\Model\ResourceModel\SapOrder as SapOrderResource;
use SMG\Sap\Model\ResourceModel\SapOrderBatchItem\CollectionFactory as SapOrderBatchItemCollectionFactory;

class OrdersHelper
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
     * @var OrderCollectionFactory
     */
    protected $_orderCollectionFactory;

    /**
     * @var OrderItemCollectionFactory
     */
    protected $_orderItemCollectionFactory;

    /**
     * @var ShippingConditionCodeFactory
     */
    protected $_shippingConditionCodeFactory;

    /**
     * @var ShippingConditionCodeResource
     */
    protected $_shippingConditionCodeResource;

    /**
     * @var SapOrderBatchItemCollectionFactory
     */
    protected  $_sapOrderBatchItemCollectionFactory;

    /**
     * @var OrderFactory
     */
    protected $_orderFactory;

    /**
     * @var OrderResource
     */
    protected $_orderResource;

    /**
     * @var ItemFactory
     */
    protected $_itemFactory;

    /**
     * @var ItemResource
     */
    protected $_itemResource;

    /**
     * @var SapOrderFactory
     */
    protected $_sapOrderFactory;

    /**
     * @var SapOrderResource
     */
    protected $_sapOrderResource;

    /**
     * @var CreditmemoRepositoryInterface
     */
    protected $_creditmemoRespository;

    /**
     * OrdersHelper constructor.
     *
     * @param LoggerInterface $logger
     * @param ResourceConnection $resourceConnection
     * @param ResponseHelper $responseHelper
     * @param OrderCollectionFactory $orderCollectionFactory
     * @param OrderItemCollectionFactory $orderItemCollectionFactory
     * @param ShippingConditionCodeFactory $shippingConditionCodeFactory
     * @param ShippingConditionCodeResource $shippingConditionCodeResource
     * @param SapOrderBatchItemCollectionFactory $sapOrderBatchItemCollectionFactory
     * @parma OrderFactory $orderFactory
     * @param OrderResource $orderResource
     # @param ItemFactory $itemFactory
     * @param ItemResource $itemResource
     * @param SapOrderFactory $sapOrderFactory
     * @param SapOrderResource $sapOrderResource
     * @param CreditmemoRepositoryInterface $creditmemoRepository
     */
    public function __construct(LoggerInterface $logger,
        ResourceConnection $resourceConnection,
        ResponseHelper $responseHelper,
        OrderCollectionFactory $orderCollectionFactory,
        OrderItemCollectionFactory $orderItemCollectionFactory,
        ShippingConditionCodeFactory $shippingConditionCodeFactory,
        ShippingConditionCodeResource $shippingConditionCodeResource,
        SapOrderBatchItemCollectionFactory $sapOrderBatchItemCollectionFactory,
        OrderFactory $orderFactory,
        OrderResource $orderResource,
        ItemFactory $itemFactory,
        ItemResource $itemResource,
        SapOrderFactory $sapOrderFactory,
        SapOrderResource $sapOrderResource,
        CreditmemoRepositoryInterface $creditmemoRepository)
    {
        $this->_logger = $logger;
        $this->_resourceConnection = $resourceConnection;
        $this->_responseHelper = $responseHelper;
        $this->_orderCollectionFactory = $orderCollectionFactory;
        $this->_orderItemCollectionFactory = $orderItemCollectionFactory;
        $this->_shippingConditionCodeFactory = $shippingConditionCodeFactory;
        $this->_shippingConditionCodeResource = $shippingConditionCodeResource;
        $this->_sapOrderBatchItemCollectionFactory = $sapOrderBatchItemCollectionFactory;
        $this->_orderFactory = $orderFactory;
        $this->_orderResource = $orderResource;
        $this->_itemFactory = $itemFactory;
        $this->_itemResource = $itemResource;
        $this->_sapOrderFactory = $sapOrderFactory;
        $this->_sapOrderResource = $sapOrderResource;
        $this->_creditmemoRespository = $creditmemoRepository;
    }

    /**
     * Get the sales orders in the desired format
     *
     * @param $requestData
     *
     * @return string
     */
    public function getOrders($requestData)
    {
        // make sure that we were given something from the request
        if (!empty($requestData))
        {
            // determine if the required input parameters were availabe
            if (array_key_exists("startDate", $requestData))
            {
                // get the start date
                $startDate = $requestData["startDate"];

                if (array_key_exists("endDate", $requestData))
                {
                    // get the end date
                    $endDate = $requestData["endDate"];

                    // get the debit order data
                    $debitArray = $this->getDebitOrderData($startDate, $endDate);

                    // get the credit order data
                    $creditArray = $this->getCreditOrderData();

                    $ordersArray = array_merge($debitArray, $creditArray);

                    // determine if there is anything there to send
                    if (empty($ordersArray))
                    {
                        // log that there were no records found.
                        $this->_logger->info("SMG\Api\Helper\OrdersHelper - No Orders were found for Begin Date: " . $startDate . " and End Date: " . $endDate);

                        $orders = $this->_responseHelper->createResponse(true, 'No Orders where found for Begin Date: ' . $startDate . " and End Date: " . $endDate);
                    }
                    else
                    {
                        $orders = $this->_responseHelper->createResponse(true, $ordersArray);
                    }
                }
                else
                {
                    // log the error
                    $this->_logger->error("SMG\Api\Helper\OrdersHelper - The End Date was not provided.");

                    $orders = $this->_responseHelper->createResponse(false, 'The End Date was not provided.');
                }
            } else
            {
                // log the error
                $this->_logger->error("SMG\Api\Helper\OrdersHelper - The Start Date was not provided.");

                $orders = $this->_responseHelper->createResponse(false, 'The Start Date was not provided.');
            }
        }
        else
        {
            // log the error
            $this->_logger->error("SMG\Api\Helper\OrdersHelper - The Start Date and End Date was not provided.");

            $orders = $this->_responseHelper->createResponse(false, 'The Start Date and End Date was not provided.');
        }

        // return
        return $orders;
    }

    /**
     * Get the debit orders
     *
     * @param $startDate
     * @param $endDate
     * @return array
     */
    private function getDebitOrderData($startDate, $endDate)
    {
        $ordersArray = array();

        // get the list of orders with shipping address info
        $orders = $this->_orderCollectionFactory->create();
        $orders->addFieldToFilter("created_at", ["gteq" => $startDate]);
        $orders->addFieldToFilter("created_at", ["lt" => $endDate]);

        // check if there are orders to process
        if ($orders->count() > 0)
        {
            /**
             * @var \Magento\Sales\Model\Order $order
             */
            foreach ($orders as $order)
            {
                $orderItems = $this->_orderItemCollectionFactory->create();
                $orderItems->addFieldToFilter("order_id", ['eq' => $order->getId()]);
                $orderItems->addFieldToFilter("product_type", ['neq' => 'bundle']);

                /**
                 * @var \Magento\Sales\Model\Order\Item $orderItem
                 */
                foreach ($orderItems as $orderItem)
                {
                    $ordersArray[] = $this->addRecordToOrdersArray($order, $orderItem);
                }
            }
        }

        // return
        return $ordersArray;
    }

    /**
     * Takes the order and item details and puts it in an array
     *
     * @param Order $order
     * @param Item $orderItem
     * @param CreditmemoInterface $creditMemo
     * @param CreditmemoItemInterface $creditMemoItem
     * @return array
     */
    private function addRecordToOrdersArray($order, $orderItem, $creditMemo = null, $creditMemoItem = null)
    {
        // get tomorrows date
        $tomorrow = date('Y-m-d', strtotime("tomorrow"));

        // split the base url into different parts for later use
        $urlParts = parse_url($order->getStore()->getBaseUrl());

        // get the shipping condition data
        /**
         * @var /SMG/OfflineShipping/Model/ShippingConditionCode $shippingCondition
         */
        $shippingCondition = $this->_shippingConditionCodeFactory->create();
        $this->_shippingConditionCodeResource->load($shippingCondition, $order->getShippingMethod(), 'shipping_method');

        // get the shipping address
        $address = $order->getShippingAddress();

        // check to see if there was a value
        $invoiceAmount = $order->getData('total_invoiced');
        if (empty($invoiceAmount))
        {
            $invoiceAmount = '';
        }

        // get the quantity
        $quantity = $orderItem->getQtyOrdered();
        $shippingAmount = $order->getData('shipping_amount');

        // set credit fields to empty
        $creditAmount = '';
        $referenceDocNum = '';
        $creditComment = '';
        $orderReason = '';
        $discCondCode = '';
        $surchCondCode = '';
        $discFixedAmt = '';
        $surchFixedAmt = '';
        $discPerAmt = '';
        $surchPerAmt = '';

        // determine what type of order
        $debitCreditFlag = 'DR';
        if (!empty($creditMemo) && !empty($creditMemoItem))
        {
            $debitCreditFlag = 'CR';

            // set other credit memeo type fields
            $quantity = $creditMemoItem->getQty();
            $shippingAmount = $creditMemo->getShippingAmount();
            $creditAmount = $creditMemoItem->getRowTotalInclTax();
            $creditComment = $creditMemo->getData('customer_note');
            $orderReason = $creditMemoItem->getData('refunded_reason_code');

            // get the sap order for the billing doc number
            /**
             * @var \SMG\Sap\Model\SapOrder $sapOrder
             */
            $sapOrder = $this->_sapOrderFactory->create();
            $this->_sapOrderResource->load($sapOrder, $order->getId(), 'order_id');

            // get the billing doc number
            $referenceDocNum = $sapOrder->getData('sap_billing_doc_number');
        }

        // return
        return array(
            'OrderNumber' => $order->getIncrementId(),
            'DatePlaced' => $order->getData('created_at'),
            'SAPDeliveryDate' => $tomorrow,
            'CustomerName' => $order->getData('customer_firstname') . ' ' . $order->getData('customer_lastname'),
            'CustomerShippingAddressStreet' => $address->getStreetLine(1),
            'CustomerShippingAddressCity' => $address->getCity(),
            'CustomerShippingAddressState' => $address->getRegion(),
            'CustomerShippingAddressZip' => $address->getPostcode(),
            'SMGSKU' => $orderItem->getSku(),
            'WebSKU' => $orderItem->getSku(),
            'Quantity' => $quantity,
            'Unit' => 'EA',
            'UnitPrice' => $orderItem->getPrice(),
            'GrossSales' => $order->getData('grand_total'),
            'ShippingAmount' => $shippingAmount,
            'ExemptAmount' => '0',
            'DiscountAmount' => $order->getData('base_discount_amount'),
            'Subtotal' => $order->getData('subtotal'),
            'TaxRate' => $orderItem->getTaxPercent(),
            'SalesTax' => $order->getData('tax_amount'),
            'InvoiceAmount' => $invoiceAmount,
            'DeliveryLocation' => '',
            'CustomerEmail' => $order->getData('customer_email'),
            'CustomerPhone' => $address->getTelephone(),
            'DeliveryWindow' => '',
            'ShippingCondition' => $shippingCondition->getData('sap_shipping_method'),
            'WebsiteURL' => $urlParts['host'],
            'CreditAmount' => $creditAmount,
            'CR/DR/RE/Flag' => $debitCreditFlag,
            'ReferenceDocNum' => $referenceDocNum,
            'CreditComment' => $creditComment,
            'OrderReason' => $orderReason,
            'DiscCondCode' => $discCondCode,
            'SurchCondCode' => $surchCondCode,
            'DiscFixedAmt' => $discFixedAmt,
            'SurchFixedAmt' => $surchFixedAmt,
            'DiscPercAmt' => $discPerAmt,
            'SurchPercAmt' => $surchPerAmt
        );
    }

    /**
     * Get the array of credit orders
     *
     * @return array
     */
    private function getCreditOrderData()
    {
        $ordersArray = array();

        // get the orders that are ready to be sent to SAP
        $sapOrderBatchItems = $this->_sapOrderBatchItemCollectionFactory->create();
        $sapOrderBatchItems->addFieldToFilter('is_credit', ['eq' => true]);
        $sapOrderBatchItems->addFieldToFilter('credit_process_date', ['null' => true]);

        // check if there are orders to process
        if ($sapOrderBatchItems->count() > 0)
        {
            /**
             * @var \SMG\Sap\Model\SapOrderBatchItem $sapOrderBatchItem
             */
            foreach ($sapOrderBatchItems as $sapOrderBatchItem)
            {
                // get the required fields needed for processing
                $orderId = $sapOrderBatchItem->getData('order_id');
                $orderItemId = $sapOrderBatchItem->getData('order_item_id');
                $creditmemoId = $sapOrderBatchItem->getData('creditmemo_order_id');
                $sku = $sapOrderBatchItem->getData('sku');

                // Get the sales order
                /**
                 * @var \Magento\Sales\Model\Order $order
                 */
                $order = $this->_orderFactory->create();
                $this->_orderResource->load($order, $orderId);

                // Get the sales order item
                /**
                 * @var \Magento\Sales\Model\Order\Item $orderItem
                 */
                $orderItem = $this->_itemFactory->create();
                $this->_itemResource->load($orderItem, $orderItemId);

                // Get the credit memo
                $creditMemo = $this->_creditmemoRespository->get($creditmemoId);

                // Get the credit memo items
                $creditMemoItems = $creditMemo->getItems();
                foreach ($creditMemoItems as $creditMemoItem)
                {
                    // see if the sku is the same as the sku that we are looking for
                    if ($sku === $creditMemoItem->getSku())
                    {
                        // add the record to the orders array
                        $ordersArray[] = $this->addRecordToOrdersArray($order, $orderItem, $creditMemo, $creditMemoItem);

                        // get out of the loop as we found it
                        break;
                    }
                }
            }
        }

        // return
        return $ordersArray;
    }
}