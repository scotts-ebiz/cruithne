<?php
/**
 * User: cnixon
 * Date: 5/14/19
 */
namespace SMG\Api\Helper;

use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\Order\ItemFactory;
use Magento\Sales\Model\ResourceModel\Order as OrderResource;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Magento\Sales\Model\ResourceModel\Order\Item as ItemResource;
use Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory as OrderItemCollectionFactory;

use Psr\Log\LoggerInterface;

use SMG\Api\Model\OrderResponseFactory;
use SMG\OfflineShipping\Model\ShippingConditionCodeFactory;
use SMG\OfflineShipping\Model\ResourceModel\ShippingConditionCode as ShippingConditionCodeResource;
use SMG\OrderDiscount\Helper\Data as DiscountHelper;
use SMG\Sap\Model\ResourceModel\SapOrderBatch as SapOrderBatchResource;
use SMG\Sap\Model\ResourceModel\SapOrderBatch\CollectionFactory as SapOrderBatchCollectionFactory;
use SMG\SubscriptionApi\Model\ResourceModel\Subscription as SubscriptionResource;

class OrdersLawnSubscriptionHelper
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
     * @var SapOrderBatchCollectionFactory
     */
    protected $_sapOrderBatchCollectionFactory;

    /**
     * @var DiscountHelper
     */
    protected $_discountHelper;

    /**
     * @var SapOrderBatchResource
     */
    protected $_sapOrderBatchResource;

    /**
     * @var SubscriptionResource
     */
    protected $_subscriptionResource;

    /**
     * @var OrdersResponseHelper
     */
    protected $_orderResponseHelper;

    /**
     * @var @var OrderResponseFactory
     */
    protected $_orderResponseFactory;

    /**
     * @var array
     */
    protected $_ordersAlreadyProcessed = [];

    /**
     * OrdersLawnSubscriptionHelper constructor.
     *
     * @param LoggerInterface $logger
     * @param ResponseHelper $responseHelper
     * @param OrderCollectionFactory $orderCollectionFactory
     * @param OrderItemCollectionFactory $orderItemCollectionFactory
     * @param ShippingConditionCodeFactory $shippingConditionCodeFactory
     * @param ShippingConditionCodeResource $shippingConditionCodeResource
     * @param OrderFactory $orderFactory
     * @param OrderResource $orderResource
     * @param ItemFactory $itemFactory
     * @param ItemResource $itemResource
     * @param SapOrderBatchCollectionFactory $sapOrderBatchCollectionFactory
     * @param DiscountHelper $discountHelper
     * @param SapOrderBatchResource $sapOrderBatchResource
     * @param SubscriptionResource $subscriptionResource
     * @param OrdersResponseHelper $ordersResponseHelper
     * @param OrderResponseFactory $orderResponseFactory
     */
    public function __construct(LoggerInterface $logger,
        ResponseHelper $responseHelper,
        OrderCollectionFactory $orderCollectionFactory,
        OrderItemCollectionFactory $orderItemCollectionFactory,
        ShippingConditionCodeFactory $shippingConditionCodeFactory,
        ShippingConditionCodeResource $shippingConditionCodeResource,
        OrderFactory $orderFactory,
        OrderResource $orderResource,
        ItemFactory $itemFactory,
        ItemResource $itemResource,
        SapOrderBatchCollectionFactory $sapOrderBatchCollectionFactory,
        DiscountHelper $discountHelper,
        SapOrderBatchResource $sapOrderBatchResource,
        SubscriptionResource $subscriptionResource,
        OrdersResponseHelper $ordersResponseHelper,
        OrderResponseFactory $orderResponseFactory)
    {
        $this->_logger = $logger;
        $this->_responseHelper = $responseHelper;
        $this->_orderCollectionFactory = $orderCollectionFactory;
        $this->_orderItemCollectionFactory = $orderItemCollectionFactory;
        $this->_shippingConditionCodeFactory = $shippingConditionCodeFactory;
        $this->_shippingConditionCodeResource = $shippingConditionCodeResource;
        $this->_orderFactory = $orderFactory;
        $this->_orderResource = $orderResource;
        $this->_itemFactory = $itemFactory;
        $this->_itemResource = $itemResource;
        $this->_sapOrderBatchCollectionFactory = $sapOrderBatchCollectionFactory;
        $this->_discountHelper = $discountHelper;
        $this->_sapOrderBatchResource = $sapOrderBatchResource;
        $this->_subscriptionResource = $subscriptionResource;
        $this->_orderResponseHelper = $ordersResponseHelper;
        $this->_orderResponseFactory = $orderResponseFactory;
    }

    /**
     * Get the sales orders in the desired format
     *
     * @param $orderLimits
     * @return string
     */
    public function getOrders($orderLimits)
    {
        try
        {
            // get the order limit count if there is one
            $orderLimit = 0;
            if (count($orderLimits) > 0)
            {
                // make sure that the key exists
                if (array_key_exists('orderLimit', $orderLimits[0]))
                {
                    $orderLimit = $orderLimits[0]["orderLimit"];
                }
            }

            // get the annual subscription data
            $ordersArray = $this->getAnnualSubscriptionData($orderLimit);

            // determine if there is anything there to send
            if (empty($ordersArray))
            {
                // log that there were no records found.
                $this->_logger->info("SMG\Api\Helper\OrdersLawnSubscriptionHelper - No Lawn Subscription Orders were found for processing.");

                $orders = $this->_responseHelper->createResponse(true, 'No Lawn Subscription Orders where found for processing.');
            }
            else
            {
                $orders = $this->_responseHelper->createResponse(true, $ordersArray);
            }
        }
        catch (\Exception $e)
        {
            $this->_logger->error($e->getMessage());

            $orders = $this->_responseHelper->createResponse(false, 'An error occurred during processing of OrdersLawnSubscriptionsHelper->getOrders().');
        }

        // return..

        return $orders;
    }

    /**
     * Process annual subscription data
     *
     * @param $orderLimit
     * @return array
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    private function getAnnualSubscriptionData($orderLimit)
    {
        $ordersArray = array();

        // get the orders that are ready to be sent to SAP
        /**
         * @var \SMG\Sap\Model\ResourceModel\SapOrderBatch\Collection $sapOrderBatches
         */
        $sapOrderBatches = $this->_sapOrderBatchCollectionFactory->create();
        $sapOrderBatches->addFieldToFilter('is_order', ['eq' => true]);
        $sapOrderBatches->addFieldToFilter('order_process_date', ['null' => true]);

        // if there is a limit then lets add it
        // if the limit is 0 then we do not add it as we want all of them
        if ($orderLimit > 0)
        {
            $sapOrderBatches->getSelect()->limit($orderLimit);
        }

        // check if there are orders to process
        if ($sapOrderBatches->count() > 0)
        {
            /**
             * @var \SMG\Sap\Model\SapOrderBatch $sapOrderBatch
             */
            foreach ($sapOrderBatches as $sapOrderBatch)
            {
                try
                {
                    // get the required fields needed for processing
                    $orderId = $sapOrderBatch->getData('order_id');

                    // check if the order has already been processed with another order
                    // as we are doing the orders for the associated master_subscription_id
                    // and not just he current order so this order may have already been
                    // sent to SAP
                    if (!in_array($orderId, $this->_ordersAlreadyProcessed))
                    {
                        // Get the sales order
                        /**
                         * @var \SMG\Sales\Model\Order $order
                         */
                        $order = $this->_orderFactory->create();
                        $this->_orderResource->load($order, $orderId);

                        // make sure that this order was not canceled before continuing
                        // we do not want to send canceled orders
                        if ($order->isCanceled())
                        {
                            // get the date for today
                            $today = date('Y-m-d H:i:s');

                            // update the process date so it isn't picked up again
                            $sapOrderBatch->setData('order_process_date', $today);

                            // save to the database
                            $this->_sapOrderBatchResource->save($sapOrderBatch);
                        }
                        else
                        {
                            // we only want to process annual subscriptions here
                            // annual subscriptions need to be placed together in the file
                            // otherwise they will not add properly in SAP.  Season subscriptions
                            // are different because they are processed like regular orders
                            $subscriptionType = $order->getData('subscription_type');
                            if ($order->isSubscription() && $subscriptionType == 'annual')
                            {
                                // get the list of orders for this master subscription id
                                $annualOrders = $this->_orderCollectionFactory->create();
                                $annualOrders->addFieldToFilter('master_subscription_id', ['eq' => $order->getData('master_subscription_id')]);
                                $annualOrders->setOrder('master_subscription_id', 'asc');
                                $annualOrders->setOrder('ship_start_date', 'asc');
                                $annualOrders->setOrder('entity_id', 'asc');

                                // make sure that there are orders
                                // check if there are orders to process
                                // there should be at least one the one that we used to
                                // filter the data
                                if ($annualOrders->count() > 0)
                                {
                                    /**
                                     * @var \SMG\Sales\Model\Order $annualOrder
                                     */
                                    foreach ($annualOrders as $annualOrder)
                                    {
                                        // get the required fields needed for processing
                                        $annualOrderId = $annualOrder->getId();

                                        if ($annualOrder->isCanceled())
                                        {
                                            // get the date for today
                                            $today = date('Y-m-d H:i:s');

                                            // update the process date so it isn't picked up again
                                            $sapOrderBatch->setData('order_process_date', $today);

                                            // save to the database
                                            $this->_sapOrderBatchResource->save($sapOrderBatch);
                                        }
                                        else
                                        {
                                            // make sure that the value was not already added
                                            if (!in_array($annualOrderId, $this->_ordersAlreadyProcessed))
                                            {
                                                // add to the array
                                                array_push($this->_ordersAlreadyProcessed, $annualOrderId);
                                            }

                                            // get the list of items for this order
                                            $orderItems = $this->_orderItemCollectionFactory->create();
                                            $orderItems->addFieldToFilter("order_id", ['eq' => $annualOrderId]);
                                            $orderItems->addFieldToFilter("product_type", ['neq' => 'bundle']);
                                            $orderItems->addFieldToFilter("product_type", ['neq' => 'configurable']);

                                            // Skip if virtual
                                            if (!$order->getIsVirtual())
                                            {
                                                /**
                                                 * @var \Magento\Sales\Model\Order\Item $orderItem
                                                 */
                                                foreach ($orderItems as $orderItem)
                                                {
                                                    $ordersArray[] = $this->addRecordToOrdersArray($annualOrder, $orderItem);
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
                catch (\Exception $e)
                {
                    if (!empty($orderId))
                    {
                        $this->_logger->error("There was an error processing orderId OrdersLawnSubscriptionHelper - " . $orderId);
                    }

                    // added this so if an error occurs during processing of the order then we can catch
                    // it here and log the message and then keep processing the other orders
                    $this->_logger->error($e->getMessage());

                    // get the date for today
                    $today = date('Y-m-d H:i:s');

                    // update the process date so it isn't picked up again
                    $sapOrderBatch->setData('order_process_date', $today);

                    // save to the database
                    $this->_sapOrderBatchResource->save($sapOrderBatch);
                }
            }
        }

        // return
        return $ordersArray;
    }

    /**
     * Takes the order and item details and puts it in an array
     *
     * @param $order
     * @param $orderItem
     * @return array
     * @throws \Exception
     */
    private function addRecordToOrdersArray($order, $orderItem)
    {
        // create a new order response object
        /**
         * @var \SMG\Api\Model\OrderResponse $orderResponse
         */
        $orderResponse = $this->_orderResponseFactory->create();

        $orderResponse->setOrderNumber($order->getIncrementId());
        $orderResponse->setSubscriptionOrder($order->getSubscriptionOrderId());
        $orderResponse->setSubscriptionType($order->getData('subscription_type'));
        $orderResponse->setDatePlaced($order->getData('created_at'));

        // get tomorrows date
        $tomorrow = date('Y-m-d', strtotime("tomorrow"));

        $orderResponse->setSapDeliveryDate($tomorrow);

        // get the shipping address
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

        // get the customer name
        // SAP requires the shipping name not the customer/billing name
        $customerName = $customerFirstName . ' ' . $customerLastName;

        $orderResponse->setCustomerName($customerName);
        $orderResponse->setAddressStreet($shippingAddress->getStreetLine(1));
        $orderResponse->setAddressCity($shippingAddress->getCity());
        $orderResponse->setAddressState($shippingAddress->getRegion());
        $orderResponse->setAddressZip($shippingAddress->getPostcode());
        $orderResponse->setSmgSku($orderItem->getSku());
        $orderResponse->setWebSku($orderItem->getSku());
        $orderResponse->setQuantity($orderItem->getQtyOrdered());
        $orderResponse->setUnit('EA');

        // Get the price to send to SAP,
        // If configurable, get parent price
        $price = $orderItem->getOriginalPrice();

        if (!empty($orderItem->getParentItemId()))
        {
            // get the parent item from the parent item id
            /**
             * @var \Magento\Sales\Model\Order\Item $parentItem
             */
            $parentItem = $this->_itemFactory->create();
            $this->_itemResource->load($parentItem, $orderItem->getParentItemId());

            if ($parentItem->getProductType() === "configurable")
            {
                $price = $parentItem->getOriginalPrice();
            }
        }

        $orderResponse->setUnitPrice($price);

        // get the subscription
        /**
         * @var \SMG\SubscriptionApi\Model\Subscription $subscription
         */
        $subscription = $this->_subscriptionResource->getSubscriptionByMasterSubscriptionId($order->getData('master_subscription_id'));

        $orderResponse->setGrossSales($subscription->getData('paid'));

        // get the shipping amount.  Currently we don't have a shipping amount
        // for subscriptions.  this will need to be changed if we ever start adding
        // a shipping amount for subscriptions.
        $orderResponse->setShippingAmount('0');
        $orderResponse->setExemptAmount('0');

        $hdrDiscFixedAmount = '';
        $hdrDiscPerc = '';
        $hdrDiscCondCode = '';
        if(!empty($order->getData('coupon_code')))
        {
            $orderDiscount = $this->_discountHelper->DiscountCode($order->getData('coupon_code'));
            $hdrDiscFixedAmount = $orderDiscount['hdr_disc_fixed_amount'];
            $hdrDiscPerc = $orderDiscount['hdr_disc_perc'];
            $hdrDiscCondCode = $orderDiscount['hdr_disc_cond_code'];
        }

        $orderResponse->setHdrDiscFixedAmount($hdrDiscFixedAmount);
        $orderResponse->setHdrDiscPerc($hdrDiscPerc);
        $orderResponse->setHdrDiscCondCode($hdrDiscCondCode);
        $orderResponse->setHdrSurchFixedAmount('');
        $orderResponse->setHdrSurchPerc('');
        $orderResponse->setHdrSurchCondCode('');
        $orderResponse->setDiscountAmount('');
        $orderResponse->setSubtotal($subscription->getData('price'));
        $orderResponse->setTaxRate($orderItem->getTaxPercent());
        $orderResponse->setSalesTax($subscription->getData('tax'));

        // check to see if there was a value for invoiceAmount
        $invoiceAmount = $subscription->getData('paid');
        if (empty($invoiceAmount))
        {
            $invoiceAmount = '';
        }

        $orderResponse->setInvoiceAmount($invoiceAmount);
        $orderResponse->setDeliveryLocation('');
        $orderResponse->setEmail($order->getData('customer_email'));
        $orderResponse->setPhone($shippingAddress->getTelephone());
        $orderResponse->setDeliveryWindow('');

        // get the shipping condition data
        /**
         * @var /SMG/OfflineShipping/Model/ShippingConditionCode $shippingCondition
         */
        $shippingCondition = $this->_shippingConditionCodeFactory->create();
        $this->_shippingConditionCodeResource->load($shippingCondition, $order->getShippingMethod(), 'shipping_method');

        $orderResponse->setShippingCondition($shippingCondition->getData('sap_shipping_method'));

        // split the base url into different parts for later use
        $urlParts = parse_url($order->getStore()->getBaseUrl());

        $orderResponse->setWebsiteUrl($urlParts['host']);
        $orderResponse->setCreditAmount('');
        $orderResponse->setCrDrReFlag('DR');
        $orderResponse->setSapBillingDocNumber('');
        $orderResponse->setCreditComment('');
        $orderResponse->setOrderReason('');

        $discCondCode = '';
        $discFixedAmt = '';
        $discPerAmt = '';
        if(!empty($itemDiscount))
        {
            $itemDiscount = $this->_discountHelper->CatalogCode($order->getId(), $orderItem);
            $discFixedAmt = $itemDiscount['disc_fixed_amount'];
            $discPerAmt  = $itemDiscount['disc_percent_amount'];
            $discCondCode = $itemDiscount['disc_condition_code'];
        }

        $orderResponse->setDiscountConditionCode($discCondCode);
        $orderResponse->setSurchConditionCode('');
        $orderResponse->setDiscountFixedAmount($discFixedAmt);
        $orderResponse->setSurchFixedAmount('');
        $orderResponse->setDiscountPercentAmount($discPerAmt);
        $orderResponse->setSurchPercentAmount('');
        $orderResponse->setDiscountReason($orderItem->getReasonCode());
        $orderResponse->setSubscriptionShipStart($order->getData('ship_start_date'));
        $orderResponse->setSubscriptionShipEnd($order->getData('ship_end_date'));

        // return
        return $this->_orderResponseHelper->addRecordToOrdersArray($orderResponse);
    }
}
