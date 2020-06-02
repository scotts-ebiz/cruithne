<?php

namespace SMG\Api\Helper;

use ZaiusSDK\ZaiusException;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\ResourceModel\Order as OrderResource;
use Psr\Log\LoggerInterface;
use SMG\Sap\Model\SapOrderFactory;
use SMG\Sap\Model\SapOrderBatchFactory;
use SMG\Sap\Model\SapOrderItemFactory;
use SMG\Sap\Model\SapOrderShipmentFactory;
use SMG\Sap\Model\ResourceModel\SapOrder as SapOrderResource;
use SMG\Sap\Model\ResourceModel\SapOrderBatch as SapOrderBatchResource;
use SMG\Sap\Model\ResourceModel\SapOrderShipment as SapOrderShipmentResource;
use SMG\Sap\Model\ResourceModel\SapOrderItem as SapOrderItemResource;
use SMG\Sap\Model\ResourceModel\SapOrderItem\CollectionFactory as SapOrderItemCollectionFactory;
use SMG\Sap\Model\ResourceModel\SapOrderShipment\CollectionFactory as SapOrderShipmentCollectionFactory;
use SMG\OrderDiscount\Helper\Data as DiscountHelper;
use Magento\Sales\Model\Service\InvoiceService as InvoiceService;
use Magento\Framework\DB\Transaction as Transaction;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender as InvoiceSender;

class OrderStatusHelper
{
    // Input JSON File Constants
    const INPUT_SAP_ORDER_NUMBER = 'SAPOrderNumber';
    const INPUT_SAP_MAGENTO_PO = 'MagentoPO';
    const INPUT_SAP_ORDER_CREATE_DATE = 'OrderCreat';
    const INPUT_SAP_SAP_ORDER_STATUS = 'OrderStatus';
    const INPUT_SAP_FULFILLMENT_LOCATION = 'FulfillmentLocation';
    const INPUT_SAP_SKU = 'SKU';
    const INPUT_SAP_SKU_DESCRIPTION = 'SKUDescription';
    const INPUT_SAP_ORDER_QTY = 'OrderQTY';
    const INPUT_SAP_CONFIRMED_QTY = 'ConfirmedQTY';
    const INPUT_SAP_SHIP_TRACKING_NUMBER = 'ShipTrackingNumber';
    const INPUT_SAP_SAP_BILLING_DOC_NUMBER = 'InvoiceNumber';
    const INPUT_SAP_SAP_BILLING_DOC_DATE = 'InvoiceDate';
    const INPUT_SAP_PAYER_ID = 'PayerId';
    const INPUT_SAP_DELIVERY_NUMBER = 'DeliveryNumber';
    const ERROR_CODE_LOCK_WAIT = 1205;
    const ERROR_CODE_DEAD_LOCK = 1213;

    /**
     * @var LoggerInterface
     */
    protected $_logger;

    /**
     * @var SapOrderFactory
     */
    protected $_sapOrderFactory;

    /**
     * @var SapOrderBatchFactory
     */
    protected $_sapOrderBatchFactory;

    /**
     * @var SapOrderItemFactory
     */
    protected $_sapOrderItemFactory;

    /**
     * @var SapOrderResource
     */
    protected $_sapOrderResource;

    /**
     * @var SapOrderBatchResource
     */
    protected $_sapOrderBatchResource;

    /**
     * @var SapOrderItemResource
     */
    protected $_sapOrderItemResource;

    /**
     * @var SapOrderItemCollectionFactory
     */
    protected $_sapOrderItemCollectionFactory;

    /**
     * @var ResponseHelper
     */
    protected $_responseHelper;

    /**
     * @var SapOrderShipmentFactory
     */
    protected $_sapOrderShipmentFactory;

    /**
     * @var SapOrderShipmentResource
     */
    protected $_sapOrderShipmentResource;

    /**
     * @var SapOrderShipmentCollectionFactory
     */
    protected $_sapOrderShipmentCollectionFactory;

    /**
     * @var OrderFactory
     */
    protected $_orderFactory;

    /**
     * @var OrderResource
     */
    protected $_orderResource;
   /**
     * @var DiscountHelper
     */
    protected $_discountHelper;

    /**
     * @var InvoiceService
     */
    protected $_invoiceService;
    /**
     * @var Transaction
     */
    protected $_transaction;
    /**
     * @var InvoiceSender
     */
    protected $_invoiceSender;

    /**
     * OrderStatusHelper constructor.
     *
     * @param LoggerInterface $logger
     * @param SapOrderFactory $sapOrderFactory
     * @param SapOrderBatchFactory $sapOrderBatchFactory
     * @param SapOrderItemFactory $sapOrderItemFactory
     * @param SapOrderResource $sapOrderResource
     * @param SapOrderBatchResource $sapOrderBatchResource
     * @param SapOrderItemResource $sapOrderItemResource
     * @param SapOrderItemCollectionFactory $sapOrderItemCollectionFactory
     * @param SapOrderShipmentFactory $sapOrderShipmentFactory
     * @param SapOrderShipmentResource $sapOrderShipmentResource
     * @param SapOrderShipmentCollectionFactory $sapOrderShipmentCollectionFactory
     * @param ResponseHelper $responseHelper
     * @param OrderFactory $orderFactory
     * @param OrderResource $orderResource
     */
    public function __construct(
        LoggerInterface $logger,
        SapOrderFactory $sapOrderFactory,
        SapOrderBatchFactory $sapOrderBatchFactory,
        SapOrderItemFactory $sapOrderItemFactory,
        SapOrderResource $sapOrderResource,
        SapOrderBatchResource $sapOrderBatchResource,
        SapOrderItemResource $sapOrderItemResource,
        SapOrderItemCollectionFactory $sapOrderItemCollectionFactory,
        SapOrderShipmentFactory $sapOrderShipmentFactory,
        SapOrderShipmentResource $sapOrderShipmentResource,
        SapOrderShipmentCollectionFactory $sapOrderShipmentCollectionFactory,
        ResponseHelper $responseHelper,
        OrderFactory $orderFactory,
        OrderResource $orderResource,
        DiscountHelper $discountHelper,
        InvoiceService $invoiceService,
        Transaction $transaction,
        InvoiceSender $invoiceSender)
    {
        $this->_logger = $logger;
        $this->_sapOrderFactory = $sapOrderFactory;
        $this->_sapOrderBatchFactory = $sapOrderBatchFactory;
        $this->_sapOrderItemFactory = $sapOrderItemFactory;
        $this->_sapOrderResource = $sapOrderResource;
        $this->_sapOrderBatchResource = $sapOrderBatchResource;
        $this->_sapOrderItemResource = $sapOrderItemResource;
        $this->_sapOrderItemCollectionFactory = $sapOrderItemCollectionFactory;
        $this->_responseHelper = $responseHelper;
        $this->_sapOrderShipmentFactory = $sapOrderShipmentFactory;
        $this->_sapOrderShipmentResource = $sapOrderShipmentResource;
        $this->_sapOrderShipmentCollectionFactory = $sapOrderShipmentCollectionFactory;
        $this->_orderFactory = $orderFactory;
        $this->_orderResource = $orderResource;
        $this->_discountHelper = $discountHelper;
        $this->_invoiceService = $invoiceService;
        $this->_transaction = $transaction;
        $this->_invoiceSender = $invoiceSender;
    }

    /**
     * Handles the order status request
     *
     * @param $requestData
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    public function setOrderStatus($requestData)
    {
        // variables
        $orderStatusResponse = $this->_responseHelper->createResponse(true, "The order status process completed successfully.");

        // make sure that we were given something from the request
        if (!empty($requestData))
        {
            // loop through the orders that were sent via the JSON file
            foreach ($requestData as $inputOrder)
            {
                try
                {
                    // check to see if there is an order increment number
                    $orderIncrementId = $inputOrder[self::INPUT_SAP_MAGENTO_PO];
                    if ($orderIncrementId)
                    {
                        // create and load the sapOrder
                        /**
                         * @var \SMG\Sap\Model\SapOrder $sapOrder
                         */
                        $sapOrder = $this->_sapOrderResource->getSapOrderByIncrementId($orderIncrementId);

                        // Grab all siblings of this order item inclusively.
                        $sapOrderItems = array_filter($requestData, function ($orderItem) use ($inputOrder) {
                            return $inputOrder[self::INPUT_SAP_MAGENTO_PO] === $orderItem[self::INPUT_SAP_MAGENTO_PO];
                        });

                        // Grab all the unique skus for this order.
                        $sapDistinctSkus = [];
                        foreach($sapOrderItems as $sapOrderItem) {
                            if (array_search($sapOrderItem[self::INPUT_SAP_SKU], array_column($sapDistinctSkus,self::INPUT_SAP_SKU)) === FALSE) {
                                $sapDistinctSkus[] = $sapOrderItem;
                            }
                        }

                        // Sum up all the confirmed (shipped) items for this order.
                        $totalConfirmedQuantity = array_reduce($sapOrderItems, function ($total, $item) {
                            if (!empty($item[self::INPUT_SAP_CONFIRMED_QTY])) {
                                return $total + floatval($item[self::INPUT_SAP_CONFIRMED_QTY]);
                            }
                        });

                        // Sum up every unique sku to get the total number of items ordered.
                        $totalOrderedQuantity = array_reduce($sapDistinctSkus, function ($total, $item) {
                            if (!empty($item[self::INPUT_SAP_ORDER_QTY])) {
                                return $total + floatval($item[self::INPUT_SAP_ORDER_QTY]);
                            }
                        });

                        // process the sap order info
                        $this->processOrderSapInfo($inputOrder, $sapOrder, $totalConfirmedQuantity, $totalOrderedQuantity);

                        // update the batch processing for those
                        // that need to be processed through batch capture
                        $this->processOrderSapBatchInfo($inputOrder, $sapOrder);
                    }
                    else
                    {
                        // log the error
                        $this->_logger->error("SMG\Api\Helper\OrderStatusHelper - Missing magento po number.");
                    }
                }
                catch (\Exception $e)
                {
                    $errorMsg = "An error has occurred for order status - " . $e->getMessage();
                    $this->_logger->error($errorMsg);
                }
            }
        }
        else
        {
            // log the error
            $this->_logger->error("SMG\Api\Helper\OrderStatusHelper - Nothing was provided to process.");

            $orderStatusResponse = $this->_responseHelper->createResponse(false, 'Nothing was provided to process.');
        }

        // return
        return $orderStatusResponse;
    }

    /**
     * Takes the request data and inserts/updates the appropriate SAP tables
     *
     * @param $inputOrder
     * @param \SMG\Sap\Model\SapOrder $sapOrder
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    private function processOrderSapInfo($inputOrder, $sapOrder, $totalConfirmedQuantity, $totalOrderedQuantity)
    {
        // get the orderId to see if it is present in the object
        $orderSapId = $sapOrder->getId();

        // determine if the sapOrder needs to be created
        // if it was loaded from the database there should be an
        // order id value
        if (!empty($orderSapId))
        {
            // check to see if the order needs to be updated
            // if so then update the order
            $this->updateOrderSap($inputOrder, $sapOrder, $totalConfirmedQuantity, $totalOrderedQuantity);

            // process the sap order item information
            $this->processOrderSapItemInfo($inputOrder, $sapOrder, $totalConfirmedQuantity, $totalOrderedQuantity);

            // process the sap order shipment information
            $this->processOrderSapShipmentInfo($inputOrder, $sapOrder);
        }
        else
        {
            // create the order sap record
            $orderSapId = $this->insertOrderSap($inputOrder, $sapOrder, $totalConfirmedQuantity, $totalOrderedQuantity);

            // create the order sap item record
            $orderSapItemId = $this->insertOrderSapItem($inputOrder, $orderSapId, $totalConfirmedQuantity, $totalOrderedQuantity);

            // create the order sap shipment record
            $this->insertOrderSapShipment($inputOrder, $orderSapItemId);
        }
    }

    /**
     * Takes the request data and inserts/updates the appropriate SAP
     * Item Tables
     *
     * @param $inputOrder
     * @param $sapOrder
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    private function processOrderSapItemInfo($inputOrder, $sapOrder, $totalConfirmedQuantity, $totalOrderedQuantity)
    {
        // get the order sap id
        $orderSapId = $sapOrder->getId();

        // create the sap orders item factory to retrieve all
        // the items with the desired order
        $sapOrderItems = $this->_sapOrderItemCollectionFactory->create();
        $sapOrderItems->addFieldToFilter('order_sap_id', ['eq' => $orderSapId]);
        $sapOrderItems->addFieldToFilter('sku', ['eq' => $inputOrder[self::INPUT_SAP_SKU]]);

        // check to see if there is a record already
        // if there is then update the appropriate tables
        // otherwise create new values in the tables
        if ($sapOrderItems->count() > 0)
        {
            // loop through the orders
            foreach($sapOrderItems as $sapOrderItem)
            {

                // check to see if the order needs to be updated
                // if so then update the order item
                $this->updateOrderSapItem($inputOrder, $sapOrderItem, $totalConfirmedQuantity, $totalOrderedQuantity);

            }
        }
        else
        {
            // create the order sap item record
            $this->insertOrderSapItem($inputOrder, $orderSapId, $totalConfirmedQuantity, $totalOrderedQuantity);
        }
    }

    /**
     * Takes the request data and inserts/updates the appropriate SAP
     * Item Tables
     *
     * @param $inputOrder
     * @param $sapOrder
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    private function processOrderSapShipmentInfo($inputOrder, $sapOrder)
    {
        // get the ship tracking number for later use
        $shipTrackingNumber = $inputOrder[self::INPUT_SAP_SHIP_TRACKING_NUMBER];

        // only add something if there is a ship tracking number
        // this is because this table is for when the item has been shipped
        if (!empty($shipTrackingNumber))
        {
            // create the sap orders item factory to retrieve all
            // the items with the desired order
            $sapOrderItems = $this->_sapOrderItemCollectionFactory->create();
            $sapOrderItems->addFieldToFilter('order_sap_id', ['eq' => $sapOrder->getId()]);
            $sapOrderItems->addFieldToFilter('sku', ['eq' => $inputOrder[self::INPUT_SAP_SKU]]);

            // loop through the order items to get the desired sap order item id
            // there should only be one
            foreach ($sapOrderItems as $sapOrderItem)
            {
                // get the order sap item id
                $orderSapItemId = $sapOrderItem->getId();
            }
            // create the sap orders shipment factory to retrieve all
            // the shipment items with the desired order
            $sapOrderShipments = $this->_sapOrderShipmentCollectionFactory->create();
            $sapOrderShipments->addFieldToFilter('order_sap_item_id', ['eq' => $orderSapItemId]);
            $sapOrderShipments->addFieldToFilter('ship_tracking_number', ['eq' => $shipTrackingNumber]);
            $sapOrderShipments->addFieldToFilter('qty', ['eq' => $inputOrder[self::INPUT_SAP_ORDER_QTY]]);

            // check to see if there is a record already
            // if there is then update the appropriate tables
            // otherwise create new values in the tables
            // there should only be one record
            if ($sapOrderShipments->count() > 0)
            {
                // loop through the orders
                foreach($sapOrderShipments as $sapOrderShipment)
                {
                    // check to see if the order needs to be updated
                    // if so then update the order item
                    $this->updateOrderSapShipment($inputOrder, $sapOrderShipment);
                }
            }
            else
            {
                // create the order sap shipment record
                $this->insertOrderSapShipment($inputOrder, $orderSapItemId);
            }
        }
    }

    /**
     * Insert the order sap table with the appropriate values
     *
     * @param $inputOrder
     * @return mixed
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    private function insertOrderSap($inputOrder, $sapOrder, $totalConfirmedQuantity, $totalOrderedQuantity)
    {
        // get the order for the desired increment id
        $order = $this->_orderFactory->create();
        $this->_orderResource->load($order, $inputOrder[self::INPUT_SAP_MAGENTO_PO], 'increment_id');

        // variables
        $sapOrderStatus = $inputOrder[self::INPUT_SAP_SAP_ORDER_STATUS];

        // get the order status for this order based on the
        // ship tracking number
        $orderStatus = $this->getOrderStatus($inputOrder, $totalConfirmedQuantity, $totalOrderedQuantity);

        // Add to the sales_order_sap table
        $sapOrder->setData('order_id', $order->getId());
        $sapOrder->setData('sap_order_id', $inputOrder[self::INPUT_SAP_ORDER_NUMBER]);
        $sapOrder->setData('order_created_at', $inputOrder[self::INPUT_SAP_ORDER_CREATE_DATE]);
        $sapOrder->setData('sap_order_status', $sapOrderStatus);
        $sapOrder->setData('order_status', $orderStatus);
        $sapOrder->setData('sap_payer_id', $inputOrder[self::INPUT_SAP_PAYER_ID]);

        // save the data to the table
        $this->_sapOrderResource->save($sapOrder);

        // get the entity id from the newly added sap order
        $orderSapId = $sapOrder->getId();

        // return the order sap id that was generated from
        // inserting into the table
        return $orderSapId;
    }

    /**
     * Update the order sap table with the appropriate values
     *
     * @param $inputOrder
     * @param $sapOrder
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    private function updateOrderSap($inputOrder, $sapOrder, $totalConfirmedQuantity, $totalOrderedQuantity)
    {
        // initialize update flag
        $isUpdateNeeded = false;

        // initialize the order status and notes
        $orderStatus = 'updated';
        $orderStatusNotes = '';

        // check sap order id
        $inputValue = $inputOrder[self::INPUT_SAP_ORDER_NUMBER];
        $sapOrderValue = $sapOrder->getData('sap_order_id');
        if ((!empty($inputValue) || !empty($sapOrderValue)) && $inputValue !== $sapOrderValue)
        {
            $isUpdateNeeded = true;
            $sapOrder->setData('sap_order_id', $inputValue);
        }

        // check order created at
        $inputValue = $inputOrder[self::INPUT_SAP_ORDER_CREATE_DATE];
        $sapOrderValue = $sapOrder->getData('order_created_at');
        if ((!empty($inputValue) || !empty($sapOrderValue)))
        {
            // update date
            $isUpdateDate = true;

            // if they both have a value then compare the dates
            // otherwise go ahead and update
            if (!empty($inputValue) && !empty($sapOrderValue))
            {
                // compare the dates if they are not equal then update
                $originalDate = date("y-m-d", strtotime($sapOrderValue));
                $newDate = date("y-m-d", strtotime($inputValue));
                if ($originalDate === $newDate)
                {
                    $isUpdateDate = false;
                }
            }

            // if the date is okay to update then update
            if ($isUpdateDate)
            {
                $isUpdateNeeded = true;
                $sapOrder->setData('order_created_at', $inputValue);
            }
        }

        // check sap order status
        $inputValue = $inputOrder[self::INPUT_SAP_SAP_ORDER_STATUS];
        $sapOrderValue = $sapOrder->getData('sap_order_status');
        if ((!empty($inputValue) || !empty($sapOrderValue)) && $inputValue !== $sapOrderValue)
        {
            $isUpdateNeeded = true;
            $sapOrder->setData('sap_order_status', $inputValue);
        }

        // check order status
        $inputValue = $this->getOrderStatus($inputOrder, $totalConfirmedQuantity, $totalOrderedQuantity);
        $sapOrderValue = $sapOrder->getData('order_status');
        if ((!empty($inputValue) || !empty($sapOrderValue)) && $inputValue !== $sapOrderValue)
        {
            $isUpdateNeeded = true;
            $sapOrder->setData('order_status', $inputValue);
        }

        // check if the payer id changed
        $inputValue = $inputOrder[self::INPUT_SAP_PAYER_ID];
        $sapOrderValue = $sapOrder->getData('sap_payer_id');
        if ((!empty($inputValue) || !empty($sapOrderValue)) && $inputValue !== $sapOrderValue)
        {
            $isUpdateNeeded = true;
            $sapOrder->setData('sap_payer_id', $inputValue);
        }

        // if there was something updated then update the table
        if ($isUpdateNeeded)
        {
            // update the table
            $this->_sapOrderResource->save($sapOrder);
        }
    }

    /**
     * Insert the order sap item table with the appropriate values
     *
     * @param $inputOrder
     * @param $orderSapId
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    private function insertOrderSapItem($inputOrder, $orderSapId, $totalConfirmedQuantity, $totalOrderedQuantity)
    {
        // variables
        $sapOrderStatus = $inputOrder[self::INPUT_SAP_SAP_ORDER_STATUS];
        $shipTrackingNumber = $inputOrder[self::INPUT_SAP_SHIP_TRACKING_NUMBER];

        // get the order status for this order based on the
        // ship tracking number
        $orderStatus = $this->getOrderStatus($inputOrder, $totalConfirmedQuantity, $totalOrderedQuantity);

        // add to the sales_order_sap_item table
        $sapOrderItem = $this->_sapOrderItemFactory->create();
        $sapOrderItem->setData('order_sap_id', $orderSapId);
        $sapOrderItem->setData('sap_order_status', $sapOrderStatus);
        $sapOrderItem->setData('order_status', $orderStatus);
        $sapOrderItem->setData('sku', $inputOrder[self::INPUT_SAP_SKU]);
        $sapOrderItem->setData('sku_description', $inputOrder[self::INPUT_SAP_SKU_DESCRIPTION]);
        $sapOrderItem->setData('qty', $inputOrder[self::INPUT_SAP_ORDER_QTY]);
        $sapOrderItem->setData('confirmed_qty', $inputOrder[self::INPUT_SAP_CONFIRMED_QTY]);

        // save the data to the table
        $this->_sapOrderItemResource->save($sapOrderItem);

        // get the entity id from the newly added sap order item
        $orderSapItemId = $sapOrderItem->getId();

        // return the order sap item id that was generated from
        // inserting into the table
        return $orderSapItemId;
    }

    /**
     * Insert the order sap shipment table with the appropriate values
     *
     * @param $inputOrder
     * @param $orderSapItemId
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    private function insertOrderSapShipment($inputOrder, $orderSapItemId)
    {
        // get the ship tracking number for later use
        $shipTrackingNumber = $inputOrder[self::INPUT_SAP_SHIP_TRACKING_NUMBER];

        // only add something if there is a ship tracking number
        // this is because this table is for when the item has been shipped
        if (!empty($shipTrackingNumber))
        {
            // add to the sales_order_sap_item table
            $sapOrderShipment = $this->_sapOrderShipmentFactory->create();
            $sapOrderShipment->setData('order_sap_item_id', $orderSapItemId);
            $sapOrderShipment->setData('ship_tracking_number', $shipTrackingNumber);
            $sapOrderShipment->setData('qty', $inputOrder[self::INPUT_SAP_ORDER_QTY]);
            $sapOrderShipment->setData('confirmed_qty', $inputOrder[self::INPUT_SAP_CONFIRMED_QTY]);
            $sapOrderShipment->setData('delivery_number', $inputOrder[self::INPUT_SAP_DELIVERY_NUMBER]);
            $sapOrderShipment->setData('fulfillment_location', $inputOrder[self::INPUT_SAP_FULFILLMENT_LOCATION]);
            $sapOrderShipment->setData('sap_billing_doc_number', $inputOrder[self::INPUT_SAP_SAP_BILLING_DOC_NUMBER]);
            $sapOrderShipment->setData('sap_billing_doc_date', $inputOrder[self::INPUT_SAP_SAP_BILLING_DOC_DATE]);

            // save the data to the table
            $this->_sapOrderShipmentResource->save($sapOrderShipment);
        }
    }

    /**
     * Update the order sap Item table with the appropriate values
     *
     * @param $inputOrder
     * @param $sapOrderItem
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    private function updateOrderSapItem($inputOrder, $sapOrderItem, $totalConfirmedQuantity, $totalOrderedQuantity)
    {
        // initialize update flag
        $isUpdateNeeded = false;

        // check sap order status
        $inputValue = $inputOrder[self::INPUT_SAP_SAP_ORDER_STATUS];
        $sapOrderValue = $sapOrderItem->getData('sap_order_status');
        if ((!empty($inputValue) || !empty($sapOrderValue)) && $inputValue !== $sapOrderValue)
        {
            $isUpdateNeeded = true;
            $sapOrderItem->setData('sap_order_status', $inputValue);
        }

        // check order status
        $inputValue = $this->getOrderStatus($inputOrder, $totalConfirmedQuantity, $totalOrderedQuantity);
        $sapOrderItemValue = $sapOrderItem->getData('order_status');
        if ((!empty($inputValue) || !empty($sapOrderValue)) && $inputValue !== $sapOrderItemValue)
        {
            $isUpdateNeeded = true;
            $sapOrderItem->setData('order_status', $inputValue);
        }

        // check the confirmed quantity
        $inputValue = $inputOrder[self::INPUT_SAP_CONFIRMED_QTY];
        $sapOrderItemValue = $sapOrderItem->getData('confirmed_qty');
        if (bccomp($inputValue, $sapOrderItemValue, 3) <> 0)
        {
            $isUpdateNeeded = true;
            $sapOrderItem->setData('confirmed_qty', $inputValue);
        }

        // if there was something updated then update the table
        if ($isUpdateNeeded)
        {
            // update the table
            $this->_sapOrderItemResource->save($sapOrderItem);

        }
    }

    /**
     * Update the order sap Item table with the appropriate values
     *
     * @param $inputOrder
     * @param $sapOrderShipment
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    private function updateOrderSapShipment($inputOrder, $sapOrderShipment)
    {
        // initialize update flag
        $isUpdateNeeded = false;

        // check the ship tracking number
        $inputValue = $inputOrder[self::INPUT_SAP_SHIP_TRACKING_NUMBER];
        $sapOrderValue = $sapOrderShipment->getData('ship_tracking_number');
        if ((!empty($inputValue) || !empty($sapOrderValue)) && $inputValue !== $sapOrderValue)
        {
            $isUpdateNeeded = true;
            $sapOrderShipment->setData('ship_tracking_number', $inputValue);
        }

        // check the quantity
        $inputValue = $inputOrder[self::INPUT_SAP_ORDER_QTY];
        $sapOrderValue = $sapOrderShipment->getData('qty');
        if (bccomp($inputValue, $sapOrderValue, 3) <> 0)
        {
            $isUpdateNeeded = true;
            $sapOrderShipment->setData('qty', $inputValue);
        }

        // check the confirmed quantity
        $inputValue = $inputOrder[self::INPUT_SAP_CONFIRMED_QTY];
        $sapOrderValue = $sapOrderShipment->getData('confirmed_qty');
        if (bccomp($inputValue, $sapOrderValue, 3) <> 0)
        {
            $isUpdateNeeded = true;
            $sapOrderShipment->setData('confirmed_qty', $inputValue);
        }

        // check delivery number
        $inputValue = $inputOrder[self::INPUT_SAP_DELIVERY_NUMBER];
        $sapOrderValue = $sapOrderShipment->getData('delivery_number');
        if ((!empty($inputValue) || !empty($sapOrderValue)) && $inputValue !== $sapOrderValue)
        {
            $isUpdateNeeded = true;
            $sapOrderShipment->setData('delivery_number', $inputValue);
        }

        // check fulfillment location
        $inputValue = $inputOrder[self::INPUT_SAP_FULFILLMENT_LOCATION];
        $sapOrderValue = $sapOrderShipment->getData('fulfillment_location');
        if ((!empty($inputValue) || !empty($sapOrderValue)) && $inputValue !== $sapOrderValue)
        {
            $isUpdateNeeded = true;
            $sapOrderShipment->setData('fulfillment_location', $inputValue);
        }

        // check to see if the billing doc number changed
        $inputValue = $inputOrder[self::INPUT_SAP_SAP_BILLING_DOC_NUMBER];
        $sapOrderValue = $sapOrderShipment->getData('sap_billing_doc_number');
        if ((!empty($inputValue) || !empty($sapOrderValue)) && $inputValue !== $sapOrderValue)
        {
            $isUpdateNeeded = true;
            $sapOrderShipment->setData('sap_billing_doc_number', $inputValue);
        }

        // check to see if the billing doc date changed
        $inputValue = $inputOrder[self::INPUT_SAP_SAP_BILLING_DOC_DATE];
        $sapOrderValue = $sapOrderShipment->getData('sap_billing_doc_date');
        if ((!empty($inputValue) || !empty($sapOrderValue)))
        {
            // update date
            $isUpdateDate = true;

            // if they both have a value then compare the dates
            // otherwise go ahead and update
            if (!empty($inputValue) && !empty($sapOrderValue))
            {
                // compare the dates if they are not equal then update
                $originalDate = date("y-m-d", strtotime($sapOrderValue));
                $newDate = date("y-m-d", strtotime($inputValue));
                if ($originalDate === $newDate)
                {
                    $isUpdateDate = false;
                }
            }

            // if the date is okay to update then update
            if ($isUpdateDate)
            {
                $isUpdateNeeded = true;
                $sapOrderShipment->setData('sap_billing_doc_date', $inputValue);
            }
        }

        // if there was something updated then update the table
        if ($isUpdateNeeded)
        {
            // update the table
            $this->_sapOrderShipmentResource->save($sapOrderShipment);
        }
    }

    /**
     * Determine the status of the order or the order item
     *
     * @param $sapOrderStatus
     * @param $shipTrackingNumber
     * @return string
     */
    private function getOrderStatus($inputOrder, $totalConfirmedQuantity, $totalOrderedQuantity)
    {
        $sapOrderStatus = $inputOrder[self::INPUT_SAP_SAP_ORDER_STATUS];
        $shipTrackingNumber = $inputOrder[self::INPUT_SAP_SHIP_TRACKING_NUMBER];

        $status = 'created';

        // determine the status of the order
        if (!empty($shipTrackingNumber))
        {
            if ($totalConfirmedQuantity >= $totalOrderedQuantity) {
                $status = 'order_shipped';
            }
            else {
                $status = 'order_partially_shipped';
            }
        }
        else if ($sapOrderStatus === 'A')
        {
            $status = 'capture';
        }
        else if ($sapOrderStatus === 'B')
        {
            $status = 'created_blocked';
        }
        else
        {
            $status = 'created_approved';
        }

        // return the status
        return $status;
    }

    /**
     * Process the batch processing table with the appropriate values
     *
     * @param $inputOrder
     * @param $sapOrder
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    private function processOrderSapBatchInfo($inputOrder, $sapOrder)
    {
        // get the order id
        $orderId = $sapOrder->getData('order_id');
        if (!empty($orderId))
        {
            // get the order for the desired increment id
            $order = $this->_orderFactory->create();
            $this->_orderResource->load($order, $inputOrder[self::INPUT_SAP_MAGENTO_PO], 'increment_id');

            // set the order id
            $orderId = $order->getId();
        }

        // check to see if there is an order id
        if ($orderId)
        {
            // get the sap batch order
            $sapOrderBatch = $this->_sapOrderBatchFactory->create();
            $this->_sapOrderBatchResource->load($sapOrderBatch, $orderId, 'order_id');

            // check to see if the sap order batch was loaded properly
            // it should as the batch record should have been created before
            // the status file to contain the order but it is best to check
            $orderIdFromBatch = $sapOrderBatch->getData('order_id');
            if (!empty($orderIdFromBatch))
            {
                // check to see if the order sap batch needs to be updated
                // if so then update the order sap batch
                $this->updateOrderSapBatch($inputOrder, $sapOrderBatch);
            }
            else
            {
                // create the order sap record
                $sapOrderBatch->setData('order_id', $orderId);
                $this->insertOrderSapBatch($inputOrder, $sapOrderBatch);
            }
        }
        else
        {
            // log the error
            $this->_logger->error("SMG\Api\Helper\OrderStatusHelper - Missing orderId in file.");
        }
    }

    /**
     * Insert the order sap batch table with the appropriate values
     *
     * @param $inputOrder
     * @param $sapOrderBatch
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    private function insertOrderSapBatch($inputOrder, $sapOrderBatch)
    {
        // since this is an insert we need to check if the capture flag can be set
        if ($inputOrder[self::INPUT_SAP_SAP_ORDER_STATUS] === 'A')
        {
            $sapOrderBatch->setData('is_capture', true);
        }

        // check to see if the sap billing doc is set if it is then set the
        // invoice reconciliation for processing
        if (!empty($inputOrder[self::INPUT_SAP_SAP_BILLING_DOC_NUMBER]))
        {
            $sapOrderBatch->setData('is_invoice_reconciliation', true);
        }

        // save the data to the table
        $this->_sapOrderBatchResource->save($sapOrderBatch);
    }

    /**
     * Update the order sap batch table with the appropriate values
     *
     * @param $inputOrder
     * @param $sapOrderBatch
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    private function updateOrderSapBatch($inputOrder, $sapOrderBatch)
    {
        // initialize update flag
        $isUpdateNeeded = false;

        // first check to see if this request was unauthorized or set to be unauthorized
        // as we don't want to do anything if that is the case
        if ($sapOrderBatch->getData('is_unauthorized') !== true)
        {
            // check the capture
            if ($inputOrder[self::INPUT_SAP_SAP_ORDER_STATUS] === 'A' &&
                empty($sapOrderBatch->getData('capture_process_date')) &&
                !$sapOrderBatch->getData('is_capture'))
            {
                $isUpdateNeeded = true;
                $sapOrderBatch->setData('is_capture', true);
            }

            // Get the sales order
            /**
             * @var \Magento\Sales\Model\Order $order
             */
            $order = $this->_orderFactory->create();
            $this->_orderResource->load($order, $sapOrderBatch->getData('order_id'));

            // Determine if this is a 100% discount as we do not
            // want to invoice the order online as it will fail.
            // 100% discounts should be invoiced offline
            if(!empty($order->getData('coupon_code')))
            {
                $orderDiscount = $this->_discountHelper->DiscountCode($order->getData('coupon_code'));
                $hdrDiscCondCode = $orderDiscount['hdr_disc_cond_code'];
                if($hdrDiscCondCode == 'Z616')
                {
                    // set the flag to have updates
                    $isUpdateNeeded = true;
                }
            }

            // determine if this is a subscription as we do not
            // want to invoice the order online as it will fail.
            // subscriptions should be invoiced offline.
            if ($order->isSubscription())
            {
                // set the flag to have updates
                $isUpdateNeeded = true;
            }

            // check the shipment
            if (!empty($inputOrder[self::INPUT_SAP_SHIP_TRACKING_NUMBER]) &&
                empty($sapOrderBatch->getData('shipment_process_date')) &&
                !$sapOrderBatch->getData('is_shipment'))
            {
                $isUpdateNeeded = true;
                $sapOrderBatch->setData('is_shipment', true);
            }

            // check if the order was invoiced
            // if it was then it is ready to be reconciled
            if (!empty($inputOrder[self::INPUT_SAP_SAP_BILLING_DOC_NUMBER]) &&
                empty($sapOrderBatch->getData('invoice_reconciliation_date')) &&
                !$sapOrderBatch->getData('is_invoice_reconciliation'))
            {
                $isUpdateNeeded = true;
                $sapOrderBatch->setData('is_invoice_reconciliation', true);
            }
        }

        // if there was something updated then update the table
        if ($isUpdateNeeded)
        {
            // update the table
            $this->_sapOrderBatchResource->save($sapOrderBatch);
        }
    }

    /**
     * This function allows orders to be invoiced but offline so
     * the system doesn't try to capture funds.
     *
     * @param \Magento\Sales\Model\Order $order
     * @param \SMG\Sap\Model\SapOrderBatch $sapOrderBatch
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function invoiceOffline($order, $sapOrderBatch)
    {
        /* create a invoice */
        // first check to see if there is an invoice that exists already
        // if there is one then don't try to create one
        if (!$order->hasInvoices())
        {
            if ($order->canInvoice())
            {
                $invoice = $this->_invoiceService->prepareInvoice($order);
                if (!$invoice->getTotalQty())
                {
                    throw new \Magento\Framework\Exception\LocalizedException(__('You can\'t create an invoice without products.'));
                }

                $invoice->setRequestedCaptureCase(\Magento\Sales\Model\Order\Invoice::CAPTURE_OFFLINE);
                $invoice->register();

                $retryAttempts = 0;
                $maxAttempts = 3;
                $retryWaitTime = 10;
                while ($retryAttempts <= $maxAttempts) {
                    try {
                        $transaction = $this->_transaction
                            ->addObject($invoice)
                            ->addObject($invoice->getOrder());
                        $transaction->save();
                        break;
                    } catch (ZaiusException $e) {
                        // Log and ignore any Zaius errors.
                        $this->_logger->error($e->getMessage());
                        break;
                    } catch (\Throwable $e) {
                        // If this is a deadlock or lock wait timeout, let's retry the transaction after waiting a few seconds.
                        if (($e->getCode() == self::ERROR_CODE_LOCK_WAIT || $e->getCode() == self::ERROR_CODE_DEAD_LOCK) && $retryAttempts <= $maxAttempts) {
                            $retryAttempts++;
                            sleep($retryWaitTime);
                        } else {
                            throw $e;
                        }
                    }
                }
                $this->_invoiceSender->send($invoice);
                $order->addStatusHistoryComment(__('Notified customer about invoice #%1.', $invoice->getId()))
                    ->setIsCustomerNotified(false)
                    ->save();
            }
        }
        /* end of create a invoice */

        $today = date('Y-m-d H:i:s');
        $sapOrderBatch->setData('is_capture', true);
        $sapOrderBatch->setData('capture_process_date', $today);
    }
}
