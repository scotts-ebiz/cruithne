<?php

namespace SMG\Api\Helper;

use Magento\Framework\App\Config\ScopeConfigInterface as ScopeConfigInterface;
use Magento\Sales\Api\Data\OrderInterfaceFactory;
use Magento\Sales\Api\Data\ShipmentItemCreationInterfaceFactory;
use Magento\Sales\Api\Data\ShipmentTrackCreationInterfaceFactory;
use Magento\Sales\Api\ShipOrderInterface;
use Magento\Sales\Model\Spi\OrderResourceInterface;

use Psr\Log\LoggerInterface;

use SMG\Sap\Model\SapOrderFactory;
use SMG\Sap\Model\SapOrderItemFactory;
use SMG\Sap\Model\SapOrderBatchFactory;
use SMG\Sap\Model\SapOrderShipmentFactory;
use SMG\Sap\Model\ResourceModel\SapOrder;
use SMG\Sap\Model\ResourceModel\SapOrderBatch;
use SMG\Sap\Model\ResourceModel\SapOrderItem as SapOrderItemResource;
use SMG\Sap\Model\ResourceModel\SapOrderItem\CollectionFactory as SapOrderItemCollectionFactory;
use SMG\Sap\Model\ResourceModel\SapOrderShipment as SapOrderShipmentResource;
use SMG\SubscriptionApi\Model\ResourceModel\SubscriptionOrder\CollectionFactory as SubscriptionOrderCollectionFactory;

use Zaius\Engage\Helper\Sdk as Sdk;

class ManualShipmentHelper
{
    /**
     * @var LoggerInterface
     */
    protected $_logger;

    /**
     * @var ResponseHelper
     */
    protected $_responseHelper;

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
     * @var SapOrderBatch
     */
    protected $_sapOrderBatchResource;

    /**
     * @var SapOrder
     */
    protected $_sapOrderResource;

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
     * @var SapOrderBatchFactory
     */
    protected $_sapOrderBatchFactory;
    
    /**
     * @var SapOrderFactory
     */
    protected $_sapOrderFactory;
    
    /**
     * @var SapOrderItemResource
     */
    protected $_sapOrderItemResource;
    
    /**
     * @var SapOrderItemFactory
     */
    protected $_sapOrderItemFactory;
    
    /**
     * @var SapOrderShipmentFactory
     */
    protected $_sapOrderShipmentFactory;
    
    /**
     * @var SapOrderShipmentResource
     */
    protected $_sapOrderShipmentResource;
    
    /**
     * ManualShipmentHelper constructor
     * 
     * @param LoggerInterface $logger
     * @param ResponseHelper $responseHelper
     * @param ShipOrderInterface $shipOrderInterface
     * @param ShipmentItemCreationInterfaceFactory $shipmentItemCreationInterfaceFactory
     * @param OrderInterfaceFactory $orderFactory
     * @param OrderResourceInterface $orderResource
     * @param ShipmentTrackCreationInterfaceFactory $shipmentTrackCreationInterfaceFactory
     * @param SapOrderItemCollectionFactory $sapOrderItemCollectionFactory
     * @param SapOrderBatch $sapOrderBatchResource
     * @param SapOrder $sapOrderResource
     * @param ScopeConfigInterface $scopeConfigInterface
     * @param Sdk $sdk
     * @param SubscriptionOrderCollectionFactory $subscriptionOrderCollectionFactory
     * @param SapOrderBatchFactory $sapOrderBatchFactory
     * @param SapOrderFactory $sapOrderFactory
     * @param SapOrderItemResource $sapOrderItemResource
     * @param SapOrderItemFactory $sapOrderItemFactory
     * @param SapOrderShipmentFactory $sapOrderShipmentFactory
     * @param SapOrderShipmentResource $sapOrderShipmentResource
     */
    public function __construct(LoggerInterface $logger,
        ResponseHelper $responseHelper,
        ShipOrderInterface $shipOrderInterface,
        ShipmentItemCreationInterfaceFactory $shipmentItemCreationInterfaceFactory,
        OrderInterfaceFactory $orderFactory,
        OrderResourceInterface $orderResource,
        ShipmentTrackCreationInterfaceFactory $shipmentTrackCreationInterfaceFactory,
        SapOrderItemCollectionFactory $sapOrderItemCollectionFactory,
        SapOrderBatch $sapOrderBatchResource,
        SapOrder $sapOrderResource,
        ScopeConfigInterface $scopeConfigInterface,
        Sdk $sdk,
        SubscriptionOrderCollectionFactory $subscriptionOrderCollectionFactory,
    	SapOrderBatchFactory $sapOrderBatchFactory,
    	SapOrderFactory $sapOrderFactory,
    	SapOrderItemResource $sapOrderItemResource,
    	SapOrderItemFactory $sapOrderItemFactory,
    	SapOrderShipmentFactory $sapOrderShipmentFactory,
        SapOrderShipmentResource $sapOrderShipmentResource)
    {
        $this->_logger = $logger;
        $this->_responseHelper = $responseHelper;
        $this->_shipOrderInterface = $shipOrderInterface;
        $this->_shipmentItemCreationInterfaceFactory = $shipmentItemCreationInterfaceFactory;
        $this->_orderFactory = $orderFactory;
        $this->_orderResource = $orderResource;
        $this->_shipmentTrackCreationInterfaceFactory = $shipmentTrackCreationInterfaceFactory;
        $this->_sapOrderItemCollectionFactory = $sapOrderItemCollectionFactory;
        $this->_sapOrderBatchResource = $sapOrderBatchResource;
        $this->_sapOrderResource = $sapOrderResource;
        $this->_scopeConfigInterface = $scopeConfigInterface;
        $this->_sdk = $sdk;
        $this->_subscriptionOrderCollectionFactory = $subscriptionOrderCollectionFactory;
        $this->_sapOrderBatchFactory = $sapOrderBatchFactory;
        $this->_sapOrderFactory = $sapOrderFactory;
        $this->_sapOrderItemResource = $sapOrderItemResource;
        $this->_sapOrderItemFactory = $sapOrderItemFactory;
        $this->_sapOrderShipmentFactory = $sapOrderShipmentFactory;
        $this->_sapOrderShipmentResource = $sapOrderShipmentResource;
    }

    /**
     * This function will process the orders
     * sent in the json file
     *
     * @return string
     */
    public function processShipment($requestData)
    {
        // variables
        $manualShipmentResponse = $this->_responseHelper->createResponse(true, "The manual shipment process completed successfully.");
        
        // make sure that we were given something from the request
        if (!empty($requestData))
        {
        	// loop through the orders that were sent via the JSON file
        	foreach ($requestData as $inputOrder)
        	{
        		// get the increment id
        		$incrementId = $inputOrder['OrderNumber'];
        		if (!empty($incrementId))
        		{
        			// get the order to get the order items
        			/**
        			 * @var \Magento\Sales\Model\Order $order
        			 */
        			$order = $this->_orderFactory->create();
        			$this->_orderResource->load($order, $incrementId, 'increment_id');
        				
        			// make sure that there is an order
        			if (!empty($order) && !empty($order->getId()))
        			{
        				try
        				{
		        			// create a shipment request and update sap tables
		        			$this->processShipmentData($order, $inputOrder);
		        			
			        		// send to zaius
		        			// Zaius apiKey
		        			$this->zaiusApiCall($order);
		        		}
		        		catch (\Exception $e)
		        		{
		        			$this->_logger->error($e->getMessage());
		        			$manualShipmentResponse = $this->_responseHelper->createResponse(true, "The manual shipment process completed successfully.  However, there were some errors that occurred. Please check the logs for errors.");
		        		}
        			}
        			else
        			{
        				$this->_logger->error("MANUAL_SHIPMENT_HELPER_ERROR - SMG\Api\Helper\ManualShipmentHelper - Order not found for order number - " . $incrementId);
        				$manualShipmentResponse = $this->_responseHelper->createResponse(true, "The manual shipment process completed successfully.  However, there were some errors that occurred. Please check the logs for errors.");
        			}
	        	}
	        	else
	        	{
	        		$this->_logger->error("MANUAL_SHIPMENT_HELPER_ERROR - SMG\Api\Helper\ManualShipmentHelper - Missing Increment ID provided.");
	        		$manualShipmentResponse = $this->_responseHelper->createResponse(true, "The manual shipment process completed successfully.  However, there were some errors that occurred. Please check the logs for errors.");
				}
        	}
        }
        else
        {
        	// log the error
        	$this->_logger->error("MANUAL_SHIPMENT_HELPER_ERROR - SMG\Api\Helper\ManualShipmentHelper - Nothing was provided to process.");
        
        	$manualShipmentResponse = $this->_responseHelper->createResponse(false, 'Nothing was provided to process.');
        }

        // return
        return $manualShipmentResponse;
    }

    /**
     * Create the Shipment Request to set the order as
     * shipped. As well as updates Sap table info.
     *
     * @param \Magento\Sales\Model\Order $order
     * @param $inputOrder
     * 
     */
    private function processShipmentData($order, $inputOrder)
    {
    	try
    	{
    		// get the current date
    		$today = date('Y-m-d H:i:s');
    		
    		// update sap order batch
    		$this->updateSapOrderBatch($order->getId(), $today);
    		
	        // determine if this can be shipped
	        if ($order->canShip())
	        {
	        	// update the sap order data
	        	$sapOrderId = $this->updateSapOrder($order->getId(), $today);
	        	
	            // get the list of shipment tracking numbers
	            $shipTrackingNumbers = $inputOrder['ShipmentTrackingNumbers'];
	            if (!empty($shipTrackingNumbers))
	            {
	            	// convert the string into an array
	            	$shipTrackingNumbersArray = explode(',', $shipTrackingNumbers);
	            	
	            	// create the list of items
	            	// initialize the items array
	            	$items = [];
	            	$tracks = [];
	            	$itemCount = 0;
	            	
	            	// loop through the items
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
	            		$sapOrderItems->addFieldToFilter('order_sap_id', ['eq' => $sapOrderId]);
	            		$sapOrderItems->addFieldToFilter('sku', ['eq' => $orderItem->getSku()]);
	            		
	            		/**
	            		 * @var \SMG\Sap\Model\SapOrderItem $sapOrderItem
	            		 */
	            		$sapOrderItem = $this->_sapOrderItemFactory->create();
	            		
	            		// if the order item doesn't exists then create it
	            		if ($sapOrderItems->count() > 0)
	            		{
	            			// update the first item
	            			/**
	            			 * @var \SMG\Sap\Model\SapOrderItem $sapOrderItem
	            			 */
	            			$sapOrderItem = $sapOrderItems->getFirstItem();
	            			
	            			// update the sap item data
	            			$sapOrderItem->setData('order_status', 'order_shipped');
    						$sapOrderItem->setData('updated_at', $today);
	            		}
	            		else
	            		{
	            			// set the sap item data
	            			$sapOrderItem->setData('order_sap_id', $sapOrderId);
	            			$sapOrderItem->setData('sap_order_status', 'A');
	            			$sapOrderItem->setData('order_status', 'order_shipped');
	            			$sapOrderItem->setData('sku', $orderItem->getData('sku'));
	            			$sapOrderItem->setData('sku_description', $orderItem->getData('name'));
	            			$sapOrderItem->setData('qty', $orderItem->getData('qty_ordered'));
	            			$sapOrderItem->setData('confirmed_qty', $orderItem->getData('qty_ordered'));
	            			$sapOrderItem->setData('created_at', $today);
	            			$sapOrderItem->setData('updated_at', $today);
	            		}
	            		
	            		// save the sap order item
	            		$this->_sapOrderItemResource->save($sapOrderItem);
	            		
	            		$this->_logger->debug("itemCount - " . $itemCount);
	            		
	            		// get the ship tracking number
	            		if ($itemCount < count($shipTrackingNumbersArray))
	            		{
	            			$shipTrackingNumber = $shipTrackingNumbersArray[$itemCount];
	            		}
	            		else
	            		{
	            			$shipTrackingNumber = $shipTrackingNumbersArray[0];
	            		}
	            		
	            		$this->_logger->debug("shipTrackingNumber - " . $shipTrackingNumber);
	            		
	            		// load the sap shipment data
	            		$sapShipment = $this->_sapOrderShipmentFactory->create();
	            		$this->_sapOrderShipmentResource->load($sapShipment, $sapOrderItem->getId(), 'order_sap_item_id');
	            		
	            		// update the status and update date
	            		$sapShipment->setData('ship_tracking_number', $shipTrackingNumber);
	            		$sapShipment->setData('updated_at', $today);
	            		 
	            		// check to see if this order existed or if we are updating now
	            		if (empty($sapShipment->getId()))
	            		{
	            			// update the status
	            			$sapShipment->setData('order_sap_item_id', $sapOrderItem->getId());
	            			$sapShipment->setData('qty', $orderItem->getData('qty_ordered'));
	            			$sapShipment->setData('confirmed_qty', $orderItem->getData('qty_ordered'));
	            			$sapShipment->setData('created_at', $today);
	            			$sapShipment->setData('updated_at', $today);
	            		}
	            		 
	            		// save the changes
	            		$this->_sapOrderShipmentResource->save($sapShipment);
	            		
	            		// increment the item count
	            		$itemCount = $itemCount + 1;
	            	}
	            	
	            	// loop through all of the ship tracking numbers
	            	foreach ($shipTrackingNumbersArray as $shipTrackingNumber)
	            	{
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
	            	
	            	// check to see if the items were added
	            	if (!empty($items) && !empty($tracks))
	            	{
	            		// create shipment status
	            		$this->_shipOrderInterface->execute($order->getId(), $items, false, false, null, $tracks);
	            	}
	            }
	            else
	            {
	            	throw new \Exception("MANUAL_SHIPMENT_HELPER_ERROR - SMG\Api\Helper\ManualShipmentHelper - The order Id " . $order->getData('increment_id') . " can not be shipped.  There are no ship tracking numbers.");
	            }
			}
	        else
	        {
	            throw new \Exception("MANUAL_SHIPMENT_HELPER_ERROR - SMG\Api\Helper\ManualShipmentHelper - The order Id " . $order->getData('increment_id') . " can not be shipped.  The order status currently is " . $order->getStatus());
	        }
    	}
    	catch (\Exception $e)
    	{
    		throw $e;
    	}
    }

    /**
     * Update the sap order batch table so the shipment will not be processed again for
     * this order
     * 
     * @param $orderId
     * @param $today
     */
    private function updateSapOrderBatch($orderId, $today)
    {
    	// get the sap order batch table info
    	/**
    	 * @var \SMG\Sap\Model\SapOrderBatch $sapBatchOrder
    	 */
    	$sapOrderBatch = $this->_sapOrderBatchFactory->create();
    	$this->_sapOrderBatchResource->load($sapOrderBatch, $orderId, 'order_id');
    	
    	// set the capture date
    	$sapOrderBatch->setData('is_shipment', 1);
    	$sapOrderBatch->setData('shipment_process_date', $today);
    	
    	// save the data
    	$this->_sapOrderBatchResource->save($sapOrderBatch);
    }
    
    /**
     * This will update or insert into the sap order table
     * 
     * @param $orderId
     * @param $today
     * @return int
     */
    private function updateSapOrder($orderId, $today)
    {
    	// get the sap order table info
    	/**
    	 * @var \SMG\Sap\Model\SapOrder $sapOrder
    	 */
    	$sapOrder = $this->_sapOrderFactory->create();
    	$this->_sapOrderResource->load($sapOrder, $orderId, 'order_id');
    	
    	// update the status and update date
    	$sapOrder->setData('order_status', 'order_shipped');
    	$sapOrder->setData('updated_at', $today);
    	
    	// check to see if this order existed or if we are updating now
    	if (empty($sapOrder->getId()))
    	{
    		// update the status
    		$sapOrder->setData('order_id', $orderId);
    		
    		// update the status
    		$sapOrder->setData('sap_order_status', 'A');
    		
    		// update the status
    		$sapOrder->setData('created_at', $today);
    	}
    	
    	// save the changes
    	$this->_sapOrderResource->save($sapOrder);
    	
    	// return the sap order id
    	return $sapOrder->getId();
    }
    
    /**
     * Send the information to zaius for shipment
     * 
     * @param \Magento\Sales\Model\Order $order
     */
    private function zaiusApiCall($order)
    {
       	$zaiusstatus = false;    

       	// get the order id
       	$orderId = $order->getId();
       
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
            if($this->_subscriptionOrders)
            {
            	foreach($this->_subscriptionOrders as $orders)
            	{
                	$startdate = strtotime($orders->getApplicationStartDate());
                    $enddate = strtotime($orders->getApplicationEndDate());
                    $product_order = $this->getProductOrder($orders->getSubscriptionEntityId(), $orderId);
                }
            }
            
            foreach ($order->getAllVisibleItems() as $_item)
            {
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
                    $this->_logger->info("The order Id " . $orderId . " with product Id " . $productid . " is passed successfully to zaius.");
            	}
            	else
            	{
            		$this->_logger->info("The order Id " . $orderId . " with product id " . $productid . " is failed to zaius.");
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
    
    /**
     * Get Product Order Information
     * 
     * @param $subscription_entity_id
     * @param $sales_order_id
     */
	private function getProductOrder($subscription_entity_id, $sales_order_id)
    {
    	$subscriptionOrders = $this->_subscriptionOrderCollectionFactory->create();
      	$subscriptionOrders
      		->setOrder('entity_id', 'asc')
            ->addFieldToFilter('subscription_entity_id', $subscription_entity_id); 
      	
      	$this->_subscriptionOrders = $subscriptionOrders;
      
      	$i = 0;
     	foreach($this->_subscriptionOrders as $subcriptionorders)
     	{
        	if($subcriptionorders->getSalesOrderId() == $sales_order_id)
            {
            	return $i;     
              	break; 
            }
            $i++;
		}           
      
		return $i;
	}
}
