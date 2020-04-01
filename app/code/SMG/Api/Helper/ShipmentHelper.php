<?php

namespace SMG\Api\Helper;

use Magento\Sales\Api\Data\OrderInterfaceFactory;
use Magento\Sales\Api\Data\ShipmentTrackInterfaceFactory;
use Magento\Sales\Api\Data\ShipmentItemCreationInterfaceFactory;
use Magento\Sales\Api\Data\ShipmentTrackCreationInterfaceFactory;
use Magento\Sales\Api\ShipOrderInterface;
use Magento\Sales\Model\Spi\OrderResourceInterface;
use Magento\Sales\Model\Spi\ShipmentTrackResourceInterface;
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

class ShipmentHelper
{
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
        Sdk $sdk)
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
    }

    /**
     * This function will process the orders
     * that have been set as ready to ship
     *
     * @return string
     */
    public function processShipment()
    {
        // variables
        $orderStatusResponse = $this->_responseHelper->createResponse(true, "The shipment process completed successfully.");

        // get all of the records in the batch capture table
        // where the shipment has not been completed and the order was not unauthorized
        $sapBatchOrders = $this->_sapOrderBatchCollectionFactory->create();
        $sapBatchOrders->addFieldToFilter('is_shipment', ['eq' => true]);
        $sapBatchOrders->addFieldToFilter('shipment_process_date', ['null' => true]);
        $sapBatchOrders->addFieldToFilter('is_unauthorized', ['neq' => true]);

        // loop through all of the batch capture records that have not been processed
        foreach ($sapBatchOrders as $sapBatchOrder)
        {
            // get the order id
            $orderId = $sapBatchOrder->getData('order_id');

            // create the shipment request
            $this->createShipmentRequest($orderId);
            
            // check the status
          if ($this->wasShipmentSuccessful($orderId))
           {
              // update the sap order batch
            $this->updateSapBatch($sapBatchOrder, $orderId);    

               // Zaius apiKey
            $this->zaiusApiCall($orderId);
           }
            
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
        if ($order->canShip())
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

            /**
             * @var \Magento\Sales\Model\Order\Item $orderItem
             */
            foreach ($order->getAllItems() as $orderItem)
            {
                /**
                 * @var \Magento\Sales\Api\Data\ShipmentItemCreationInterface $shipmentItemCreation
                 */
                $shipmentItemCreation = $this->_shipmentItemCreationInterfaceFactory->create();
                $shipmentItemCreation->setOrderItemId($orderItem->getItemId());
                $shipmentItemCreation->setQty($orderItem->getQtyOrdered());
                $items[] = $shipmentItemCreation;

                // get the sap order item
                $sapOrderItems = $this->_sapOrderItemCollectionFactory->create();
                $sapOrderItems->addFieldToFilter('order_sap_id', ['eq' => $sapOrder->getId()]);
                $sapOrderItems->addFieldToFilter('sku', ['eq' => $orderItem->getSku()]);

                // get the first item from the collection.  there should only be one
                // item
                /**
                 * @var \SMG\Sap\Model\SapOrderItem $sapOrderItem
                 */
                foreach ($sapOrderItems as $sapOrderItem)
                {
                    if (!empty($sapOrderItem))
                    {
                        // get the sap order shipment
                        $sapOrderShipments = $this->_sapOrderShipmentCollectionFactory->create();
                        $sapOrderShipments->addFieldToFilter('order_sap_item_id', ['eq' => $sapOrderItem->getId()]);
                        $sapOrderShipments->addFieldToFilter('ship_tracking_number', ['notnull' => true]);

                        // loop through the order shipment
                        /**
                         * @var \SMG\Sap\Model\SapOrderShipment $sapOrderShipment
                         */
                        foreach ($sapOrderShipments as $sapOrderShipment)
                        {
                            // get the shipping tracking number
                            $shipTrackingNumber = $sapOrderShipment->getData('ship_tracking_number');

                            // check if the ship tracking number exists in the array as we don't want to add
                            // the same ship tracking number twice
                            if (!in_array($shipTrackingNumber, $shipTrackingNumbers))
                            {
                                // add the ship tracking number to the array
                                $shipTrackingNumbers[] = $shipTrackingNumber;

                                // create the title
                                $shippingTitle = "Federal Express - " . $order->getShippingDescription();

                                /**
                                 * @var \Magento\Sales\Api\Data\ShipmentTrackCreationInterface @$shipmentTrackItemCreation
                                 */
                                $shipmentTrackItemCreation = $this->_shipmentTrackCreationInterfaceFactory->create();
                                $shipmentTrackItemCreation->setTrackNumber($shipTrackingNumber);
                                $shipmentTrackItemCreation->setTitle($shippingTitle);
                                $shipmentTrackItemCreation->setCarrierCode("fedex");
                                $tracks[] = $shipmentTrackItemCreation;
                            }
                        }
                    }
                }
            }

            // check to see if the items were added
            if (!empty($items) && !empty($tracks))
            {
                // create shipment status
                $this->_shipOrderInterface->execute($orderId, $items, true, false, null, $tracks);
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
            // get the current date
            $today = date('Y-m-d H:i:s');

            // set the capture date
            $sapBatchOrder->setData('shipment_process_date', $today);

            // save the data
            $this->_sapOrderBatchResource->save($sapBatchOrder);

            // add the order id to the array to send email
            // to customer service
            $this->_customerServiceEmailIds[] = $orderId;
    }

    /**
     * Determine if the update was successful
     *
     * @param $orderId
     * @return bool
     */
    private function wasShipmentSuccessful($orderId)
    {
        // set the success flag
        $isBatchCaptureSuccess = false;

        // load the shipment track data
        /**
         * @var \Magento\Sales\Model\Order\Shipment\Track $shipmentTrack
         */
        $shipmentTrack = $this->_shipmentTrackFactory->create();
        $this->_shipmentTracKResource->load($shipmentTrack, $orderId, 'order_id');

        // check to see if the user is loaded
        $trackingNumber = $shipmentTrack->getTrackNumber();
        if (isset($trackingNumber))
        {
            $isBatchCaptureSuccess = true;
        }

        // return
        return $isBatchCaptureSuccess;
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

       // check isSubcription and shipmentstatus
       if ($order->isSubscription() && $shipmentstatus)
        {
            // call getsdkclient function
            $zaiusClient = $this->_sdk->getSdkClient();

            // get customer email
            $email = $order->getCustomerEmail();
            
            // get order increment Id
            $shipmentId = $order->getIncrementId();
            
            foreach ($order->getAllVisibleItems() as $_item) {
            $productid = $_item->getProductId();
                      // take event as a array and add parameters
            $event = array();
            $event['type'] = 'product';
            $event['action'] = 'shipped';
            $event['identifiers'] = ['email'=>$email];
            $event['data'] = ['product_id'=>$productid, 'shipment_id'=>$shipmentId, 'magento_store_view'=>'Default Store View'];

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
}
