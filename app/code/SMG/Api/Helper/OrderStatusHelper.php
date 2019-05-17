<?php

namespace SMG\Api\Helper;

use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\ResourceModel\Order as OrderResource;
use Psr\Log\LoggerInterface;
use SMG\Sap\Model\SapOrderFactory;
use SMG\Sap\Model\SapOrderBatchFactory;
use SMG\Sap\Model\SapOrderHistoryFactory;
use SMG\Sap\Model\SapOrderItemFactory;
use SMG\Sap\Model\SapOrderItemHistoryFactory;
use SMG\Sap\Model\ResourceModel\SapOrder;
use SMG\Sap\Model\ResourceModel\SapOrderBatch;
use SMG\Sap\Model\ResourceModel\SapOrderHistory;
use SMG\Sap\Model\ResourceModel\SapOrderItem;
use SMG\Sap\Model\ResourceModel\SapOrderItemHistory;
use SMG\Sap\Model\ResourceModel\SapOrder\CollectionFactory as SapOrderCollectionFactory;
use SMG\Sap\Model\ResourceModel\SapOrderBatch\CollectionFactory as SapOrderBatchCollectionFactory;
use SMG\Sap\Model\ResourceModel\SapOrderHistory\CollectionFactory as SapOrderHistoryCollectionFactory;
use SMG\Sap\Model\ResourceModel\SapOrderItem\CollectionFactory as SapOrderItemCollectionFactory;
use SMG\Sap\Model\ResourceModel\SapOrderItemHistory\CollectionFactory as SapOrderItemHistoryCollectionFactory;

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

    // Table Constants
    const SALES_ORDER_SAP_ORDER_ID = 'order_id';
    const SALES_ORDER_SAP_SAP_ORDER_ID = 'sap_order_id';
    const SALES_ORDER_SAP_ORDER_CREATED_AT = 'order_created_at';
    const SALES_ORDER_SAP_SAP_ORDER_STATUS = 'sap_order_status';
    const SALES_ORDER_SAP_ORDER_STATUS = 'order_status';
    const SALES_ORDER_SAP_SAP_BILLING_DOC_NUMBER = 'sap_billing_doc_number';
    const SALES_ORDER_SAP_SAP_BILLING_DOC_DATE = 'sap_billing_doc_date';
    const SALES_ORDER_SAP_SAP_PAYER_ID = 'sap_payer_id';
    const SALES_ORDER_SAP_DELIVERY_NUMBER = 'delivery_number';

    const SALES_ORDER_SAP_HISTORY_ORDER_SAP_ID = 'order_sap_id';
    const SALES_ORDER_SAP_HISTORY_ORDER_STATUS = 'order_status';
    const SALES_ORDER_SAP_HISTORY_ORDER_STATUS_NOTES = 'order_status_notes';

    const SALES_ORDER_SAP_ITEM_ORDER_SAP_ID = 'order_sap_id';
    const SALES_ORDER_SAP_ITEM_SAP_ORDER_STATUS = 'sap_order_status';
    const SALES_ORDER_SAP_ITEM_ORDER_STATUS = 'order_status';
    const SALES_ORDER_SAP_ITEM_FULFILLMENT_LOCATION = 'fulfillment_location';
    const SALES_ORDER_SAP_ITEM_SKU = 'sku';
    const SALES_ORDER_SAP_ITEM_SKU_DESCRIPTION = 'sku_description';
    const SALES_ORDER_SAP_ITEM_QTY = 'qty';
    const SALES_ORDER_SAP_ITEM_CONFIRMED_QTY = 'confirmed_qty';
    const SALES_ORDER_SAP_ITEM_SHIP_TRACKING_NUMBER = 'ship_tracking_number';

    const SALES_ORDER_SAP_ITEM_HISTORY_ORDER_SAP_ITEM_ID = 'order_sap_item_id';
    const SALES_ORDER_SAP_ITEM_HISTORY_ORDER_STATUS = 'order_status';
    const SALES_ORDER_SAP_ITEM_HISTORY_ORDER_STATUS_NOTES = 'order_status_notes';

    const SALES_ORDER_SAP_BATCH_ORDER_ID = "order_id";
    const SALES_ORDER_SAP_BATCH_IS_CAPTURE = "is_capture";
    const SALES_ORDER_SAP_BATCH_CAPTURE_PROCESS_DATE = "capture_process_date";
    const SALES_ORDER_SAP_BATCH_IS_SHIPMENT = "is_shipment";
    const SALES_ORDER_SAP_BATCH_SHIPMENT_PROCESS_DATE = "shipment_process_date";
    const SALES_ORDER_SAP_BATCH_IS_UNAUTHORIZED = "is_unauthorized";
    const SALES_ORDER_SAP_BATCH_UNAUTHORIZED_PROCESS_DATE = "unauthorized_process_date";

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
     * @var SapOrderHistoryFactory
     */
    protected $_sapOrderHistoryFactory;

    /**
     * @var SapOrderItemFactory
     */
    protected $_sapOrderItemFactory;

    /**
     * @var SapOrderItemHistoryFactory
     */
    protected $_sapOrderItemHistoryFactory;

    /**
     * @var SapOrder
     */
    protected $_sapOrderResource;

    /**
     * @var SapOrderBatch
     */
    protected $_sapOrderBatchResource;

    /**
     * @var SapOrderHistory
     */
    protected $_sapOrderHistoryResource;

    /**
     * @var SapOrderItem
     */
    protected $_sapOrderItemResource;

    /**
     * @var SapOrderItemHistory
     */
    protected $_sapOrderItemHistoryResource;

    /**
     * @var SapOrderCollectionFactory
     */
    protected $_sapOrderCollectionFactory;

    /**
     * @var SapOrderBatchCollectionFactory
     */
    protected $_sapOrderBatchCollectionFactory;

    /**
     * @var SapOrderHistoryCollectionFactory
     */
    protected $_sapOrderHistoryCollectionFactory;

    /**
     * @var SapOrderItemCollectionFactory
     */
    protected $_sapOrderItemCollectionFactory;

    /**
     * @var SapOrderItemHistoryCollectionFactory
     */
    protected $_sapOrderItemHistoryCollectionFactory;

    /**
     * @var ResponseHelper
     */
    protected $_responseHelper;

    /**
     * @var OrderFactory
     */
    protected $_orderFactory;

    /**
     * @var OrderResource
     */
    protected $_orderResource;

    /**
     * OrdersHelper constructor.
     *
     * @param LoggerInterface $logger
     * @param SapOrderFactory $sapOrderFactory
     * @param SapOrderBatchFactory $sapOrderBatchFactory
     * @param SapOrderHistoryFactory $sapOrderHistoryFactory
     * @param SapOrderItemFactory $sapOrderItemFactory
     * @param SapOrderItemHistoryFactory $sapOrderItemHistoryFactory
     * @param SapOrder $sapOrderResource
     * @param SapOrderBatch $sapOrderBatchResource
     * @param SapOrderHistory $sapOrderHistoryResource
     * @param SapOrderItem $sapOrderItemResource
     * @param SapOrderItemHistory $sapOrderItemHistoryResource
     * @param SapOrderCollectionFactory $sapOrderCollectionFactory
     * @param SapOrderBatchCollectionFactory $sapOrderBatchCollectionFactory
     * @param SapOrderHistoryCollectionFactory $sapOrderHistoryCollectionFactory
     * @param SapOrderItemCollectionFactory $sapOrderItemCollectionFactory
     * @param SapOrderItemHistoryCollectionFactory $sapOrderItemHistoryCollectionFactory
     * @param ResponseHelper $responseHelper
     * @param OrderFactory $orderFactory
     * @param OrderResource $orderResource
     */
    public function __construct(
        LoggerInterface $logger,
        SapOrderFactory $sapOrderFactory,
        SapOrderBatchFactory $sapOrderBatchFactory,
        SapOrderHistoryFactory $sapOrderHistoryFactory,
        SapOrderItemFactory $sapOrderItemFactory,
        SapOrderItemHistoryFactory $sapOrderItemHistoryFactory,
        SapOrder $sapOrderResource,
        SapOrderBatch $sapOrderBatchResource,
        SapOrderHistory $sapOrderHistoryResource,
        SapOrderItem $sapOrderItemResource,
        SapOrderItemHistory $sapOrderItemHistoryResource,
        SapOrderCollectionFactory $sapOrderCollectionFactory,
        SapOrderBatchCollectionFactory $sapOrderBatchCollectionFactory,
        SapOrderHistoryCollectionFactory $sapOrderHistoryCollectionFactory,
        SapOrderItemCollectionFactory $sapOrderItemCollectionFactory,
        SapOrderItemHistoryCollectionFactory $sapOrderItemHistoryCollectionFactory,
        ResponseHelper $responseHelper,
        OrderFactory $orderFactory,
        OrderResource $orderResource)
    {
        $this->_logger = $logger;
        $this->_sapOrderFactory = $sapOrderFactory;
        $this->_sapOrderBatchFactory = $sapOrderBatchFactory;
        $this->_sapOrderHistoryFactory = $sapOrderHistoryFactory;
        $this->_sapOrderItemFactory = $sapOrderItemFactory;
        $this->_sapOrderItemHistoryFactory = $sapOrderItemHistoryFactory;
        $this->_sapOrderResource = $sapOrderResource;
        $this->_sapOrderBatchResource = $sapOrderBatchResource;
        $this->_sapOrderHistoryResource = $sapOrderHistoryResource;
        $this->_sapOrderItemResource = $sapOrderItemResource;
        $this->_sapOrderItemHistoryResource = $sapOrderItemHistoryResource;
        $this->_sapOrderCollectionFactory = $sapOrderCollectionFactory;
        $this->_sapOrderBatchCollectionFactory = $sapOrderBatchCollectionFactory;
        $this->_sapOrderHistoryCollectionFactory = $sapOrderHistoryCollectionFactory;
        $this->_sapOrderItemCollectionFactory = $sapOrderItemCollectionFactory;
        $this->_sapOrderItemHistoryCollectionFactory = $sapOrderItemHistoryCollectionFactory;
        $this->_responseHelper = $responseHelper;
        $this->_orderFactory = $orderFactory;
        $this->_orderResource = $orderResource;
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
                // check to see if there is an order increment number
                $orderIncrementId = $inputOrder[self::INPUT_SAP_MAGENTO_PO];
                if ($orderIncrementId)
                {
                    // get the order from the increment id
                    $order = $this->_orderFactory->create();
                    $this->_orderResource->load($order, $orderIncrementId, 'increment_id');

                    // get the order id
                    $orderId = $order->getId();

                    // process the sap order info
                    $this->processOrderSapInfo($inputOrder, $orderId);

                    // update the batch processing for those
                    // that need to be processed through batch capture
                    $this->processOrderSapBatchInfo($inputOrder, $orderId);
                }
                else
                {
                    // log the error
                    $this->_logger->error("SMG\Api\Helper\OrderStatusHelper - Missing magento po number.");
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
     * @param $orderId
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    private function processOrderSapInfo($inputOrder, $orderId)
    {
        // check to see if there is an order id
        if ($orderId)
        {
            // create the sap orders factory to retrieve all
            // that have an order id but there should only be one
            // if it was created already
            $sapOrders = $this->_sapOrderCollectionFactory->create();
            $sapOrders->addFieldToFilter(self::SALES_ORDER_SAP_ORDER_ID, ['eq' => $orderId]);

            // check to see if there is a record already
            // if there is then update the appropriate tables
            // otherwise create new values in the tables
            if ($sapOrders->count() > 0)
            {
                // loop through the orders from sales_order_sap table
                // there should only be one
                foreach($sapOrders as $sapOrder)
                {
                    // check to see if the order needs to be updated
                    // if so then update the order
                    $this->updateOrderSap($inputOrder, $sapOrder);

                    // get the order sap id
                    $orderSapId = $sapOrder->getId();

                    // create the sap orders item factory to retrieve all
                    // the items with the desired order
                    $sapOrderItems = $this->_sapOrderItemCollectionFactory->create();
                    $sapOrderItems->addFieldToFilter(self::SALES_ORDER_SAP_ITEM_ORDER_SAP_ID, ['eq' => $orderSapId]);

                    // check to see if there is a record already
                    // if there is then update the appropriate tables
                    // otherwise create new values in the tables
                    if ($sapOrderItems->count() > 0)
                    {
                        // initialize if the item has already been added to this order
                        $isAdd = true;

                        // loop through the orders
                        foreach($sapOrderItems as $sapOrderItem)
                        {
                            // if it is the same sku then we update
                            // otherwise we will insert
                            if ($inputOrder[self::INPUT_SAP_SKU] === $sapOrderItem->getData(self::SALES_ORDER_SAP_ITEM_SKU))
                            {
                                // set the is add flag to false since
                                // it already exists for this order
                                $isAdd = false;

                                // check to see if the order needs to be updated
                                // if so then update the order item
                                $this->updateOrderSapItem($inputOrder, $sapOrderItem);
                            }
                        }

                        // if the flag is true then add the order item
                        if ($isAdd)
                        {
                            // create the order sap item record
                            $this->insertOrderSapItem($inputOrder, $orderSapId, null, null);
                        }
                    }
                    else
                    {
                        // create the order sap item record
                        $this->insertOrderSapItem($inputOrder, $orderSapId, null, null);
                    }
                }
            }
            else
            {
                // create the order sap record
                $orderSapId = $this->insertOrderSap($inputOrder, $orderId, null, null);

                // create the order sap item record
                $this->insertOrderSapItem($inputOrder, $orderSapId, null, null);
            }
        }
        else
        {
            // log the error
            $this->_logger->error("SMG\Api\Helper\OrderStatusHelper - Missing orderId in file.");
        }
    }

    /**
     * Insert the order sap table with the appropriate values
     *
     * @param $inputOrder
     * @param $orderId
     * @param $orderStatus
     * @param $orderStatusNotes
     * @return mixed
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    private function insertOrderSap($inputOrder, $orderId, $orderStatus, $orderStatusNotes)
    {
        // variables
        $sapOrderStatus = $inputOrder[self::INPUT_SAP_SAP_ORDER_STATUS];

        // if the order status is empty then that means this is a new
        // order so make it created
        if (empty($orderStatus))
        {
            $orderStatus = $this->getOrderStatus($sapOrderStatus, $inputOrder[self::INPUT_SAP_SHIP_TRACKING_NUMBER]);
        }

        // Add to the sales_order_sap table
        $sapOrder = $this->_sapOrderFactory->create();
        $sapOrder->setData(self::SALES_ORDER_SAP_ORDER_ID, $orderId);
        $sapOrder->setData(self::SALES_ORDER_SAP_SAP_ORDER_ID, $inputOrder[self::INPUT_SAP_ORDER_NUMBER]);
        $sapOrder->setData(self::SALES_ORDER_SAP_ORDER_CREATED_AT, $inputOrder[self::INPUT_SAP_ORDER_CREATE_DATE]);
        $sapOrder->setData(self::SALES_ORDER_SAP_SAP_ORDER_STATUS, $sapOrderStatus);
        $sapOrder->setData(self::SALES_ORDER_SAP_ORDER_STATUS, $orderStatus);
        $sapOrder->setData(self::SALES_ORDER_SAP_SAP_PAYER_ID, $inputOrder[self::INPUT_SAP_PAYER_ID]);
        $sapOrder->setData(self::SALES_ORDER_SAP_DELIVERY_NUMBER, $inputOrder[self::INPUT_SAP_DELIVERY_NUMBER]);

        // save the data to the table
        $this->_sapOrderResource->save($sapOrder);

        // get the entity id from the newly added sap order
        $orderSapId = $sapOrder->getId();

        // Add to the sale_order_sap_history table
        $this->insertOrderSapHistory($orderSapId, $orderStatus, $orderStatusNotes);

        // return the order sap id that was generated from
        // inserting into the table
        return $orderSapId;
    }

    /**
     * Insert the order sap history table with the appropriate values
     *
     * @param $orderSapId
     * @param $orderStatus
     * @param $orderStatusNotes
     * @return mixed
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    private function insertOrderSapHistory($orderSapId, $orderStatus, $orderStatusNotes)
    {
        // Add to the sale_order_sap_history table
        $sapOrderHistory = $this->_sapOrderHistoryFactory->create();
        $sapOrderHistory->setData(self::SALES_ORDER_SAP_HISTORY_ORDER_SAP_ID, $orderSapId);
        $sapOrderHistory->setData(self::SALES_ORDER_SAP_HISTORY_ORDER_STATUS, $orderStatus);

        if (!empty($orderStatusNotes))
        {
            $sapOrderHistory->setData(self::SALES_ORDER_SAP_HISTORY_ORDER_STATUS_NOTES, $orderStatusNotes);
        }

        // save the data to the table
        $this->_sapOrderHistoryResource->save($sapOrderHistory);

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
    private function updateOrderSap($inputOrder, $sapOrder)
    {
        // initialize update flag
        $isUpdateNeeded = false;

        // initialize the order status and notes
        $orderStatus = 'updated';
        $orderStatusNotes = '';

        // check sap order id
        $inputValue = $inputOrder[self::INPUT_SAP_ORDER_NUMBER];
        $sapOrderValue = $sapOrder->getData(self::SALES_ORDER_SAP_SAP_ORDER_ID);
        if ((!empty($inputValue) || !empty($sapOrderValue)) && $inputValue !== $sapOrderValue)
        {
            $isUpdateNeeded = true;
            $sapOrder->setData(self::SALES_ORDER_SAP_SAP_ORDER_ID, $inputValue);
            $orderStatusNotes .= 'SAP Order Id was ' . $sapOrderValue . ' now ' . $inputValue . '. ';
        }

        // check order created at
        $inputValue = $inputOrder[self::INPUT_SAP_ORDER_CREATE_DATE];
        $sapOrderValue = $sapOrder->getData(self::SALES_ORDER_SAP_ORDER_CREATED_AT);
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
                $sapOrder->setData(self::SALES_ORDER_SAP_ORDER_CREATED_AT, $inputValue);
                $orderStatusNotes .= 'Order created was ' . $sapOrderValue . ' now ' . $inputValue . '. ';
            }
        }

        // check sap order status
        $inputValue = $inputOrder[self::INPUT_SAP_SAP_ORDER_STATUS];
        $sapOrderValue = $sapOrder->getData(self::SALES_ORDER_SAP_SAP_ORDER_STATUS);
        if ((!empty($inputValue) || !empty($sapOrderValue)) && $inputValue !== $sapOrderValue)
        {
            $isUpdateNeeded = true;
            $sapOrder->setData(self::SALES_ORDER_SAP_SAP_ORDER_STATUS, $inputValue);
            $orderStatusNotes .= 'SAP Order Status was ' . $sapOrderValue . ' now ' . $inputValue . '. ';
        }

        // check order status
        $inputValue = $this->getOrderStatus($inputOrder[self::INPUT_SAP_SAP_ORDER_STATUS], $inputOrder[self::INPUT_SAP_SHIP_TRACKING_NUMBER]);
        $sapOrderValue = $sapOrder->getData(self::SALES_ORDER_SAP_ORDER_STATUS);
        if ((!empty($inputValue) || !empty($sapOrderValue)) && $inputValue !== $sapOrderValue)
        {
            $isUpdateNeeded = true;
            $sapOrder->setData(self::SALES_ORDER_SAP_ORDER_STATUS, $inputValue);
            $orderStatus = $inputValue;
            $orderStatusNotes .= 'Order Status was ' . $sapOrderValue . ' now ' . $inputValue . '. ';
        }

        // check if the payer id changed
        $inputValue = $inputOrder[self::INPUT_SAP_PAYER_ID];
        $sapOrderValue = $sapOrder->getData(self::SALES_ORDER_SAP_SAP_PAYER_ID);
        if ((!empty($inputValue) || !empty($sapOrderValue)) && $inputValue !== $sapOrderValue)
        {
            $isUpdateNeeded = true;
            $sapOrder->setData(self::SALES_ORDER_SAP_SAP_PAYER_ID, $inputValue);
            $orderStatusNotes .= 'Payer Id was ' . $sapOrderValue . ' now ' . $inputValue . '. ';
        }

        // if there was something updated then update the table
        if ($isUpdateNeeded)
        {
            // update the table
            $this->_sapOrderResource->save($sapOrder);

            // insert new record in the history
            $this->insertOrderSapHistory($sapOrder->getId(), $orderStatus, $orderStatusNotes);
        }
    }

    /**
     * Insert the order sap item table with the appropriate values
     *
     * @param $inputOrder
     * @param $orderSapId
     * @param $orderStatus
     * @param $orderStatusNotes
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    private function insertOrderSapItem($inputOrder, $orderSapId, $orderStatus, $orderStatusNotes)
    {
        // variables
        $sapOrderStatus = $inputOrder[self::INPUT_SAP_SAP_ORDER_STATUS];
        $shipTrackingNumber = $inputOrder[self::INPUT_SAP_SHIP_TRACKING_NUMBER];

        // if the order status is empty then that means this is a new
        // order so make it created
        if (empty($orderStatus))
        {
            // get the order status
            $orderStatus = $this->getOrderStatus($sapOrderStatus, $shipTrackingNumber);
        }

        // add to the sales_order_sap_item table
        $sapOrderItem = $this->_sapOrderItemFactory->create();
        $sapOrderItem->setData(self::SALES_ORDER_SAP_ITEM_ORDER_SAP_ID, $orderSapId);
        $sapOrderItem->setData(self::SALES_ORDER_SAP_ITEM_SAP_ORDER_STATUS, $sapOrderStatus);
        $sapOrderItem->setData(self::SALES_ORDER_SAP_ITEM_ORDER_STATUS, $orderStatus);
        $sapOrderItem->setData(self::SALES_ORDER_SAP_ITEM_FULFILLMENT_LOCATION, $inputOrder[self::INPUT_SAP_FULFILLMENT_LOCATION]);
        $sapOrderItem->setData(self::SALES_ORDER_SAP_ITEM_SKU, $inputOrder[self::INPUT_SAP_SKU]);
        $sapOrderItem->setData(self::SALES_ORDER_SAP_ITEM_SKU_DESCRIPTION, $inputOrder[self::INPUT_SAP_SKU_DESCRIPTION]);
        $sapOrderItem->setData(self::SALES_ORDER_SAP_ITEM_QTY, $inputOrder[self::INPUT_SAP_ORDER_QTY]);
        $sapOrderItem->setData(self::SALES_ORDER_SAP_ITEM_CONFIRMED_QTY, $inputOrder[self::INPUT_SAP_CONFIRMED_QTY]);
        $sapOrderItem->setData(self::SALES_ORDER_SAP_ITEM_SHIP_TRACKING_NUMBER, $shipTrackingNumber);
        $sapOrderItem->setData(self::SALES_ORDER_SAP_SAP_BILLING_DOC_NUMBER, $inputOrder[self::INPUT_SAP_SAP_BILLING_DOC_NUMBER]);
        $sapOrderItem->setData(self::SALES_ORDER_SAP_SAP_BILLING_DOC_DATE, $inputOrder[self::INPUT_SAP_SAP_BILLING_DOC_DATE]);

        // save the data to the table
        $this->_sapOrderItemResource->save($sapOrderItem);

        // get the entity id from the newly added sap order item
        $orderSapItemId = $sapOrderItem->getId();

        // add to the sales_order_sap_item_history table
        $this->insertOrderSapItemHistory($orderSapItemId, $orderStatus, $orderStatusNotes);
    }

    /**
     * Insert the order sap item history table with the appropriate values
     *
     * @param $orderSapItemId
     * @param $orderStatus
     * @param $orderStatusNotes
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    private function insertOrderSapItemHistory($orderSapItemId, $orderStatus, $orderStatusNotes)
    {
        // add to the sales_order_sap_item_history table
        $sapOrderItemHistory = $this->_sapOrderItemHistoryFactory->create();
        $sapOrderItemHistory->setData(self::SALES_ORDER_SAP_ITEM_HISTORY_ORDER_SAP_ITEM_ID, $orderSapItemId);
        $sapOrderItemHistory->setData(self::SALES_ORDER_SAP_ITEM_HISTORY_ORDER_STATUS, $orderStatus);

        if (!empty($orderStatusNotes))
        {
            $sapOrderItemHistory->setData(self::SALES_ORDER_SAP_ITEM_HISTORY_ORDER_STATUS_NOTES, $orderStatusNotes);
        }

        // save the data to the table
        $this->_sapOrderItemHistoryResource->save($sapOrderItemHistory);
    }

    /**
     * Update the order sap Item table with the appropriate values
     *
     * @param $inputOrder
     * @param $sapOrderItem
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    private function updateOrderSapItem($inputOrder, $sapOrderItem)
    {
        // initialize update flag
        $isUpdateNeeded = false;

        // initialize the order status and notes
        $orderStatus = 'updated';
        $orderStatusNotes = '';

        // check sap order status
        $inputValue = $inputOrder[self::INPUT_SAP_SAP_ORDER_STATUS];
        $sapOrderValue = $sapOrderItem->getData(self::SALES_ORDER_SAP_ITEM_SAP_ORDER_STATUS);
        if ((!empty($inputValue) || !empty($sapOrderValue)) && $inputValue !== $sapOrderValue)
        {
            $isUpdateNeeded = true;
            $sapOrderItem->setData(self::SALES_ORDER_SAP_ITEM_SAP_ORDER_STATUS, $inputValue);
            $orderStatusNotes .= 'SAP Order Status was ' . $sapOrderValue . ' now ' . $inputValue . '. ';
        }

        // check order status
        $inputValue = $this->getOrderStatus($inputOrder[self::INPUT_SAP_SAP_ORDER_STATUS], $inputOrder[self::INPUT_SAP_SHIP_TRACKING_NUMBER]);
        $sapOrderItemValue = $sapOrderItem->getData(self::SALES_ORDER_SAP_ITEM_ORDER_STATUS);
        if ((!empty($inputValue) || !empty($sapOrderValue)) && $inputValue !== $sapOrderItemValue)
        {
            $isUpdateNeeded = true;
            $sapOrderItem->setData(self::SALES_ORDER_SAP_ITEM_ORDER_STATUS, $inputValue);
            $orderStatus = $inputValue;
            $orderStatusNotes .= 'Order Status was ' . $sapOrderItemValue . ' now ' . $inputValue . '. ';
        }

        // check fulfillment location
        $inputValue = $inputOrder[self::INPUT_SAP_FULFILLMENT_LOCATION];
        $sapOrderValue = $sapOrderItem->getData(self::SALES_ORDER_SAP_ITEM_FULFILLMENT_LOCATION);
        if ((!empty($inputValue) || !empty($sapOrderValue)) && $inputValue !== $sapOrderValue)
        {
            $isUpdateNeeded = true;
            $sapOrderItem->setData(self::SALES_ORDER_SAP_ITEM_FULFILLMENT_LOCATION, $inputValue);
            $orderStatusNotes .= 'Fulfillment Location was ' . $sapOrderValue . ' now ' . $inputValue . '. ';
        }

        // check the quantity
        $inputValue = $inputOrder[self::INPUT_SAP_ORDER_QTY];
        $sapOrderValue = $sapOrderItem->getData(self::SALES_ORDER_SAP_ITEM_QTY);
        if (bccomp($inputValue, $sapOrderValue, 3) <> 0)
        {
            $isUpdateNeeded = true;
            $sapOrderItem->setData(self::SALES_ORDER_SAP_ITEM_QTY, $inputValue);
            $orderStatusNotes .= 'Quantity was ' . $sapOrderValue . ' now ' . $inputValue . '. ';
        }

        // check the confirmed quantity
        $inputValue = $inputOrder[self::INPUT_SAP_CONFIRMED_QTY];
        $sapOrderValue = $sapOrderItem->getData(self::SALES_ORDER_SAP_ITEM_CONFIRMED_QTY);
        if (bccomp($inputValue, $sapOrderValue, 3) <> 0)
        {
            $isUpdateNeeded = true;
            $sapOrderItem->setData(self::SALES_ORDER_SAP_ITEM_CONFIRMED_QTY, $inputValue);
            $orderStatusNotes .= 'Confirmed Quantity was ' . $sapOrderValue . ' now ' . $inputValue . '. ';
        }

        // check the ship tracking number
        $inputValue = $inputOrder[self::INPUT_SAP_SHIP_TRACKING_NUMBER];
        $sapOrderValue = $sapOrderItem->getData(self::SALES_ORDER_SAP_ITEM_SHIP_TRACKING_NUMBER);
        if ((!empty($inputValue) || !empty($sapOrderValue)) && $inputValue !== $sapOrderValue)
        {
            $isUpdateNeeded = true;
            $sapOrderItem->setData(self::SALES_ORDER_SAP_ITEM_SHIP_TRACKING_NUMBER, $inputValue);
            $orderStatusNotes .= 'Ship Tracking was ' . $sapOrderValue . ' now ' . $inputValue . '. ';
        }

        // check to see if the billing doc number changed
        $inputValue = $inputOrder[self::INPUT_SAP_SAP_BILLING_DOC_NUMBER];
        $sapOrderValue = $sapOrderItem->getData(self::SALES_ORDER_SAP_SAP_BILLING_DOC_NUMBER);
        if ((!empty($inputValue) || !empty($sapOrderValue)) && $inputValue !== $sapOrderValue)
        {
            $isUpdateNeeded = true;
            $sapOrderItem->setData(self::SALES_ORDER_SAP_SAP_BILLING_DOC_NUMBER, $inputValue);
            $orderStatusNotes .= 'SAP Invoice was ' . $sapOrderValue . ' now ' . $inputValue . '. ';
        }

        // check to see if the billing doc date changed
        $inputValue = $inputOrder[self::INPUT_SAP_SAP_BILLING_DOC_DATE];
        $sapOrderValue = $sapOrderItem->getData(self::SALES_ORDER_SAP_SAP_BILLING_DOC_DATE);
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
                $sapOrderItem->setData(self::SALES_ORDER_SAP_SAP_BILLING_DOC_DATE, $inputValue);
                $orderStatusNotes .= 'SAP Invoice Date was ' . $sapOrderValue . ' now ' . $inputValue . '. ';
            }
        }

        // if there was something updated then update the table
        if ($isUpdateNeeded)
        {
            // update the table
            $this->_sapOrderItemResource->save($sapOrderItem);

            // insert new record in the history
            $this->insertOrderSapItemHistory($sapOrderItem->getId(), $orderStatus, $orderStatusNotes);
        }
    }

    /**
     * Determine the status of the order or the order item
     *
     * @param $sapOrderStatus
     * @param $shipTrackingNumber
     * @return string
     */
    private function getOrderStatus($sapOrderStatus, $shipTrackingNumber)
    {
        $status = 'created';

        // determine the status of the order
        if (!empty($shipTrackingNumber))
        {
            $status = 'order_shipped';
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
     * @param $orderId
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    private function processOrderSapBatchInfo($inputOrder, $orderId)
    {
        // check to see if there is an order id
        if ($orderId)
        {
            // create the sap orders factory to retrieve all
            // that have an order id but there should only be one
            // if it was created already
            $sapOrderBatches = $this->_sapOrderBatchCollectionFactory->create();
            $sapOrderBatches->addFieldToFilter(self::SALES_ORDER_SAP_BATCH_ORDER_ID, ['eq' => $orderId]);

            // check to see if there is a record already
            // if there is then update the appropriate tables
            // otherwise create new values in the tables
            if ($sapOrderBatches->count() > 0)
            {
                // loop through the orders from sales_order_sap_batch table
                // there should only be one
                foreach($sapOrderBatches as $sapOrderBatch)
                {
                    // check to see if the order sap batch needs to be updated
                    // if so then update the order sap batch
                    $this->updateOrderSapBatch($inputOrder, $sapOrderBatch);
                }
            }
            else
            {
                // create the order sap record
                $this->insertOrderSapBatch($inputOrder, $orderId);
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
     * @param $orderId
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    private function insertOrderSapBatch($inputOrder, $orderId)
    {

        // Add to the sales_order_sap table
        $sapOrderBatch = $this->_sapOrderBatchFactory->create();
        $sapOrderBatch->setData(self::SALES_ORDER_SAP_BATCH_ORDER_ID, $orderId);

        // since this is an insert we need to check if the capture flag can be set
        if ($inputOrder[self::INPUT_SAP_SAP_ORDER_STATUS] === 'A')
        {
            $sapOrderBatch->setData(self::SALES_ORDER_SAP_BATCH_IS_CAPTURE, true);
        }

        // save the data to the table
        $this->_sapOrderBatchResource->save($sapOrderBatch);
    }

    /**
     * Update the order sap batch table with the appropriate values
     *
     * @param $inputOrder
     * @param $sapOrder
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    private function updateOrderSapBatch($inputOrder, $sapOrderBatch)
    {
        // initialize update flag
        $isUpdateNeeded = false;

        // first check to see if this request was unauthorized or set to be unauthorized
        // as we don't want to do anything if that is the case
        if ($sapOrderBatch->getData(self::SALES_ORDER_SAP_BATCH_IS_UNAUTHORIZED) !== true)
        {
            // check the capture
            if ($inputOrder[self::INPUT_SAP_SAP_ORDER_STATUS] === 'A' &&
                empty($sapOrderBatch->getData(self::SALES_ORDER_SAP_BATCH_CAPTURE_PROCESS_DATE)) &&
                !$sapOrderBatch->getData(self::SALES_ORDER_SAP_BATCH_IS_CAPTURE))
            {
                $isUpdateNeeded = true;
                $sapOrderBatch->setData(self::SALES_ORDER_SAP_BATCH_IS_CAPTURE, true);
            }

            // check the shipment
            if (!empty($inputOrder[self::INPUT_SAP_SHIP_TRACKING_NUMBER]) &&
                !empty($sapOrderBatch->getData(self::SALES_ORDER_SAP_BATCH_CAPTURE_PROCESS_DATE)) &&
                empty($sapOrderBatch->getData(self::SALES_ORDER_SAP_BATCH_SHIPMENT_PROCESS_DATE)) &&
                !$sapOrderBatch->getData(self::SALES_ORDER_SAP_BATCH_IS_SHIPMENT))
            {
                $isUpdateNeeded = true;
                $sapOrderBatch->setData(self::SALES_ORDER_SAP_BATCH_IS_SHIPMENT, true);
            }
        }

        // if there was something updated then update the table
        if ($isUpdateNeeded)
        {
            // update the table
            $this->_sapOrderBatchResource->save($sapOrderBatch);
        }
    }
}