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
     * @param $startDate
     * @param $endDate
     * @return string
     */
    public function getOrders($startDate, $endDate)
    {
        // initialize the return value
        $orders = '{}';

        // check if the dates are provided
        if ($startDate && $endDate)
        {
            // get the data from the database
            $results = $this->getOrderData($startDate, $endDate);

            // if there are results then loop through them and create the file
            if ($results)
            {
                $orders = $this->getOrdersJson($results);
            }
            else
            {
                // log that there were no records found.
                $this->_logger->info("SMG\Api\Helper\OrdersHelper - No Orders were found for Begin Date: " . $startDate . " and End Date: " . $endDate);
            }
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
        $query = $query . "    '' as 'shipping_condition',";
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

        // return the results
        return $results;
    }

    /**
     * Create the JSON file as a string
     *
     * @param $results
     * @return string
     */
    private function getOrdersJson($results)
    {
        // variables
        $response = array();
        $orders = array();

        // loop through each row of data creating the csv file
        foreach ($results as $result)
        {
            $orders[] = array(
                'order_number' => $result['order_number'],
                'date_placed' => $result['date_placed'],
                'sap_delivery_date' => $result['sap_delivery_date'],
                'customer_name' => $result['customer_name'],
                'customer_shipping_address_street' => $result['customer_shipping_address_street'],
                'customer_shipping_address_city' => $result['customer_shipping_address_city'],
                'customer_shipping_address_state' => $result['customer_shipping_address_state'],
                'customer_shipping_address_zip' => $result['customer_shipping_address_zip'],
                'smg_sku_1' => $result['smg_sku_1'],
                'web_sku_1' => $result['web_sku_1'],
                'quantity_1' => $result['quantity_1'],
                'unit_1' => $result['unit_1'],
                'unit_price_1' => $result['unit_price_1'],
                'smg_sku_2' => $result['smg_sku_2'],
                'web_sku_2' => $result['web_sku_2'],
                'quantity_2' => $result['quantity_2'],
                'unit_2' => $result['unit_2'],
                'unit_price_2' => $result['unit_price_2'],
                'gross_sales' => $result['gross_sales'],
                'shipping_amount' => $result['shipping_amount'],
                'shipping_condition' => $result['shipping_condition'],
                'exempt_amount' => $result['exempt_amount'],
                'discount_amount' => $result['discount_amount'],
                'subtotal' => $result['subtotal'],
                'tax_rate' => $result['tax_rate'],
                'sales_tax_no_shipping' => $result['sales_tax_no_shipping'],
                'sales_tax_on_shipping' => $result['sales_tax_on_shipping'],
                'invoice_amount' => $result['invoice_amount'],
                'delivery_location' => $result['delivery_location'],
                'customer_email' => $result['customer_email'],
                'customer_phone' => $result['customer_phone'],
                'delivery_window' => $result['delivery_window']
            );
        }

        $response['orders'] = $orders;

        // return
        return json_encode($response);
    }
}