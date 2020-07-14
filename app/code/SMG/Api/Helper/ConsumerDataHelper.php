<?php

namespace SMG\Api\Helper;

use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\ResourceModel\Order as OrderResource;
use Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory as OrderItemCollectionFactory;
use Psr\Log\LoggerInterface;
use SMG\OfflineShipping\Model\ShippingConditionCodeFactory;
use SMG\OfflineShipping\Model\ResourceModel\ShippingConditionCode as ShippingConditionCodeResource;
use SMG\Sap\Model\ResourceModel\SapOrderBatch\CollectionFactory as SapOrderBatchCollectionFactory;

class ConsumerDataHelper
{
    // Output JSON file constants
    const TIMESTAMP = 'timestamp';
    const ORDER_ID = 'order_id';
    const ORDER_STATUS = 'order_status';
    const CUSTOMER_EMAIL = 'customer_email';
    const NEWSLETTER_OPT = 'newsletter_opt';
    const STORE_TITLE = 'store_title';
    const CATEGORY = 'category';
    const SKU = 'sku';
    const DESCRIPTION = 'description';
    const QUANTITY = 'quantity';
    const UNIT_PRICE = 'unit_price';
    const EXTENDED_PRICE = 'extended_price';
    const SHIPPING = 'shipping';
    const TAX = 'tax';
    const SUBTOTAL = 'subtotal';
    const DISCOUNTS = 'discounts';
    const GRAND_TOTAL = 'grand_total';
    const SHIPPING_METHOD = 'shipping_method';
    const BILLING_FIRSTNAME = 'billing_firstname';
    const BILLING_LASTNAME = 'billing_lastname';
    const BILLING_ADDRESS = 'billing_address';
    const BILLING_CITY = 'billing_city';
    const BILLING_STATE = 'billing_state';
    const BILLING_POSTCODE = 'billing_postcode';
    const BILLING_COUNTRY = 'billing_country';
    const SHIPPING_FIRSTNAME = 'shipping_firstname';
    const SHIPPING_LASTNAME = 'shipping_lastname';
    const SHIPPING_ADDRESS = 'shipping_address';
    const SHIPPING_CITY = 'shipping_city';
    const SHIPPING_STATE = 'shipping_state';
    const SHIPPING_POSTCODE = 'shipping_postcode';
    const SHIPPING_COUNTRY = 'shipping_country';

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
     * @var OrderFactory
     */
    protected $_orderFactory;

    /**
     * @var OrderResource
     */
    protected $_orderResource;

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
     * OrdersHelper constructor.
     *
     * @param LoggerInterface $logger
     * @param ResponseHelper $responseHelper
     * @param SapOrderBatchCollectionFactory $sapOrderBatchCollectionFactory
     * @param OrderFactory $orderFactory
     * @param OrderResource $orderResource
     * @param OrderItemCollectionFactory $orderItemCollectionFactory
     * @param ShippingConditionCodeFactory $shippingConditionCodeFactory
     * @param ShippingConditionCodeResource $shippingConditionCodeResource
     */
    public function __construct(LoggerInterface $logger,
        ResponseHelper $responseHelper,
        SapOrderBatchCollectionFactory $sapOrderBatchCollectionFactory,
        OrderFactory $orderFactory,
        OrderResource $orderResource,
        OrderItemCollectionFactory $orderItemCollectionFactory,
        ShippingConditionCodeFactory $shippingConditionCodeFactory,
        ShippingConditionCodeResource $shippingConditionCodeResource)
    {
        $this->_logger = $logger;
        $this->_responseHelper = $responseHelper;
        $this->_sapOrderBatchCollectionFactory = $sapOrderBatchCollectionFactory;
        $this->_orderFactory = $orderFactory;
        $this->_orderResource = $orderResource;
        $this->_orderItemCollectionFactory = $orderItemCollectionFactory;
        $this->_shippingConditionCodeFactory = $shippingConditionCodeFactory;
        $this->_shippingConditionCodeResource = $shippingConditionCodeResource;
    }

    /**
     * Get the sales orders in the desired format
     *
     * @return string
     */
    public function getConsumerData()
    {
        // get the order data
        $debitArray = $this->getDebitOrderData();

        // determine if there is anything there to send
        if (empty($debitArray))
        {
            // log that there were no records found.
            $this->_logger->info("SMG\Api\Helper\ConsumerData - No Orders were found for processing.");

            $orders = $this->_responseHelper->createResponse(true, 'No Orders where found for processing.');
        }
        else
        {
            $orders = $this->_responseHelper->createResponse(true, $debitArray);
        }

        // return
        return $orders;
    }

    /**
     * Get the debit orders
     *
     * @return array
     */
    private function getDebitOrderData()
    {
        $ordersArray = array();

        // get the orders that are ready to be sent to SAP
        $sapOrderBatches = $this->_sapOrderBatchCollectionFactory->create();
        $sapOrderBatches->addFieldToFilter('is_consumer_data', ['eq' => true]);
        $sapOrderBatches->addFieldToFilter('consumer_data_date', ['null' => true]);

        // check if there are orders to process
        if ($sapOrderBatches->count() > 0)
        {
            /**
             * @var \SMG\Sap\Model\SapOrderBatch $sapOrderBatch
             */
            foreach ($sapOrderBatches as $sapOrderBatch)
            {
                // get the required fields needed for processing
                $orderId = $sapOrderBatch->getData('order_id');

                // Get the sales order
                /**
                 * @var \Magento\Sales\Model\Order $order
                 */
                $order = $this->_orderFactory->create();
                $this->_orderResource->load($order, $orderId);

                // get the list of items for this order
                $orderItems = $this->_orderItemCollectionFactory->create();
                $orderItems->addFieldToFilter("order_id", ['eq' => $order->getId()]);
                $orderItems->addFieldToFilter("product_type", ['neq' => 'bundle']);
                $orderItems->addFieldToFilter("product_type", ['neq' => 'configurable']);

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
     * @param \Magento\Sales\Model\Order $order
     * @param \Magento\Sales\Model\Order\Item $orderItem
     * @return array
     */
    private function addRecordToOrdersArray($order, $orderItem)
    {
        // get the name of the site
        // if the site is Default Store View then change it to reflect scotts program
        $siteName = $order->getStore()->getName();
        if ($siteName == 'Default Store View')
        {
            $siteName = 'Scotts Program Store';
        }

        // get the shipping condition data
        /**
         * @var /SMG/OfflineShipping/Model/ShippingConditionCode $shippingCondition
         */
        $shippingCondition = $this->_shippingConditionCodeFactory->create();
        $this->_shippingConditionCodeResource->load($shippingCondition, $order->getShippingMethod(), 'shipping_method');

        // get the shipping address to be used for the customer first and last name
        /**
         * @var \Magento\Sales\Model\Order\Address $shippingAddress
         */
        $shippingAddress = $order->getShippingAddress();
        $customerFirstName = $shippingAddress->getFirstname();
        $customerLastName = $shippingAddress->getLastname();
        if (empty($customerFirstName) && empty($customerLastName))
        {
            $customerFirstName = $order->getData('customer_firstname');
            $customerLastName = $order->getData('customer_lastname');
        }

        // return
        return array_map('trim', array(
            self::TIMESTAMP => $order->getData('created_at'),
            self::ORDER_ID => $order->getData('increment_id'),
            self::ORDER_STATUS => $order->getData('status'),
            self::CUSTOMER_EMAIL => $order->getData('customer_email'),
            self::NEWSLETTER_OPT => '',
            self::STORE_TITLE => $siteName,
            self::CATEGORY => $siteName,
            self::SKU => $orderItem->getData('sku'),
            self::DESCRIPTION => $orderItem->getData('name'),
            self::QUANTITY => $orderItem->getData('qty_ordered'),
            self::UNIT_PRICE => $orderItem->getData('price'),
            self::EXTENDED_PRICE => $orderItem->getData('price'),
            self::SHIPPING => $order->getData('shipping_amount'),
            self::TAX => $order->getData('tax_amount'),
            self::SUBTOTAL => $order->getData('subtotal'),
            self::DISCOUNTS => $order->getData("discount_amount"),
            self::GRAND_TOTAL => $order->getData('grand_total'),
            self::SHIPPING_METHOD => $shippingCondition->getData('sap_shipping_method'),
            self::BILLING_FIRSTNAME => '',
            self::BILLING_LASTNAME => '',
            self::BILLING_ADDRESS => '',
            self::BILLING_CITY => '',
            self::BILLING_STATE => '',
            self::BILLING_POSTCODE => '',
            self::BILLING_COUNTRY => '',
            self::SHIPPING_FIRSTNAME => $customerFirstName,
            self::SHIPPING_LASTNAME => $customerLastName,
            self::SHIPPING_ADDRESS => $shippingAddress->getStreetLine(1),
            self::SHIPPING_CITY => $shippingAddress->getCity(),
            self::SHIPPING_STATE => $shippingAddress->getRegion(),
            self::SHIPPING_POSTCODE => $shippingAddress->getPostcode(),
            self::SHIPPING_COUNTRY => $shippingAddress->getCountryId()
        ));
    }
}
