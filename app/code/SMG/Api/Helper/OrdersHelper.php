<?php

namespace SMG\Api\Helper;

use \Magento\Framework\App\ResourceConnection;
use \Psr\Log\LoggerInterface;

class OrdersHelper
{
    // Variables
    protected $_logger;
    protected $_resourceConnection;

    /**
     * OrdersHelper constructor.
     *
     * @param LoggerInterface $logger
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(LoggerInterface $logger, ResourceConnection $resourceConnection)
    {
        $this->_logger = $logger;
        $this->_resourceConnection = $resourceConnection;
    }

    /**
     * Get the sales orders in the desired format
     *
     * 1 - Csv
     * 2 - Xml
     * 3 - Json
     *
     * @param $startDate
     * @param $endDate
     * @param $type
     * @return string
     */
    public function getOrders($startDate, $endDate, $type)
    {
        // initialize the return value
        $orders = '';

        // get the data from the database
        $results = $this->getOrderData($startDate, $endDate);

        // if there are results then loop through them and create the file
        if ($results)
        {
            // determine which type
            switch ($type)
            {
                // Get Orders as CSV
                case 1:
                    $orders = $this->getOrdersCsv($results);
                    break;
                // Get Orders as XML
                case 2:
                    $orders = $this->getOrdersXml($results);
                    break;
                // Get Orders as JSON
                case 3:
                    $orders = $this->getOrdersJson($results);
                    break;
                default:
                    break;
            }
        }
        else
        {
            // log that there were no records found.
            $this->_logger->info("SMG\Api\Helper\OrdersHelper - No Orders were found for Begin Date: " . $startDate . " and End Date: " . $endDate);
        }

        // return
        return $orders;
    }

    /**
     * Create the CSV file as a string
     *
     * @param $results
     * @return string
     */
    private function getOrdersCsv($results)
    {
        // initialize the return value
        $orders = '';

        if ($results)
        {
            // add the header
            $orders = $this->getHeaders();

            // loop through each row of data creating the csv file
            foreach ($results as $result)
            {
                $orders = $orders . $result['order_number'] . ',';
                $orders = $orders . $result['date_placed'] . ',';
                $orders = $orders . $result['sap_delivery_date'] . ',';
                $orders = $orders . $result['customer_name'] . ',';
                $orders = $orders . $result['customer_shipping_address_street'] . ',';
                $orders = $orders . $result['customer_shipping_address_city'] . ',';
                $orders = $orders . $result['customer_shipping_address_state'] . ',';
                $orders = $orders . $result['customer_shipping_address_zip'] . ',';
                $orders = $orders . $result['smg_sku_1'] . ',';
                $orders = $orders . $result['web_sku_1'] . ',';
                $orders = $orders . $result['quantity_1'] . ',';
                $orders = $orders . $result['unit_1'] . ',';
                $orders = $orders . $result['unit_price_1'] . ',';
                $orders = $orders . $result['smg_sku_2'] . ',';
                $orders = $orders . $result['web_sku_2'] . ',';
                $orders = $orders . $result['quantity_2'] . ',';
                $orders = $orders . $result['unit_2'] . ',';
                $orders = $orders . $result['unit_price_2'] . ',';
                $orders = $orders . $result['gross_sales'] . ',';
                $orders = $orders . $result['shipping_amount'] . ',';
                $orders = $orders . $result['exempt_amount'] . ',';
                $orders = $orders . $result['discount_amount'] . ',';
                $orders = $orders . $result['subtotal'] . ',';
                $orders = $orders . $result['tax_rate'] . ',';
                $orders = $orders . $result['sales_tax_no_shipping'] . ',';
                $orders = $orders . $result['sales_tax_on_shipping'] . ',';
                $orders = $orders . $result['invoice_amount'] . ',';
                $orders = $orders . $result['delivery_location'] . ',';
                $orders = $orders . $result['customer_email'] . ',';
                $orders = $orders . $result['customer_phone'] . ',';
                $orders = $orders . $result['delivery_window'] . '\n';
            }
        }

        // return
        return $orders;
    }

    /**
     * Create the XML file as a string
     *
     * @param $results
     * @return string
     */
    private function getOrdersXml($results)
    {
        // initialize the return value
        $orders = '';

        // TODO: This is a stub to create the orders as XML

        // return
        return $orders;
    }

    /**
     * Create the JSON file as a string
     *
     * @param $results
     * @return string
     */
    private function getOrdersJson($results)
    {
        // initialize the return value
        $orders = '';

        // TODO: This is a stub to create the orders as JSON

        // return
        return $orders;
    }

    /**
     * This function gets the desired sales order data from the database
     * based on the startdate and enddate
     *
     * @param $startDate
     * @param $endDate
     * @return array|null
     */
    private function getOrderData($startDate, $endDate)
    {
        // default the results
        $results = null;

        // check if the dates are provided
        if ($startDate && $endDate)
        {
            // get a connection
            $connection = $this->_resourceConnection->getConnection(ResourceConnection::DEFAULT_CONNECTION);

            // create the query
            $query = "SELECT distinct so.entity_id as 'order_number',";
            $query = $query . "    so.created_at as 'date_placed',";
            $query = $query . "    '' as 'sap_delivery_date',";
            $query = $query . "    CONCAT(so.customer_firstname, ' ', so.customer_lastname) as 'customer_name',";
            $query = $query . "    soa.street as 'customer_shipping_address_street',";
            $query = $query . "    soa.city as 'customer_shipping_address_city',";
            $query = $query . "    soa.region as 'customer_shipping_address_state',";
            $query = $query . "    soa.postcode as 'customer_shipping_address_zip',";
            $query = $query . "    soi.sku as 'smg_sku_1',";
            $query = $query . "    soi.sku as 'web_sku_1',";
            $query = $query . "    soi.qty_ordered as 'quantity_1',";
            $query = $query . "    soi.product_id as 'unit_1',";
            $query = $query . "    soi.price as 'unit_price_1',";
            $query = $query . "    '' as 'smg_sku_2',";
            $query = $query . "    '' as 'web_sku_2',";
            $query = $query . "    '' as 'quantity_2',";
            $query = $query . "    '' as 'unit_2',";
            $query = $query . "    '' as 'unit_price_2',";
            $query = $query . "    so.grand_total as 'gross_sales',";
            $query = $query . "    so.shipping_amount as 'shipping_amount',";
            $query = $query . "    '0' as 'exempt_amount',";
            $query = $query . "    so.base_discount_amount as 'discount_amount',";
            $query = $query . "    so.subtotal as 'subtotal',";
            $query = $query . "    soi.tax_percent as 'tax_rate',";
            $query = $query . "    so.tax_amount as 'sales_tax_no_shipping',";
            $query = $query . "    so.shipping_tax_amount as 'sales_tax_on_shipping',";
            $query = $query . "    so.total_invoiced as 'invoice_amount',";
            $query = $query . "    so.shipping_description as 'delivery_location',";
            $query = $query . "    so.customer_email as 'customer_email',";
            $query = $query . "    soa.telephone as 'customer_phone',";
            $query = $query . "    '' as 'delivery_window' ";
            $query = $query . "from sales_order so";
            $query = $query . "    inner join sales_order_item soi on soi.order_id = so.entity_id";
            $query = $query . "    inner join sales_order_address soa on soa.parent_id = soi.order_id ";
            $query = $query . "where soa.address_type = 'shipping'";
            $query = $query . " and soi.product_type <> 'bundle'";
            $query = $query . " and so.created_at between '" . $startDate . "' and '" . $endDate . "' ";
            $query = $query . "order by so.entity_id";

            // execute the query
            $results = $connection->fetchAll($query);
        }
        elseif (!$startDate)
        {
            // log the error
            $this->_logger->error("SMG\Api\Helper\OrdersHelper - The Start Date was not provided.");
        }
        else
        {
            // log the error
            $this->_logger->error("SMG\Api\Helper\OrdersHelper - The End Date was not provided.");
        }

        // return the results
        return $results;
    }

    /**
     * Get the headers for the desired CSV file
     *
     * @return string
     */
    private function getHeaders()
    {
        $headers = 'Order Number';
        $headers = $headers . 'Date Placed,';
        $headers = $headers . 'SAP Delivery Date,';
        $headers = $headers . 'Customer Name,';
        $headers = $headers . 'Customer Shipping Address Street,';
        $headers = $headers . 'Customer Shipping Address City,';
        $headers = $headers . 'Customer Shipping Address State,';
        $headers = $headers . 'Customer Shipping Address Zip,';
        $headers = $headers . 'SMG SKU 1,';
        $headers = $headers . 'Web SKU 1,';
        $headers = $headers . 'Quantity 1,';
        $headers = $headers . 'Unit 1,';
        $headers = $headers . 'Unit Price 1,';
        $headers = $headers . 'SMG SKU 2,';
        $headers = $headers . 'Web SKU 2,';
        $headers = $headers . 'Quantity 2,';
        $headers = $headers . 'Unit 2,';
        $headers = $headers . 'Unit Price 2,';
        $headers = $headers . 'Gross Sales,';
        $headers = $headers . 'Shipping Amount,';
        $headers = $headers . 'Exempt Amount,';
        $headers = $headers . 'Discount Amount,';
        $headers = $headers . 'Subtotal,';
        $headers = $headers . 'Tax Rate,';
        $headers = $headers . 'Sales Tax No Shipping,';
        $headers = $headers . 'Sales Tax On Shipping,';
        $headers = $headers . 'Invoice Amount,';
        $headers = $headers . 'Delivery Location,';
        $headers = $headers . 'Customer Email,';
        $headers = $headers . 'Customer Phone,';
        $headers = $headers . 'Delivery Window\n';

        // return
        return $headers;
    }
}