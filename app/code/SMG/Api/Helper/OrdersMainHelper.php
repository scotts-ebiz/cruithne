<?php

namespace SMG\Api\Helper;

use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\Order\ItemFactory;
use Magento\Sales\Model\ResourceModel\Order as OrderResource;
use Magento\Sales\Model\ResourceModel\Order\Item as ItemResource;
use Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory as OrderItemCollectionFactory;
use Magento\Store\Api\StoreRepositoryInterface;

use Psr\Log\LoggerInterface;

use SMG\Api\Model\OrderResponseFactory;
use SMG\OfflineShipping\Model\ShippingConditionCodeFactory;
use SMG\OfflineShipping\Model\ResourceModel\ShippingConditionCode as ShippingConditionCodeResource;
use SMG\OrderDiscount\Helper\Data as DiscountHelper;
use SMG\Sap\Model\ResourceModel\SapOrderBatch as SapOrderBatchResource;
use SMG\Sap\Model\ResourceModel\SapOrderBatch\CollectionFactory as SapOrderBatchCollectionFactory;

class OrdersMainHelper
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
     * @var OrdersResponseHelper
     */
    protected $_orderResponseHelper;

    /**
     * @var OrderResponseFactory
     */
    protected $_orderResponseFactory;

    /**
     * @var StoreRepositoryInterface
     */
    protected $_storeRepositoryInterface;

    /**
     * OrdersHelper constructor.
     *
     * @param LoggerInterface $logger
     * @param ResponseHelper $responseHelper
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
     * @param OrdersResponseHelper $ordersResponseHelper
     * @param OrderResponseFactory $orderResponseFactory
     * @param StoreRepositoryInterface $storeRepositoryInterface
     */
    public function __construct(LoggerInterface $logger,
        ResponseHelper $responseHelper,
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
        OrdersResponseHelper $ordersResponseHelper,
        OrderResponseFactory $orderResponseFactory,
        StoreRepositoryInterface $storeRepositoryInterface)
    {
        $this->_logger = $logger;
        $this->_responseHelper = $responseHelper;
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
        $this->_orderResponseHelper = $ordersResponseHelper;
        $this->_orderResponseFactory = $orderResponseFactory;
        $this->_storeRepositoryInterface = $storeRepositoryInterface;
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
            $website = null;
            if (count($orderLimits) > 0)
            {
                // make sure that the key exists
                if (array_key_exists('orderLimit', $orderLimits[0]))
                {
                    $orderLimit = $orderLimits[0]["orderLimit"];
                }

                // make sure that the key exists
                if (array_key_exists('website', $orderLimits[0]))
                {
                    $website = $orderLimits[0]["website"];
                }
            }

            // get the debit order data
            $ordersArray = $this->getDebitOrderData($orderLimit, $website);

            // determine if there is anything there to send
            if (empty($ordersArray))
            {
                // log that there were no records found.
                $this->_logger->info("SMG\Api\Helper\OrdersMainHelper - No Orders were found for processing.");

                $orders = $this->_responseHelper->createResponse(true, 'No Orders where found for processing.');
            }
            else
            {
                $orders = $this->_responseHelper->createResponse(true, $ordersArray);
            }
        }
        catch (\Exception $e)
        {
            $this->_logger->error($e->getMessage());

            $orders = $this->_responseHelper->createResponse(false, 'An error occurred during processing of OrdersMainHelper->getOrders().');
        }

        // return
        return $orders;
    }

    /**
     * Get the debit orders
     *
     * @param $orderLimit
     * @param $website
     * @return array
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getDebitOrderData($orderLimit, $website)
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
            // if there is a website passed in then limit the results to that website
            $storeId = 0;
            if (!empty($website))
            {
                // get the store for the store id
                /**
                 * @var \Magento\Store\Api\Data\StoreInterface $store
                 */
                $store = $this->_storeRepositoryInterface->get($website);
                $storeId = $store->getId();
            }

            /**
             * @var \SMG\Sap\Model\SapOrderBatch $sapOrderBatch
             */
            foreach ($sapOrderBatches as $sapOrderBatch)
            {
                try
                {
                    // get the required fields needed for processing
                    $orderId = $sapOrderBatch->getData('order_id');

                    // Get the sales order
                    /**
                     * @var \SMG\Sales\Model\Order $order
                     */
                    $order = $this->_orderFactory->create();
                    $this->_orderResource->load($order, $orderId);

                    // check to see if this is site specific ordering
                    // if the storeId is 0 then we want all otherwise only
                    // get the data for the desired site
                    if ($storeId == 0 || $storeId == $order->getData("store_id"))
                    {
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
                            // we do not want to process annual subscriptions here
                            // annual subscriptions need to be placed together in the file
                            // otherwise they will not add properly in SAP.  Season subscriptions
                            // are different because they are processed like regular orders
                            $subscriptionType = $order->getData('subscription_type');
                            if (!$order->isSubscription() || ($order->isSubscription() && $subscriptionType != 'annual'))
                            {
                                // get the list of items for this order
                                $orderItems = $this->_orderItemCollectionFactory->create();
                                $orderItems->addFieldToFilter("order_id", ['eq' => $order->getId()]);
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
                                        $ordersArray[] = $this->addRecordToOrdersArray($order, $orderItem);
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
                        $this->_logger->error("There was an error processing orderId in OrdersMainHelper - " . $orderId);
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
     * @param Order $order
     * @param Item $orderItem
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

        // get the shipping address to determine address information
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
        $orderResponse->setGrossSales($order->getData('grand_total'));
        $orderResponse->setShippingAmount($order->getData('shipping_amount'));
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
        $orderResponse->setSubtotal($order->getData('subtotal'));
        $orderResponse->setTaxRate($orderItem->getTaxPercent());
        $orderResponse->setSalesTax($order->getData('tax_amount'));

        $invoiceAmount = $order->getData('total_invoiced');

        // check to see if there was a value for invoiceAmount
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
        $itemDiscount = $this->_discountHelper->CatalogCode($order->getId(), $orderItem);

        if(!empty($itemDiscount))
        {
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
