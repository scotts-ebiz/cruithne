<?php
/**
 * User: cnixon
 * Date: 5/14/19
 */
namespace SMG\Api\Helper;

use Magento\Sales\Api\CreditmemoRepositoryInterface;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\Order\ItemFactory;
use Magento\Sales\Model\ResourceModel\Order as OrderResource;
use Magento\Sales\Model\ResourceModel\Order\Item as ItemResource;

use Psr\Log\LoggerInterface;

use SMG\Api\Model\OrderResponseFactory;
use SMG\OfflineShipping\Model\ShippingConditionCodeFactory;
use SMG\OfflineShipping\Model\ResourceModel\ShippingConditionCode as ShippingConditionCodeResource;
use SMG\OrderDiscount\Helper\Data as DiscountHelper;
use SMG\Sap\Model\SapOrderFactory;
use SMG\Sap\Model\ResourceModel\SapOrder as SapOrderResource;
use SMG\Sap\Model\ResourceModel\SapOrderBatch\CollectionFactory as SapOrderBatchCollectionFactory;
use SMG\Sap\Model\ResourceModel\SapOrderBatchCreditmemo\CollectionFactory as SapOrderBatchCreditmemoCollectionFactory;
use SMG\SubscriptionApi\Model\ResourceModel\Subscription as SubscriptionResource;

class OrdersCreditMemoHelper
{
    // Return Credit Memo Codes
    const CUSTOMER_REFUSAL_CODE = '014';
    const BUYBACK_CODE = '037';
    const RECOVERY_RECALL_CODE = '038';

    /**
     * @var LoggerInterface
     */
    protected $_logger;

    /**
     * @var ResponseHelper
     */
    protected $_responseHelper;

    /**
     * @var ShippingConditionCodeFactory
     */
    protected $_shippingConditionCodeFactory;

    /**
     * @var ShippingConditionCodeResource
     */
    protected $_shippingConditionCodeResource;

    /**
     * @var SapOrderBatchCreditmemoCollectionFactory
     */
    protected  $_sapOrderBatchCreditmemoCollectionFactory;

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
     * @var SapOrderFactory
     */
    protected $_sapOrderFactory;

    /**
     * @var SapOrderResource
     */
    protected $_sapOrderResource;

    /**
     * @var CreditmemoRepositoryInterface
     */
    protected $_creditmemoRespository;

    /**
     * @var rmaRepositoryInterface
     */
    protected $_rmaRespository;

    /**
     * @var SapOrderBatchCollectionFactory
     */
    protected $_sapOrderBatchCollectionFactory;

    /**
     * @var DiscountHelper
     */
    protected $_discountHelper;

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

    public function __construct(LoggerInterface $logger,
        ResponseHelper $responseHelper,
        ShippingConditionCodeFactory $shippingConditionCodeFactory,
        ShippingConditionCodeResource $shippingConditionCodeResource,
        SapOrderBatchCreditmemoCollectionFactory $sapOrderBatchCreditmemoCollectionFactory,
        OrderFactory $orderFactory,
        OrderResource $orderResource,
        ItemFactory $itemFactory,
        ItemResource $itemResource,
        SapOrderFactory $sapOrderFactory,
        SapOrderResource $sapOrderResource,
        CreditmemoRepositoryInterface $creditmemoRepository,
        RmaRepositoryInterface $rmaRepository,
        SapOrderBatchCollectionFactory $sapOrderBatchCollectionFactory,
        DiscountHelper $discountHelper,
        SubscriptionResource $subscriptionResource,
        OrdersResponseHelper $ordersResponseHelper,
        OrderResponseFactory $orderResponseFactory)
    {
        $this->_logger = $logger;
        $this->_responseHelper = $responseHelper;
        $this->_shippingConditionCodeFactory = $shippingConditionCodeFactory;
        $this->_shippingConditionCodeResource = $shippingConditionCodeResource;
        $this->_sapOrderBatchCreditmemoCollectionFactory = $sapOrderBatchCreditmemoCollectionFactory;
        $this->_orderFactory = $orderFactory;
        $this->_orderResource = $orderResource;
        $this->_itemFactory = $itemFactory;
        $this->_itemResource = $itemResource;
        $this->_sapOrderFactory = $sapOrderFactory;
        $this->_sapOrderResource = $sapOrderResource;
        $this->_creditmemoRespository = $creditmemoRepository;
        $this->_rmaRespository = $rmaRepository;
        $this->_sapOrderBatchCollectionFactory = $sapOrderBatchCollectionFactory;
        $this->_discountHelper = $discountHelper;
        $this->_subscriptionResource = $subscriptionResource;
        $this->_orderResponseHelper = $ordersResponseHelper;
        $this->_orderResponseFactory = $orderResponseFactory;
    }

    /**
     * Get the sales orders in the desired format
     *
     * @param int $orderLimitCount
     * @return string
     */
    public function getOrders($orderLimitCount)
    {
        try
        {
            // get the credit order data
            $ordersArray = $this->getCreditOrderData($orderLimitCount);

            // determine if there is anything there to send
            if (empty($ordersArray))
            {
                // log that there were no records found.
                $this->_logger->info("SMG\Api\Helper\OrdersCreditMemoHelper - No Credit Memo Orders were found for processing.");

                $orders = $this->_responseHelper->createResponse(true, 'No Credit Memo Orders where found for processing.');
            }
            else
            {
                $orders = $this->_responseHelper->createResponse(true, $ordersArray);
            }
        }
        catch (\Exception $e)
        {
            $this->_logger->error($e->getMessage());

            $orders = $this->_responseHelper->createResponse(false, 'An error occurred during processing of OrdersCreditMemoHelper->getOrders().');
        }

        // return..

        return $orders;
    }

    /**
     * Get the array of credit orders
     *
     * @param int $orderLimitCount
     * @return array
     */
    private function getCreditOrderData($orderLimitCount)
    {
        $ordersArray = array();

        // get the orders that are ready to be sent to SAP
        $sapOrderBatchCreditmemos = $this->_sapOrderBatchCreditmemoCollectionFactory->create();
        $sapOrderBatchCreditmemos->addFieldToFilter('is_credit', ['eq' => true]);
        $sapOrderBatchCreditmemos->addFieldToFilter('credit_process_date', ['null' => true]);

        // if there is a limit then lets add it
        // if the limit is 0 then we do not add it as we want all of them
        if ($orderLimitCount > 0)
        {
            $sapOrderBatchCreditmemos->getSelect()->limit($orderLimitCount);
        }

        // check if there are orders to process
        if ($sapOrderBatchCreditmemos->count() > 0)
        {
            /**
             * @var \SMG\Sap\Model\SapOrderBatchCreditmemo $sapOrderBatchCreditmemo
             */
            foreach ($sapOrderBatchCreditmemos as $sapOrderBatchCreditmemo)
            {
                // get the required fields needed for processing
                $orderId = $sapOrderBatchCreditmemo->getData('order_id');
                $orderItemId = $sapOrderBatchCreditmemo->getData('order_item_id');
                $creditmemoId = $sapOrderBatchCreditmemo->getData('creditmemo_order_id');
                $sku = $sapOrderBatchCreditmemo->getData('sku');

                // Get the sales order
                /**
                 * @var \Magento\Sales\Model\Order $order
                 */
                $order = $this->_orderFactory->create();
                $this->_orderResource->load($order, $orderId);

                // Get the sales order item
                /**
                 * @var \Magento\Sales\Model\Order\Item $orderItem
                 */
                $orderItem = $this->_itemFactory->create();
                $this->_itemResource->load($orderItem, $orderItemId);

                // Get the credit memo
                $creditMemo = $this->_creditmemoRespository->get($creditmemoId);

                // Get the credit memo items
                $creditMemoItems = $creditMemo->getItems();
                foreach ($creditMemoItems as $creditMemoItem)
                {
                    // see if the sku is the same as the sku that we are looking for
                    if ($sku === $creditMemoItem->getSku())
                    {
                        // add the record to the orders array
                        $ordersArray[] = $this->addRecordToOrdersArray($order, $orderItem, $creditMemo, $creditMemoItem);

                        // get out of the loop as we found it
                        break;
                    }
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
     * @param $creditMemo
     * @param $creditMemoItem
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function addRecordToOrdersArray($order, $orderItem, $creditMemo, $creditMemoItem)
    {
        // create a new order response object
        /**
         * @var \SMG\Api\Model\OrderResponse $orderResponse
         */
        $orderResponse = $this->_orderResponseFactory->create();

        $orderResponse->setOrderNumber($order->getIncrementId());
        $orderResponse->setSubscriptionOrder($order->getSubscriptionOrderId());

        // determine if this is a subscription
        $subscriptionType = $order->getData('subscription_type');

        $orderResponse->setSubscriptionType($subscriptionType);
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
        $orderResponse->setQuantity($creditMemoItem->getQty());
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

        $orderResponse->setUnitPrice($price);
        $orderResponse->setGrossSales($order->getData('grand_total'));
        $orderResponse->setShippingAmount($creditMemo->getShippingAmount());
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
        $orderResponse->setInvoiceAmount($order->getData('total_invoiced'));
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
        $orderResponse->setCreditAmount($creditMemoItem->getRowTotalInclTax());

        $orderReason = $creditMemoItem->getData('refunded_reason_code');

        // Changes have occurred that the CSRs are entering
        // Returns as credit memos on magento.  In order to handle
        // this processing automatically we need to see if this request
        // is one of three types of credit memos because if it is then
        // it isn't a credit memo but rather a RMA so we need to flag it as such.
        $debitCreditFlag = 'CR';
        if ($orderReason == self::CUSTOMER_REFUSAL_CODE || $orderReason == self::BUYBACK_CODE || $orderReason == self::RECOVERY_RECALL_CODE)
        {
            $debitCreditFlag = 'RE';
        }

        $orderResponse->setCrDrReFlag($debitCreditFlag);

        // get the sap order for the billing doc number
        /**
         * @var \SMG\Sap\Model\SapOrder $sapOrder
         */
        $sapOrder = $this->_sapOrderFactory->create();
        $this->_sapOrderResource->load($sapOrder, $order->getId(), 'order_id');

        $sapOrderItems = $sapOrder->getSapOrderItems();
        $sapOrderItems->addFieldToFilter('sku', ['eq' => $orderItem->getSku()]);

        // if there is something there then get the first item
        // there should only be one item but get the first just in case
        $sapOrderItem = $sapOrderItems->getFirstItem();

        // get the billing doc number
        $referenceDocNum = $sapOrderItem->getData('sap_billing_doc_number');
        if (!isset($referenceDocNum))
        {
            $referenceDocNum = '';
        }

        $orderResponse->setSapBillingDocNumber($referenceDocNum);
        $orderResponse->setCreditComment($creditMemo->getData('customer_note'));
        $orderResponse->setOrderReason($orderReason);

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


        // if this is an annual subscription set the values for a credit
        // appropriately
        if ($order->isSubscription() && $subscriptionType == 'annual')
        {
            // get the subscription
            /**
             * @var \SMG\SubscriptionApi\Model\Subscription $subscription
             */
            $masterSubscriptionId = $order->getData('master_subscription_id');
            $subscription = $this->_subscriptionResource->getSubscriptionByMasterSubscriptionId($masterSubscriptionId);

            // get the gross sales from the subscription order
            $orderResponse->setGrossSales($subscription->getData('paid'));

            // get the shipping amount.  Currently we don't have a shipping amount
            // for subscriptions.  this will need to be changed if we ever start adding
            // a shipping amount for subscriptions.
            $orderResponse->setShippingAmount('0');

            // get the subtotal of the subscription
            $orderResponse->setSubtotal($subscription->getData('price'));

            // get the tax of the subscription
            $orderResponse->setSalesTax($subscription->getData('tax'));

            // get the invoice amount which is the same as the gross sales
            $orderResponse->setInvoiceAmount($subscription->getData('paid'));
        }

        // check to see if there was a value for invoiceAmount
        if (empty($orderResponse->getInvoiceAmount()))
        {
            $orderResponse->setInvoiceAmount('');
        }

        // return
        return $this->_orderResponseHelper->addRecordToOrdersArray($orderResponse);
    }
}
