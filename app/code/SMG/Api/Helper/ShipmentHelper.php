<?php

namespace SMG\Api\Helper;

use Magento\Sales\Api\Data\OrderInterfaceFactory;
use Magento\Sales\Api\Data\ShipmentTrackInterfaceFactory;
use Magento\Sales\Api\Data\ShipmentItemCreationInterfaceFactory;
use Magento\Sales\Api\Data\ShipmentTrackCreationInterfaceFactory;
use Magento\Sales\Api\ShipOrderInterface;
use Magento\Sales\Model\Spi\OrderResourceInterface;
use Magento\Sales\Model\Spi\ShipmentTrackResourceInterface;
use Magento\Setup\Exception;
use Psr\Log\LoggerInterface;
use SMG\CustomerServiceEmail\Api\OrderManagementInterface;
use SMG\CustomerServiceEmail\Api\Data\ItemInterface;
use SMG\Sap\Model\ResourceModel\SapOrder;
use SMG\Sap\Model\ResourceModel\SapOrderBatch;
use SMG\Sap\Model\ResourceModel\SapOrderBatch\CollectionFactory as SapOrderBatchCollectionFactory;
use SMG\Sap\Model\ResourceModel\SapOrderItem\CollectionFactory as SapOrderItemCollectionFactory;
use SMG\Sap\Model\ResourceModel\SapOrderShipment\CollectionFactory as SapOrderShipmentCollectionFactory;
use Magento\Framework\App\Config\ScopeConfigInterface as ScopeConfigInterface;
use Zaius\Engage\Helper\Sdk as Sdk;
use SMG\SubscriptionApi\Model\ResourceModel\SubscriptionOrder\CollectionFactory as SubscriptionOrderCollectionFactory;
class ShipmentHelper
{

    const INPUT_SAP_CONFIRMED_QTY = 'confirmed_qty';
    const INPUT_SAP_ORDER_QTY = 'qty';
    const INPUT_SAP_SKU = 'sku';
    const ACTION_FLAG_SHIP = 'ship';
    /**
     * @var array
     */
    protected $_customerServiceEmailIds = [];

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
     * @var ShipOrderInterface
     */
    protected $_shipOrderInterface;

    /**
     * @var ShipmentItemCreationInterfaceFactory
     */
    protected $_shipmentItemCreationInterfaceFactory;

    /**
     * @var OrderInterfaceFactory
     */
    protected $_orderFactory;

    /**
     * @var OrderResourceInterface
     */
    protected $_orderResource;

    /**
     * @var ShipmentTrackCreationInterfaceFactory
     */
    protected $_shipmentTrackCreationInterfaceFactory;

    /**
     * @var SapOrderItemCollectionFactory
     */
    protected $_sapOrderItemCollectionFactory;

    /**
     * @var ShipmentTrackInterfaceFactory
     */
    protected $_shipmentTrackFactory;

    /**
     * @var ShipmentTrackResourceInterface
     */
    protected $_shipmentTracKResource;

    /**
     * @var SapOrderBatch
     */
    protected $_sapOrderBatchResource;

    /**
     * @var SapOrder
     */
    protected $_sapOrderResource;

    /**
     * @var OrderManagementInterface
     */
    protected $_orderManagementInterface;

    /**
     * @var ItemInterface
     */
    protected $_itemInterface;

    /**
     * @var SapOrderShipmentCollectionFactory
     */
    protected $_sapOrderShipmentCollectionFactory;

    /**
     * @var scopeConfigInterface
     */
    protected $_scopeConfigInterface;

    /**
     * @var sdk
     */
    protected $_sdk;

    /**
     * @var SubscriptionOrderCollectionFactory
     */
    protected $_subscriptionOrderCollectionFactory;

    /**
     * BatchCaptureHelper constructor.
     *
     * @param LoggerInterface $logger
     * @param ResponseHelper $responseHelper
     * @param SapOrderBatchCollectionFactory $sapOrderBatchCollectionFactory
     * @param ShipOrderInterface $shipOrderInterface
     * @param ShipmentItemCreationInterfaceFactory $shipmentItemCreationInterfaceFactory
     * @param OrderInterfaceFactory $orderFactory
     * @param OrderResourceInterface $orderResource
     * @param ShipmentTrackCreationInterfaceFactory $shipmentTrackCreationInterfaceFactory
     * @param SapOrderItemCollectionFactory $sapOrderItemCollectionFactory
     * @param ShipmentTrackInterfaceFactory $shipmentTrackFactory
     * @param ShipmentTrackResourceInterface $shipmentTrackResource
     * @param SapOrderBatch $sapOrderBatchResource
     * @param SapOrder $sapOrderResource
     * @param OrderManagementInterface $orderManagementInterface
     * @param ItemInterface $itemInterface
     * @param SapOrderShipmentCollectionFactory $sapOrderShipmentCollectionFactory
     * @param ScopeConfigInterface $scopeConfigInterface
     * @param Sdk $sdk
     */
    public function __construct(LoggerInterface $logger,
        ResponseHelper $responseHelper,
        SapOrderBatchCollectionFactory $sapOrderBatchCollectionFactory,
        ShipOrderInterface $shipOrderInterface,
        ShipmentItemCreationInterfaceFactory $shipmentItemCreationInterfaceFactory,
        OrderInterfaceFactory $orderFactory,
        OrderResourceInterface $orderResource,
        ShipmentTrackCreationInterfaceFactory $shipmentTrackCreationInterfaceFactory,
        SapOrderItemCollectionFactory $sapOrderItemCollectionFactory,
        ShipmentTrackInterfaceFactory $shipmentTrackFactory,
        ShipmentTrackResourceInterface $shipmentTrackResource,
        SapOrderBatch $sapOrderBatchResource,
        SapOrder $sapOrderResource,
        OrderManagementInterface $orderManagementInterface,
        ItemInterface $itemInterface,
        SapOrderShipmentCollectionFactory $sapOrderShipmentCollectionFactory,
        ScopeConfigInterface $scopeConfigInterface,
        Sdk $sdk,
        SubscriptionOrderCollectionFactory $subscriptionOrderCollectionFactory)
    {
        $this->_logger = $logger;
        $this->_responseHelper = $responseHelper;
        $this->_sapOrderBatchCollectionFactory = $sapOrderBatchCollectionFactory;
        $this->_shipOrderInterface = $shipOrderInterface;
        $this->_shipmentItemCreationInterfaceFactory = $shipmentItemCreationInterfaceFactory;
        $this->_orderFactory = $orderFactory;
        $this->_orderResource = $orderResource;
        $this->_shipmentTrackCreationInterfaceFactory = $shipmentTrackCreationInterfaceFactory;
        $this->_sapOrderItemCollectionFactory = $sapOrderItemCollectionFactory;
        $this->_shipmentTrackFactory = $shipmentTrackFactory;
        $this->_shipmentTracKResource = $shipmentTrackResource;
        $this->_sapOrderBatchResource = $sapOrderBatchResource;
        $this->_sapOrderResource = $sapOrderResource;
        $this->_orderManagementInterface = $orderManagementInterface;
        $this->_itemInterface = $itemInterface;
        $this->_sapOrderShipmentCollectionFactory = $sapOrderShipmentCollectionFactory;
        $this->_scopeConfigInterface = $scopeConfigInterface;
        $this->_sdk = $sdk;
        $this->_subscriptionOrderCollectionFactory = $subscriptionOrderCollectionFactory;
    }

    /**
     * This function will process the orders
     * that have been set as ready to ship
     *
     * @return string
     */
    public function processShipment()
    {
        if (!$this->getShipmentActive()) {
            return $this->_responseHelper->createResponse(true, "The shipment process is not active.");
        }

        // variables
        $orderStatusResponse = $this->_responseHelper->createResponse(true, "The shipment process completed successfully.");


        // get all of the records in the batch capture table
        // where the shipment has not been completed
        $sapBatchOrders = $this->_sapOrderBatchCollectionFactory->create();
        $sapBatchOrders->addFieldToFilter('is_shipment', ['eq' => true]);
        $sapBatchOrders->addFieldToFilter('shipment_process_date', ['null' => true]);
        $sapBatchOrders->setOrder('order_process_date','DESC');

        if ($this->getShipmentBatchSize()) {
            $sapBatchOrders->setPageSize($this->getShipmentBatchSize());
        }


        // loop through all of the batch capture records that have not been processed
        foreach ($sapBatchOrders as $sapBatchOrder)
        {
            // get the order id
            $orderId = $sapBatchOrder->getData('order_id');

            try {
                // create the shipment request
                $this->createShipmentRequest($orderId);
            }
            catch (\Throwable $ex) {
                // if an error occurs, log it and continue with the batch processing.
                $this->_logger->error($ex->getMessage());
                continue;
            }

            // update the sap order batch
            $this->updateSapBatch($sapBatchOrder, $orderId);
        }

        // send consumer service email
        $this->sendCustomerServiceEmails();

        // return
        return $orderStatusResponse;
    }

    /**
     * Create the Shipment Request to set the order as
     * shipped.
     *
     * @param $orderId
     */
    private function createShipmentRequest($orderId)
    {
        // get the order to get the order items
        /**
         * @var \Magento\Sales\Model\Order $order
         */
        $order = $this->_orderFactory->create();
        $this->_orderResource->load($order, $orderId);

        // determine if this can be shipped
        if ($this->canShip($order))
        {
            // create the list of items
            // initialize the items array
            $items = [];
            $tracks = [];
            $shipTrackingNumbers = [];


            /**
             * @var \SMG\Sap\Model\SapOrder $sapOrder
             */
            $sapOrder = $this->_sapOrderResource->getSapOrderByOrderId($orderId);

            foreach ($sapOrder->getSapOrderTrackingNumbers() as $trackingNumber)
            {
                try {
                    // Do not create the tracking number record if it already exists on the Magento 2 order.
                    $trackingNumberExists = false;
                    $trackingNumberCustomExists = false;
                    if ($trackingNumber == '123456789') {
                       $trackingNumberCustomExists = true;
                    }

                    foreach($order->getTracksCollection() as $track) {
                        if ($track->getTrackNumber() === $trackingNumber) {
                            $trackingNumberExists = true;
                        }
                    }

                    if ($trackingNumberExists) {
                        continue;
                    }

                    // add the ship tracking number to the array
                    $shipTrackingNumbers[] = $trackingNumber;

                    // create the title
                    $shippingTitle = "Federal Express - " . $order->getShippingDescription();

                    /**
                     * @var \Magento\Sales\Api\Data\ShipmentTrackCreationInterface @$shipmentTrackItemCreation
                     */
                    $shipmentTrackItemCreation = $this->_shipmentTrackCreationInterfaceFactory->create();
                    $shipmentTrackItemCreation->setTrackNumber($trackingNumber);
                    $shipmentTrackItemCreation->setTitle($shippingTitle);
                    $shipmentTrackItemCreation->setCarrierCode("fedex");
                    $tracks[] = $shipmentTrackItemCreation;

                    // get the sap order items
                    $sapOrderItems = $this->_sapOrderItemCollectionFactory->create();
                    $sapOrderItems->addFieldToFilter('order_sap_id', ['eq' => $sapOrder->getId()]);

                    // go through each sap order item and create a shipment item if it is related to the given tracking number.
                    /**
                     * @var \SMG\Sap\Model\SapOrderItem $sapOrderItem
                     */
                    foreach ($sapOrderItems as $sapOrderItem) {

                        /**
                         * @var \SMG\Sap\Model\ResourceModel\SapOrderShipment\Collection
                         */
                        $shipments =  $sapOrderItem->getSapOrderShipments($sapOrderItem->getId());
                        $shipments->addFieldToFilter('ship_tracking_number', ['eq' => $trackingNumber]);

                        // Ensure the current sap item is associated with the current tracking number.
                        if (empty($shipments)) {
                            continue;
                        }

                        // get the magento order item for the current sap order item.
                        /**
                         * @var \Magento\Sales\Model\Order\Item $orderItem
                         */
                        if($trackingNumberCustomExists){
                            $orderItem = $order->getAllItems();
                        }else{
                            $orderItem = array_values(array_filter($order->getAllItems(), function ($item) use(&$sapOrderItem) {
                                return $item->getData('sku') == $sapOrderItem->getData('sku');
                            }));
                        }

                        if (empty($orderItem)) {
                            $this->_logger->error('Could not find sku for item' . $sapOrderItem->getId());
                            continue;
                        }
                        // Do not add the item to the track if its quantity is 0.
                        if (floatval($sapOrderItem->getData('confirmed_qty')) < 1) {
                            continue;
                        }
                        // Do not add the item to the track if all items have shipped.
                        if (floatval($orderItem[0]->getData('qty_ordered')) == floatval($orderItem[0]->getData('qty_shipped'))) {
                            $this->_logger->info("Item already shipped: orderItem.item_id=" . $orderItem[0]->getItemId());
                            continue;
                        }

                        /**
                         * @var \Magento\Sales\Api\Data\ShipmentItemCreationInterface $shipmentItemCreation
                         */
                        $shipmentItemCreation = $this->_shipmentItemCreationInterfaceFactory->create();
                        $shipmentItemCreation->setOrderItemId($orderItem[0]->getItemId());
                        $shipmentItemCreation->setQty($sapOrderItem->getData('confirmed_qty'));
                        $items[] = $shipmentItemCreation;
                    }
                    } catch (\Throwable $ex) {

                    $this->_logger->error($ex->getMessage());
                    continue;
                }
            }

            $shipDate = $order->getData('ship_start_date') ?: $order->getData('created_at');
            $isOld = false;
            if ($this->getDaysAfterWhichWeDoNotSendEmail()) {
                $isOld = strtotime($shipDate) < strtotime('-'. $this->getDaysAfterWhichWeDoNotSendEmail() .'days');
            }

            // check to see if the items were added
            if (!empty($items) && !empty($tracks))
            {
                // create shipment status
                if($trackingNumberCustomExists || $isOld){
                  $this->_shipOrderInterface->execute($orderId, $items, false, false, null, $tracks);
                }else{
                   $this->_shipOrderInterface->execute($orderId, $items, true, false, null, $tracks);
                }
                try
                {
                    // Zaius apiKey
                    $this->zaiusApiCall($orderId);
                }
                catch (\Throwable $ex)
                {
                    $this->_logger->error($ex->getMessage());
                    return;
                }
            }
        }
        else
        {
            $this->_logger->error("The order Id " . $orderId . " can not be shipped.  The order status currently is " . $order->getStatus());
        }
    }

    /**
     * @param $sapBatchOrder
     * @param $orderId
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    private function updateSapBatch($sapBatchOrder, $orderId)
    {
        /**
         * @var \SMG\Sap\Model\SapOrder $sapOrder
         */
        $sapOrder = $this->_sapOrderResource->getSapOrderByOrderId($orderId);
        $trackingNumberCustomExists = false;
        foreach ($sapOrder->getSapOrderTrackingNumbers() as $trackingNumber)
        {

            if ($trackingNumber == '123456789') {
                $trackingNumberCustomExists = true;
            }
        }

        // get the order for the given order id
        /**
         * @var \Magento\Sales\Model\Order $order
         */
        $order = $this->_orderFactory->create();
        $this->_orderResource->load($order, $orderId);

        // initialize the values
        $totalQuantityOrdered = $order->getTotalQtyOrdered();
        $totalShipmentQuantity = 0;

        // get the shipments from the order if there are any
        $shipments = $order->getShipmentsCollection();

        // check to see if there are any shipments for this order
        // if so then we need to add the previous order shipment quantity to the
        // current SAP confirmed quantity
        if ($shipments)
        {
            // loop through the shipments and add up the previous shipments to
            // the new confirmed qty received from SAP
            /**
             * @var /Magento/Sales/Model/Order/Shipment $shipment
             */
            foreach ($shipments as $shipment)
            {
                // add the shipment qty to the total order qty
                if ($shipment)
                {
                    // add the shipment order total qty to the total confirmed qty
                    $totalShipmentQuantity += $shipment->getData('total_qty');
                }
            }
        }

        $shipDate = $order->getData('ship_start_date') ?: $order->getData('created_at');
        $isOld = false;
        if ($this->getDaysAfterWhichWeMarkAShipmentFailed()) {
            $isOld = strtotime($shipDate) < strtotime('-'. $this->getDaysAfterWhichWeMarkAShipmentFailed() .'days');
        }
        // if the total ordered equals the total shipped then update the sap batch order
        if($totalShipmentQuantity >= $totalQuantityOrdered || in_array($order->getData('state'), ['canceled', 'closed', 'complete']) || $isOld)
        {
            // get the current date
            $today = date('Y-m-d H:i:s');

            if ($isOld) {
                $today = '0000-00-00 00:00:00';
            }

            // set the capture date
            $sapBatchOrder->setData('shipment_process_date', $today);

            // save the data
            $this->_sapOrderBatchResource->save($sapBatchOrder);

            // add the order id to the array to send email
            // to customer service
            if (!$trackingNumberCustomExists)
            {
                $this->_customerServiceEmailIds[] = $orderId;
            }
        }
    }

    /**
     * Sends email to customer service for the orders
     */
    private function sendCustomerServiceEmails()
    {
        // if there is something to send then send the emails
        if (count($this->_customerServiceEmailIds) > 0)
        {
            // add the items to the item interface
            $this->_itemInterface->setOrderIds($this->_customerServiceEmailIds);

            // send the email
            $this->_orderManagementInterface->notifyShipmentOrdersServiceTeam($this->_itemInterface);
        }
    }

    private function zaiusApiCall($orderId)
    {
       $zaiusstatus = false;

       // get order
       $order = $this->_orderFactory->create();

       // load order from orderId
       $this->_orderResource->load($order, $orderId);

       // get send shipment status
       $shipmentstatus = $this->getSendShipmentStatus();

       // get subcription order details
        $subscriptionOrders = $this->_subscriptionOrderCollectionFactory->create();
        $subscriptionOrders
                ->setOrder('ship_start_date', 'asc')
                ->addFieldToFilter('sales_order_id', $orderId);
        $this->_subscriptionOrders = $subscriptionOrders;

       // check isSubcription and shipmentstatus
       if ($order->isSubscription() && $shipmentstatus)
        {
            // call getsdkclient function
            $zaiusClient = $this->_sdk->getSdkClient();

            // get customer email
            $email = $order->getCustomerEmail();

            // get order increment Id
            $shipmentId = $order->getIncrementId();

            $startdate = '';
            $enddate = '';
            $product_order = '';
            if($this->_subscriptionOrders){
                foreach($this->_subscriptionOrders as $orders){
                    $startdate = strtotime($orders->getApplicationStartDate());
                    $enddate   = strtotime($orders->getApplicationEndDate());
                    $product_order =  $this->getProductOrder($orders->getSubscriptionEntityId(), $orderId);
                }
            }

            foreach ($order->getAllVisibleItems() as $_item) {
            $productid = $_item->getProductId();
                      // take event as a array and add parameters
            $event = array();
            $event['type'] = 'product';
            $event['action'] = 'shipped';
            $event['identifiers'] = ['email'=>$email];
            $event['data'] = ['product_id'=>$productid, 'shipment_id'=>$shipmentId, 'magento_store_view'=>'Default Store View','applicationstartdate'=>$startdate,'applicationenddate'=>$enddate,'product_order'=>$product_order];

            // get postevent function
            $zaiusstatus = $zaiusClient->postEvent($event);

                 // check return values from the postevent function
                if($zaiusstatus)
                {
                    $this->_logger->info("The order Id " . $orderId . " with product Id " . $productid . " is passed successfully to zaius."); //saved in var/log/system.log
                }
                else
                {
                    $this->_logger->info("The order Id " . $orderId . " with product id " . $productid . " is failed to zaius."); //saved in var/log/system.log
                }
            }
        }
    }

    /**
     * @param \Magento\Store\Model\Store|int|null $store
     * @return bool
     */
    private function getSendShipmentStatus()
    {
        return $this->_scopeConfigInterface->getValue('zaius_engage/status/send_shipment_status', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    private function getShipmentActive()
    {
        return $this->_scopeConfigInterface->getValue('smg/shipment/active', \Magento\Store\Model\ScopeInterface::SCOPE_STORE) == 'false'?false:true;
    }

    private function getDaysAfterWhichWeDoNotSendEmail()
    {
        return $this->_scopeConfigInterface->getValue('smg/shipment/email_days', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    private function getDaysAfterWhichWeMarkAShipmentFailed()
    {
        return $this->_scopeConfigInterface->getValue('smg/shipment/mark_failed_days', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    private function getShipmentBatchSize()
    {
        return $this->_scopeConfigInterface->getValue('smg/shipment/batch_size', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    private function getProductOrder($subscription_entity_id, $sales_order_id)
    {
      $subscriptionOrders = $this->_subscriptionOrderCollectionFactory->create();
      $subscriptionOrders
                ->setOrder('entity_id', 'asc')
                ->addFieldToFilter('subscription_entity_id', $subscription_entity_id);
      $this->_subscriptionOrders = $subscriptionOrders;
      $i = 0;
     foreach($this->_subscriptionOrders as $subcriptionorders){
             if($subcriptionorders->getSalesOrderId() == $sales_order_id)
             {
              return $i;
              break;
             }
             $i++;
        }
      return $i;
    }

    /*
     * Retrieve order shipment availability
     * @param \Magento\Sales\Model\Order $order
     * @return bool
     */
    public function canShip($order)
    {
        if ($order->canUnhold() || $order->isPaymentReview()) {
            return false;
        }

        if ($order->getIsVirtual() || $order->isCanceled()) {
            return false;
        }

        if ($order->getActionFlag(self::ACTION_FLAG_SHIP) === false) {
            return false;
        }

        foreach ($order->getAllItems() as $item) {
            if (!$item->getIsVirtual() &&
                !$item->getLockedDoShip() && $item->getQtyRefunded() != $item->getQtyOrdered()) {
                return true;
            }
        }
        return false;
    }
}
