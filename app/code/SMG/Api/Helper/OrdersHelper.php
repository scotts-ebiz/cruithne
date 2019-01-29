<?php

namespace SMG\Api\Helper;

use Magento\Framework\App\ResourceConnection;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory as OrderItemCollectionFactory;
use Psr\Log\LoggerInterface;

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
     * OrdersHelper constructor.
     *
     * @param LoggerInterface $logger
     * @param ResourceConnection $resourceConnection
     * @param ResponseHelper $responseHelper
     * @param OrderCollectionFactory $orderCollectionFactory
     * @param OrderItemCollectionFactory $orderItemCollectionFactory
     */
    public function __construct(LoggerInterface $logger,
        ResourceConnection $resourceConnection,
        ResponseHelper $responseHelper,
        OrderCollectionFactory $orderCollectionFactory,
        OrderItemCollectionFactory $orderItemCollectionFactory)
    {
        $this->_logger = $logger;
        $this->_resourceConnection = $resourceConnection;
        $this->_responseHelper = $responseHelper;
        $this->_orderCollectionFactory = $orderCollectionFactory;
        $this->_orderItemCollectionFactory = $orderItemCollectionFactory;
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

                    // get the data from the database
                    $orders = $this->getOrderData($startDate, $endDate);
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
     * Get the Order Data for the desired time frame for processing
     *
     * @param $startDate
     * @param $endDate
     * @return string
     */
    private function getOrderData($startDate, $endDate)
    {
        // variables
        $returnValue = '';
        $ordersArray = array();

        // get the list of orders with shipping address info
        $orders = $this->_orderCollectionFactory->create();
        $orders->addFieldToFilter("created_at", ["gteq" => $startDate]);
        $orders->addFieldToFilter("created_at", ["lt" => $endDate]);

        $orderAddressAlias = 'soa';
        $joinOrderAddress = $orders->getTable('sales_order_address');

        $orders->getSelect()->joinInner(
            [$orderAddressAlias => $joinOrderAddress],
            "(main_table.entity_id = {$orderAddressAlias}.parent_id AND {$orderAddressAlias}.address_type = 'shipping')",
            [
                $orderAddressAlias . '.street',
                $orderAddressAlias . '.city',
                $orderAddressAlias . '.region',
                $orderAddressAlias . '.postcode',
                $orderAddressAlias . '.telephone'
            ]
        );

        // check if there are orders to process
        if ($orders->count() > 0)
        {
            // get tomorrows date
            $tomorrow = date('Y-m-d', strtotime("tomorrow"));

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
                    $ordersArray[] = array(
                        'OrderNumber' => $order->getId(),
                        'DatePlaced' => $order->getData('created_at'),
                        'SAPDeliveryDate' => $tomorrow,
                        'CustomerName' => $order->getData('customer_firstname') . ' ' . $order->getData('customer_lastname'),
                        'CustomerShippingAddressStreet' => $order->getData('street'),
                        'CustomerShippingAddressCity' => $order->getData('city'),
                        'CustomerShippingAddressState' => $order->getData('region'),
                        'CustomerShippingAddressZip' => $order->getData('postcode'),
                        'SMGSKU' => $orderItem->getSku(),
                        'WebSKU' => $orderItem->getSku(),
                        'Quantity' => $orderItem->getQtyOrdered(),
                        'Unit' => 'EA',
                        'UnitPrice' => $orderItem->getPrice(),
                        'GrossSales' => $order->getData('grand_total'),
                        'ShippingAmount' => $order->getData('shipping_amount'),
                        'ExemptAmount' => '0',
                        'DiscountAmount' => $order->getData('base_discount_amount'),
                        'Subtotal' => $order->getData('subtotal'),
                        'TaxRate' => $orderItem->getTaxPercent(),
                        'SalesTax' => $order->getData('tax_amount'),
                        'InvoiceAmount' => $order->getData('total_invoiced'),
                        'DeliveryLocation' => '',
                        'CustomerEmail' => $order->getData('customer_email'),
                        'CustomerPhone' => $order->getData('telephone'),
                        'DeliveryWindow' => '',
                        'ShippingCondition' => '',
                        'WebsiteURL' => '',
                        'CreditAmount' => '',
                        'CR/DR/RE/Flag' => '',
                        'ReferenceDocNum' => '',
                        'CreditComment' => '',
                        'OrderReason' => '',
                        'DiscCondCode' => '',
                        'SurchCondCode' => '',
                        'DiscFixedAmt' => '',
                        'SurchFixedAmt' => '',
                        'DiscPercAmt' => '',
                        'SurchPercAmt' => ''
                    );
                }
            }

            // set the return
            $returnValue = $this->_responseHelper->createResponse(true, $ordersArray);
        }
        else
        {
            // log that there were no records found.
            $this->_logger->info("SMG\Api\Helper\OrdersHelper - No Orders were found for Begin Date: " . $startDate . " and End Date: " . $endDate);

            $returnValue = $this->_responseHelper->createResponse(true, 'No Orders where found for Begin Date: ' . $startDate . " and End Date: " . $endDate);
        }

        // return
        return $returnValue;
    }
}