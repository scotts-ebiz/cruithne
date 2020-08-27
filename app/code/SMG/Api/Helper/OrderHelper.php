<?php
/**
 * User: cnixon
 * Date: 5/14/19
 */
namespace SMG\Api\Helper;

use Magento\Sales\Model\Order\Creditmemo;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\Order\Item;
use Magento\Sales\Model\Order\ItemFactory;
use Magento\Sales\Model\ResourceModel\Order as OrderResource;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Magento\Sales\Model\ResourceModel\Order\Creditmemo\CollectionFactory as CreditMemoCollectionFactory;
use Magento\Sales\Model\ResourceModel\Order\Item as ItemResource;
use Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory as OrderItemCollectionFactory;

use Psr\Log\LoggerInterface;

use SMG\CreditReason\Model\CreditReasonCodeFactory;
use SMG\CreditReason\Model\ResourceModel\CreditReasonCode as CreditReasonCodeReource;
use SMG\CreditReason\Model\ResourceModel\CreditReasonCode\CollectionFactory as CreditReasonCodeCollectionFactory;
use SMG\OfflineShipping\Model\ResourceModel\ShippingConditionCode as ShippingConditionCodeResource;
use SMG\OfflineShipping\Model\ShippingConditionCodeFactory;
use SMG\OrderDiscount\Helper\Data as DiscountHelper;
use SMG\SubscriptionApi\Model\ResourceModel\Subscription as SubscriptionResource;
use SMG\SubscriptionApi\Model\SubscriptionFactory;

class OrderHelper
{
    // Output JSON file constants
    const ORDER_NUMBER = 'OrderNumber';
    const SUBSCRIPTION_ORDER = 'SubscriptOrder';
    const SUBSCRIPTION_TYPE = 'SubscriptType';
    const DATE_PLACED = 'DatePlaced';
    const SAP_DELIVERY_DATE = 'SAPDeliveryDate';
    const CUSTOMER_NAME = 'CustomerName';
    const ADDRESS_STREET = 'CustomerShippingAddressStreet';
    const ADDRESS_CITY = 'CustomerShippingAddressCity';
    const ADDRESS_STATE = 'CustomerShippingAddressState';
    const ADDRESS_ZIP = 'CustomerShippingAddressZip';
    const SMG_SKU = 'SMGSKU';
    const WEB_SKU = 'WebSKU';
    const QUANTITY = 'Quantity';
    const UNIT = 'Unit';
    const UNIT_PRICE = 'UnitPrice';
    const GROSS_SALES = 'GrossSales';
    const SHIPPING_AMOUNT = 'ShippingAmount';
    const EXEMPT_AMOUNT = 'ExemptAmount';
    const HDR_DISC_FIXED_AMOUNT = 'HdrDiscFixedAmount';
    const HDR_DISC_PERC = 'HdrDiscPerc';
    const HDR_DISC_COND_CODE = 'HdrDiscCondCode';
    const HDR_SURCH_FIXED_AMOUNT = 'HdrSurchFixedAmount';
    const HDR_SURCH_PERC = 'HdrSurchPerc';
    const HDR_SURCH_COND_CODE = 'HdrSurchCondCode';
    const DISCOUNT_AMOUNT = 'DiscountAmount';
    const SUBTOTAL = 'Subtotal';
    const TAX_RATE = 'TaxRate';
    const SALES_TAX = 'SalesTax';
    const INVOICE_AMOUNT = 'InvoiceAmount';
    const DELIVERY_LOCATION = 'DeliveryLocation';
    const EMAIL = 'CustomerEmail';
    const PHONE = 'CustomerPhone';
    const DELIVERY_WINDOW = 'DeliveryWindow';
    const SHIPPING_CONDITION = 'ShippingCondition';
    const WEBSITE_URL = 'WebsiteURL';
    const CREDIT_AMOUNT = 'CreditAmount';
    const CR_DR_RE_FLAG = 'CR/DR/RE/Flag';
    const SAP_BILLING_DOC_NUMBER = 'ReferenceDocNum';
    const CREDIT_COMMENT = 'CreditComment';
    const ORDER_REASON = 'OrderReason';
    const DISCOUNT_CONDITION_CODE = 'DiscCondCode';
    const SURCH_CONDITION_CODE = 'SurchCondCode';
    const DISCOUNT_FIXED_AMOUNT = 'DiscFixedAmt';
    const SURCH_FIXED_AMOUNT = 'SurchFixedAmt';
    const DISCOUNT_PERCENT_AMOUNT = 'DiscPercAmt';
    const SURCH_PERCENT_AMOUNT = 'SurchPercAmt';
    const DISCOUNT_REASON = 'ReasonCode';
    const SUBSCRIPTION_SHIP_START = 'SubscriptLineShipStart';
    const SUBSCRIPTION_SHIP_END = 'SubscriptLineShipEnd';

    // Return Credit Memo Codes
    const CUSTOMER_REFUSAL_CODE = '014';
    const BUYBACK_CODE = '037';
    const RECOVERY_RECALL_CODE = '038';

    /**
     * @var LoggerInterface
     */
    protected $_logger;

    /**
     * @var CreditReasonCodeFactory
     */
    protected $_creditReasonCodeFactory;

    /**
     * @var CreditReasonCodeResource
     */
    protected $_creditReasonCodeResource;

    /**
     * @var CreditReasonCodeCollectionFactory
     */
    protected $_creditReasonCodeCollectionFactory;

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
     * @var DiscountHelper
     */
    protected $_discountHelper;

    /**
     * @var SubscriptionResource
     */
    protected $_subscriptionResource;

    /**
     * @var array
     */
    protected $_masterSubscriptionIds = [];

    /**
     * @var SubscriptionFactory
     */
    protected $_subscriptionFactory;
    
    /**
     * @var CreditMemoCollectionFactory
     */
    protected $_creditMemoCollectionFactory;
    
    public function __construct(LoggerInterface $logger,
        CreditReasonCodeFactory $creditReasonCodeFactory,
        CreditReasonCodeReource $_creditReasonCodeResource,
        CreditReasonCodeCollectionFactory $creditReasonCodeCollectionFactory,
        ResponseHelper $responseHelper,
        OrderCollectionFactory $orderCollectionFactory,
        OrderItemCollectionFactory $orderItemCollectionFactory,
        ShippingConditionCodeFactory $shippingConditionCodeFactory,
        ShippingConditionCodeResource $shippingConditionCodeResource,
        OrderFactory $orderFactory,
        OrderResource $orderResource,
        ItemFactory $itemFactory,
        ItemResource $itemResource,
        DiscountHelper $discountHelper,
        SubscriptionResource $subscriptionResource,
        SubscriptionFactory $subscriptionFactory,
    	CreditMemoCollectionFactory $creditMemoCollectionFactory)
    {
        $this->_logger = $logger;
        $this->_creditReasonCodeFactory = $creditReasonCodeFactory;
        $this->_creditReasonCodeResource = $_creditReasonCodeResource;
        $this->_creditReasonCodeCollectionFactory = $creditReasonCodeCollectionFactory;
        $this->_responseHelper = $responseHelper;
        $this->_orderCollectionFactory = $orderCollectionFactory;
        $this->_orderItemCollectionFactory = $orderItemCollectionFactory;
        $this->_shippingConditionCodeFactory = $shippingConditionCodeFactory;
        $this->_shippingConditionCodeResource = $shippingConditionCodeResource;
        $this->_orderFactory = $orderFactory;
        $this->_orderResource = $orderResource;
        $this->_itemFactory = $itemFactory;
        $this->_itemResource = $itemResource;
        $this->_discountHelper = $discountHelper;
        $this->_subscriptionResource = $subscriptionResource;
        $this->_subscriptionFactory = $subscriptionFactory;
        $this->_creditMemoCollectionFactory = $creditMemoCollectionFactory;
    }
    
    /**
     * Get the order information for the desired order id that was passed in
     * 
     * @param $orderId
     * @return string
     */
    public function getOrderById($orderId)
    {
    	$this->_logger->info("Entering SMG/Api/Helper/OrderHelper->getOrderById() - ORDER_GET_ORDER_BY_ID - for orderId - $orderId");
    	
    	try
    	{
	    	// make sure that we were passed something before continuing
	    	if (!empty($orderId))
	    	{
	    		// get the debit order data
	    		$debitArray = $this->getDebitOrderData($orderId);
	    		
	    		// get the annual subscription data
	    		$annualSubscriptionArray = $this->getAnnualSubscriptionData($orderId);
	    		
	    		// merge the debits and credits
	    		$ordersArray = array_merge($debitArray, $annualSubscriptionArray);
	    		
	    		// determine if there is anything there to send
	    		if (empty($ordersArray))
	    		{
	    			throw new \Exception("There was an issue processing $orderId. No Records were found for the the order.");
	    		}
	    		else
	    		{
	    			$orders = $this->_responseHelper->createResponse(true, $ordersArray);
	    		}
	    	}
	    	else
	    	{
	    		throw new \Exception("The order $orderId was not found for processing.");
	    	}
    	}
    	catch (\Exception $e)
    	{
    		// log the error
    		$this->_logger->error($e);
    		
    		// create the error response
    		$orders = $this->_responseHelper->createResponse(false, "There was an issue with orderId $orderId. Check logs for details."); 
    	}

        $this->_logger->info("Exiting SMG/Api/Helper/OrderHelper->getOrderById() - ORDER_GET_ORDER_BY_ID - for orderId - $orderId");
        
        // return
        return $orders;
    }

    /**
     * Get the debit orders
     *
     * @param orderId
     * @return array
     */
    private function getDebitOrderData($orderId)
    {
    	$this->_logger->info("Entering SMG/Api/Helper/OrderHelper->getDebitOrderData() - ORDER_GET_ORDER_BY_ID - for orderId - $orderId");
    	
        $ordersArray = array();

        // Get the sales order
        /**
         * @var \SMG\Sales\Model\Order $order
         */
        $order = $this->_orderFactory->create();
        $this->_orderResource->load($order, $orderId);

        // we do not want to process annual subscriptions here
        // annual subscriptions need to be placed together in the file
        // otherwise they will not add properly in SAP.  Season subscriptions
        // are different because they are processed like regular orders
        $subscriptionType = $order->getSubscriptionType();
        
        $this->_logger->info("OrderId - $orderId - subscriptionType - $subscriptionType");
        
        if ($order->isSubscription() && $subscriptionType == 'annual')
        {
        	// get the master subscription id
            $masterSubscriptionId = $order->getData('master_subscription_id');

            // make sure that the value was not already added
            if (!in_array($masterSubscriptionId, $this->_masterSubscriptionIds))
            {
            	// add to the array
                array_push($this->_masterSubscriptionIds, $masterSubscriptionId);
            }
        }
        else if ($order->isCanceled())
        {
        	throw new \Exception("The order $orderId has already been cancelled.");
        }
        else 
        {
        	// get the orders for the array
        	$ordersArray[] = $this->getOrdersForProcessing($order, $orderId);
        }
        
        $this->_logger->info("Exiting SMG/Api/Helper/OrderHelper->getDebitOrderData() - ORDER_GET_ORDER_BY_ID - for orderId - $orderId");

        // return
        return $ordersArray;
    }

    /**
     * Process annual subscription data
     *
     * @param $orderId
     * @return array
     */
    private function getAnnualSubscriptionData($orderId)
    {
    	$this->_logger->info("Entering SMG/Api/Helper/OrderHelper->getAnnualSubscriptionData() - ORDER_GET_ORDER_BY_ID - for orderId - $orderId");
    	
        $ordersArray = array();

        // loop through the list of subscriptions if there are any
        if (!empty($this->_masterSubscriptionIds))
        {
            // loop through the list of master subscription ids
            foreach ($this->_masterSubscriptionIds as $masterSubscriptionId)
            {
                // get the subscription data with filter master_subscription_id of sales_order
                $subscription = $this->_subscriptionFactory->create();
                $this->_subscriptionResource->load($subscription, $masterSubscriptionId, 'subscription_id');
                
                $subscriptionId = $subscription->getId();
                $this->_logger->info("The subscription ID is $subscriptionId");
                
                // check subscription exist or not
                if(!empty($subscriptionId))
                {
	                // get the list of orders for this master subscription id
	                $annualOrders = $this->_orderCollectionFactory->create();
	                $annualOrders->addFieldToFilter('master_subscription_id', ['eq' => $masterSubscriptionId]);
	                $annualOrders->setOrder('master_subscription_id', 'asc');
	                $annualOrders->setOrder('ship_start_date', 'asc');
	                $annualOrders->setOrder('entity_id', 'asc');
	
	                $numberOfAnnualOrders = $annualOrders->count();
	                
	                $this->_logger->info("There are $numberOfAnnualOrders subscription orders with $orderId");
	                
	                // make sure that there are orders
	                // check if there are orders to process
	                if ($annualOrders->count() > 0)
	                {
	                    /**
	                     * @var \SMG\Sales\Model\Order $annualOrder
	                     */
	                    foreach ($annualOrders as $annualOrder)
	                    {
	                     	// get the required fields needed for processing
	                     	$annualOrderId = $annualOrder->getId();
	                     	
	                     	$this->_logger->info("The annual orderId is $annualOrderId");
	
	                        if ($annualOrder->isCanceled())
	                        {
	                        	throw new \Exception("The subscription order $annualOrderId has already been cancelled.");
	                        }
	                        else
	                        {
	                            // get the orders for the array
	                        	$ordersArray[] = $this->getOrdersForProcessing($annualOrder, $annualOrderId);
	                        }
	                    }
	                }
	                else
	                {
	                	throw new \Exception("There are no annual orders found for order $orderId with master subscription id $masterSubscriptionId");
	                }
                }
                else
                {
                	$incrementId = $annualOrder->getData('increment_id');
                	throw new \Exception("Subscription id is null for orderId $annualOrderId - order number - $incrementId");
                }
            }
        }
        
        $this->_logger->info("Exiting SMG/Api/Helper/OrderHelper->getAnnualSubscriptionData() - ORDER_GET_ORDER_BY_ID - for orderId - $orderId");

        // return
        return $ordersArray;
    }
    
    /**
     * This gets the orders detail from the order
     * 
     * @param Order $order
     * @param $orderId
     * @return array
     */
    private function getOrdersForProcessing($order, $orderId)
    {
    	$this->_logger->info("Entering SMG/Api/Helper/OrderHelper->getOrdersForProcessing() - ORDER_GET_ORDER_BY_ID - for orderId - $orderId");
    	
    	$ordersArray = array();
    	
    	// Skip if virtual
    	if (!$order->getIsVirtual())
    	{
    		// get the list of items for this order
    		$orderItems = $this->_orderItemCollectionFactory->create();
    		$orderItems->addFieldToFilter("order_id", ['eq' => $orderId]);
    		$orderItems->addFieldToFilter("product_type", ['neq' => 'bundle']);
    		$orderItems->addFieldToFilter("product_type", ['neq' => 'configurable']);
    	
    		/**
    		 * @var \Magento\Sales\Model\Order\Item $orderItem
    		*/
    		foreach ($orderItems as $orderItem)
    		{
    			$ordersArray[] = $this->addRecordToOrdersArray($order, $orderItem, $orderId);
    		}
    	}
    	else
    	{
    		throw new \Exception("The order $orderId is a Virtual Order");
    	}
    	
    	$this->_logger->info("Exiting SMG/Api/Helper/OrderHelper->getOrdersForProcessing() - ORDER_GET_ORDER_BY_ID - for orderId - $orderId");
    	
    	return $ordersArray;
    }

    /**
     * Takes the order and item details and puts it in an array
     *
     * @param Order $order
     * @param Item $orderItem
     * @param $orderId
     * @return array
     */
    private function addRecordToOrdersArray($order, $orderItem, $orderId)
    {
    	$this->_logger->info("Entering SMG/Api/Helper/OrderHelper->addRecordToOrdersArray() - ORDER_GET_ORDER_BY_ID - for orderId - $orderId");
    	
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

        // get the values that change depending on the type of order
        $debitCreditFlag = 'DR';
        $quantity = $orderItem->getQtyOrdered();
        $grossSales = $order->getData('grand_total');
        $shippingAmount = $order->getData('shipping_amount');
        $taxAmount = $order->getData('tax_amount');
        $hdrDiscFixedAmount = '';
        $hdrDiscPerc = '';
        $hdrDiscCondCode = '';
        $subtotal = $order->getData('subtotal');
        $invoiceAmount = $order->getData('total_invoiced');

        // get the shipping address
        /**
         * @var \Magento\Sales\Model\Order\Address $shippingAddress
         */
        $shippingAddress = $order->getShippingAddress();
        $streetArray = $shippingAddress->getStreet();
        $implodedStreetAddress = $this->implodeStreetArray($streetArray);
        $customerFirstName = $shippingAddress->getFirstname();
        $customerLastName = $shippingAddress->getLastname();
        if (empty($customerFirstName) && empty($customerLastName))
        {
            $customerFirstName = $order->getData('customer_firstname');
            $customerLastName = $order->getData('customer_lastname');
        }

        // get the customer name
        // SAP requires the shipping name not the customer/billing name
        $customerName = $customerFirstName . ' ' . $customerLastName;
        
        if(!empty($order->getData('coupon_code'))){
            $orderDiscount = $this->_discountHelper->DiscountCode($order->getData('coupon_code'));
            $hdrDiscFixedAmount = $orderDiscount['hdr_disc_fixed_amount'];
            $hdrDiscPerc = $orderDiscount['hdr_disc_perc'];
            $hdrDiscCondCode = $orderDiscount['hdr_disc_cond_code'];
        }

        $discCondCode = '';
        $discFixedAmt = '';
        $discPerAmt = '';
        $itemDiscount = $this->_discountHelper->CatalogCode($order->getId(), $orderItem);

        if(!empty($itemDiscount))
        {
            $discFixedAmt = $itemDiscount['disc_fixed_amount'];
            $discPerAmt  = $itemDiscount['disc_percent_amount'];
            $discCondCode = $itemDiscount['disc_condition_code'];
        }

        // set credit fields to empty
        $hdrSurchFixedAmount = '';
        $hdrSurchPerc = '';
        $hdrSurchCondCode = '';
        $creditAmount = '';
        $referenceDocNum = '';
        $creditComment = '';
        $orderReason = '';
        $surchCondCode='';
        $surchFixedAmt='';
        $surchPerAmt='';

        // determine if this is a subscription
        $subscriptionType = $order->getSubscriptionType();
        
        $this->_logger->info("Subscription Type $subscriptionType for $orderId");
        
        if ($order->isSubscription() && $subscriptionType == 'annual')
        {
            // get the subscription
            /**
             * @var \SMG\SubscriptionApi\Model\Subscription $subscription
             */
            $masterSubscriptionId = $order->getData('master_subscription_id');

            $subscription = $this->_subscriptionResource->getSubscriptionByMasterSubscriptionId($masterSubscriptionId);

            // get the gross sales from the subscription order
            $grossSales = $subscription->getData('paid');

            // get the shipping amount.  Currently we don't have a shipping amount
            // for subscriptions.  this will need to be changed if we ever start adding
            // a shipping amount for subscriptions.
            $shippingAmount = '0';

            // get the subtotal of the subscription
            $subtotal = $subscription->getData('price');

            // get the tax of the subscription
            $taxAmount = $subscription->getData('tax');

            // get the invoice amount which is the same as the gross sales
            $invoiceAmount = $subscription->getData('paid');
        }

        // If configurable, get parent price
        $price = $orderItem->getOriginalPrice();

        if (!empty($orderItem->getParentItemId()))
        {
            $parent = $this->_orderItemCollectionFactory->create()->addFieldToFilter('item_id', ['eq' => $orderItem->getParentItemId()]);
            
            /**
             * There will be only one result since we filter on the unique id
             *
             * @var \Magento\Sales\Model\Order\Item $parentItem
             */
            $parentItem = $parent->getFirstItem();
            if ($parentItem->getProductType() === "configurable")
            {
                $price = $parentItem->getOriginalPrice();
            }
        }

        // check to see if there was a value for invoiceAmount
        if (empty($invoiceAmount))
        {
            $invoiceAmount = '';
        }
        
        $this->_logger->info("Exiting SMG/Api/Helper/OrderHelper->addRecordToOrdersArray() - ORDER_GET_ORDER_BY_ID - for orderId - $orderId");

        // return
        return array_map('trim', array(
            self::ORDER_NUMBER => $order->getIncrementId(),
            self::SUBSCRIPTION_ORDER => $order->getSubscriptionOrderId(),
            self::SUBSCRIPTION_TYPE => $subscriptionType,
            self::DATE_PLACED => $order->getData('created_at'),
            self::SAP_DELIVERY_DATE => $tomorrow,
            self::CUSTOMER_NAME => $customerName,
            self::ADDRESS_STREET => $implodedStreetAddress,
            self::ADDRESS_CITY => $shippingAddress->getCity(),
            self::ADDRESS_STATE => $shippingAddress->getRegion(),
            self::ADDRESS_ZIP => $shippingAddress->getPostcode(),
            self::SMG_SKU => $orderItem->getSku(),
            self::WEB_SKU => $orderItem->getSku(),
            self::QUANTITY => $quantity,
            self::UNIT => 'EA',
            self::UNIT_PRICE => $price,
            self::GROSS_SALES => $grossSales,
            self::SHIPPING_AMOUNT => $shippingAmount,
            self::EXEMPT_AMOUNT => '0',
            self::HDR_DISC_FIXED_AMOUNT => $hdrDiscFixedAmount,
            self::HDR_DISC_PERC => $hdrDiscPerc,
            self::HDR_DISC_COND_CODE => $hdrDiscCondCode,
            self::HDR_SURCH_FIXED_AMOUNT => $hdrSurchFixedAmount,
            self::HDR_SURCH_PERC => $hdrSurchPerc,
            self::HDR_SURCH_COND_CODE => $hdrSurchCondCode,
            self::DISCOUNT_AMOUNT => '',
            self::SUBTOTAL => $subtotal,
            self::TAX_RATE => $orderItem->getTaxPercent(),
            self::SALES_TAX => $taxAmount,
            self::INVOICE_AMOUNT => $invoiceAmount,
            self::DELIVERY_LOCATION => '',
            self::EMAIL => $order->getData('customer_email'),
            self::PHONE => $shippingAddress->getTelephone(),
            self::DELIVERY_WINDOW => '',
            self::SHIPPING_CONDITION => $shippingCondition->getData('sap_shipping_method'),
            self::WEBSITE_URL => $urlParts['host'],
            self::CREDIT_AMOUNT => $creditAmount,
            self::CR_DR_RE_FLAG => $debitCreditFlag,
            self::SAP_BILLING_DOC_NUMBER => $referenceDocNum,
            self::CREDIT_COMMENT => $creditComment,
            self::ORDER_REASON => $orderReason,
            self::DISCOUNT_CONDITION_CODE => $discCondCode,
            self::SURCH_CONDITION_CODE => $surchCondCode,
            self::DISCOUNT_FIXED_AMOUNT => $discFixedAmt,
            self::SURCH_FIXED_AMOUNT => $surchFixedAmt,
            self::DISCOUNT_PERCENT_AMOUNT => $discPerAmt,
            self::SURCH_PERCENT_AMOUNT => $surchPerAmt,
            self::DISCOUNT_REASON => $orderItem->getReasonCode(),
            self::SUBSCRIPTION_SHIP_START => $order->getData('ship_start_date'),
            self::SUBSCRIPTION_SHIP_END => $order->getData('ship_end_date')
        ));
    }

    /**
    * Implode all Street Line Values
    */
    private function implodeStreetArray($value)
    {
        $streetArrayValue = $value;
        $impodeStreetValues = implode(" ", array_reverse($streetArrayValue));
        $value = $impodeStreetValues;
    
        return $value;
    }
}
