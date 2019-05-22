<?php
/**
 * User: cnixon
 * Date: 5/14/19
 */
namespace SMG\Api\Helper;

use Magento\Framework\App\ResourceConnection;
use Magento\Sales\Api\CreditmemoRepositoryInterface;
use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Api\Data\CreditmemoItemInterface;
use Magento\Rma\Api\RmaRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\Order\Item;
use Magento\Sales\Model\Order\ItemFactory;
use Magento\Sales\Model\ResourceModel\Order as OrderResource;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Magento\Sales\Model\ResourceModel\Order\Item as ItemResource;
use Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory as OrderItemCollectionFactory;
use Psr\Log\LoggerInterface;
use SMG\OfflineShipping\Model\ShippingConditionCodeFactory;
use SMG\OfflineShipping\Model\ResourceModel\ShippingConditionCode as ShippingConditionCodeResource;
use SMG\Sap\Model\SapOrderFactory;
use SMG\Sap\Model\ResourceModel\SapOrder as SapOrderResource;
use SMG\Sap\Model\ResourceModel\SapOrderBatch\CollectionFactory as SapOrderBatchCollectionFactory;
use SMG\Sap\Model\ResourceModel\SapOrderBatchItem\CollectionFactory as SapOrderBatchItemCollectionFactory;
use SMG\Sap\Model\ResourceModel\SapOrderBatchRma\CollectionFactory as SapOrderBatchRmaCollectionFactory;
use SMG\OrderDiscount\Helper\Data as DiscountHelper;
use SMG\CreditReason\Model\CreditReasonCodeFactory;
use SMG\CreditReason\Model\ResourceModel\CreditReasonCode as CreditReasonCodeReource;
use SMG\CreditReason\Model\ResourceModel\CreditReasonCode\CollectionFactory as CreditReasonCodeCollectionFactory;

class OrdersHelper
{
    // Output JSON file constants
    const ORDER_NUMBER = 'OrderNumber';
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
    /**
     * @var LoggerInterface
     */
    protected $_logger;

    /**
     * @var ResourceConnection
     */
    protected $_resourceConnection;

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
     * @var SapOrderBatchItemCollectionFactory
     */
    protected  $_sapOrderBatchItemCollectionFactory;

    /**
     * @var SapOrderBatchRmaCollectionFactory
     */
    protected  $_sapOrderBatchRmaCollectionFactory;

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
     * OrdersHelper constructor.
     *
     * @param LoggerInterface $logger
     * @param ResourceConnection $resourceConnection
     * @param ResponseHelper $responseHelper
     * @param OrderCollectionFactory $orderCollectionFactory
     * @param OrderItemCollectionFactory $orderItemCollectionFactory
     * @param ShippingConditionCodeFactory $shippingConditionCodeFactory
     * @param ShippingConditionCodeResource $shippingConditionCodeResource
     * @param SapOrderBatchItemCollectionFactory $sapOrderBatchItemCollectionFactory
     * @param SapOrderBatchRmaCollectionFactory $sapOrderBatchRmaCollectionFactory
     * @param OrderResource $orderResource
     * @param ItemFactory $itemFactory
     * @param ItemResource $itemResource
     * @param SapOrderFactory $sapOrderFactory
     * @param SapOrderResource $sapOrderResource
     * @param CreditmemoRepositoryInterface $creditmemoRepository
     * @param SapOrderBatchCollectionFactory $sapOrderBatchCollectionFactory
     */
    public function __construct(LoggerInterface $logger,
        ResourceConnection $resourceConnection,
        CreditReasonCodeFactory $creditReasonCodeFactory,
        CreditReasonCodeReource $_creditReasonCodeResource,
        CreditReasonCodeCollectionFactory $creditReasonCodeCollectionFactory,
        ResponseHelper $responseHelper,
        OrderCollectionFactory $orderCollectionFactory,
        OrderItemCollectionFactory $orderItemCollectionFactory,
        ShippingConditionCodeFactory $shippingConditionCodeFactory,
        ShippingConditionCodeResource $shippingConditionCodeResource,
        SapOrderBatchItemCollectionFactory $sapOrderBatchItemCollectionFactory,
        SapOrderBatchRmaCollectionFactory $sapOrderBatchRmaCollectionFactory,
        OrderFactory $orderFactory,
        OrderResource $orderResource,
        ItemFactory $itemFactory,
        ItemResource $itemResource,
        SapOrderFactory $sapOrderFactory,
        SapOrderResource $sapOrderResource,
        CreditmemoRepositoryInterface $creditmemoRepository,
        RmaRepositoryInterface $rmaRepository,
        SapOrderBatchCollectionFactory $sapOrderBatchCollectionFactory,
        DiscountHelper $discountHelper)
    {
        $this->_logger = $logger;
        $this->_resourceConnection = $resourceConnection;
        $this->_responseHelper = $responseHelper;
        $this->_orderCollectionFactory = $orderCollectionFactory;
        $this->_orderItemCollectionFactory = $orderItemCollectionFactory;
        $this->_shippingConditionCodeFactory = $shippingConditionCodeFactory;
        $this->_shippingConditionCodeResource = $shippingConditionCodeResource;
        $this->_sapOrderBatchItemCollectionFactory = $sapOrderBatchItemCollectionFactory;
        $this->_sapOrderBatchRmaCollectionFactory = $sapOrderBatchRmaCollectionFactory;
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
        $this->_logger = $logger;
        $this->_creditReasonCodeFactory = $creditReasonCodeFactory;
        $this->_creditReasonCodeResource = $_creditReasonCodeResource;
        $this->_creditReasonCodeCollectionFactory = $creditReasonCodeCollectionFactory;
    }

    /**
     * Get the sales orders in the desired format
     *
     * @return string
     */
    public function getOrders()
    {
        // get the debit order data
        $debitArray = $this->getDebitOrderData();

        // get the credit order data
        $creditArray = $this->getCreditOrderData();

        // get the rma dat
        $rmaArray = $this->getRmaOrderData();

        // merge the debits and credits
        $ordersArray = array_merge($debitArray, $creditArray, $rmaArray);

        // determine if there is anything there to send
        if (empty($ordersArray))
        {
            // log that there were no records found.
            $this->_logger->info("SMG\Api\Helper\OrdersHelper - No Orders were found for processing.");

            $orders = $this->_responseHelper->createResponse(true, 'No Orders where found for processing.');
        }
        else
        {
            $orders = $this->_responseHelper->createResponse(true, $ordersArray);
        }

        // return..
        
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
        $sapOrderBatches->addFieldToFilter('is_order', ['eq' => true]);
        $sapOrderBatches->addFieldToFilter('order_process_date', ['null' => true]);

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
     * @param Order $order
     * @param Item $orderItem
     * @param CreditmemoInterface $creditMemo
     * @param CreditmemoItemInterface $creditMemoItem
     * @return array
     */
    private function addRecordToOrdersArray($order, $orderItem, $creditMemo = null, $creditMemoItem = null, $rmaItem = null, $rmaItemInfo = null)
    {
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

        // get the shipping address
        $address = $order->getShippingAddress();

        // check to see if there was a value
        $invoiceAmount = $order->getData('total_invoiced');
        if (empty($invoiceAmount))
        {
            $invoiceAmount = '';
        }

        // get the quantity
        $quantity = $orderItem->getQtyOrdered();
        $shippingAmount = $order->getData('shipping_amount');
        $taxAmount = $order->getData('tax_amount');
        
        $hdrDiscFixedAmount = '';
        $hdrDiscPerc = '';
        $hdrDiscCondCode = '';
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

        // determine what type of order
        $debitCreditFlag = 'DR';
        if (!empty($creditMemo) && !empty($creditMemoItem))
        {
            $debitCreditFlag = 'CR';

            // set other credit memeo type fields
            $quantity = $creditMemoItem->getQty();
            $shippingAmount = $creditMemo->getShippingAmount();
            $creditAmount = $creditMemoItem->getRowTotalInclTax();
            $creditComment = $creditMemo->getData('customer_note');
            $orderReason = $creditMemoItem->getData('refunded_reason_code');

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
        }

        // Returns (Rma)

        if (!empty($rmaItem) && !empty($rmaItemInfo))
        {
            $debitCreditFlag = 'RE';

            // set rma fields
            $quantity = $rmaItem->getData('qty_returned');
            $shippingAmount = '0';
            $discCondCode = 'ZMPA';
            $discPerAmt = '100';
            $taxAmount = '0';

            // Get the reason code
            $reasonId = $rmaItemInfo->getData('reason_id');
            $returnReason = $this->_creditReasonCodeFactory->create();
            $this->_creditReasonCodeResource->load($returnReason, $reasonId);

            $orderReason = $returnReason->getData('reason_code');

            // get the sap order for the billing doc number
            /**
             * @var \SMG\Sap\Model\SapOrder $sapOrder
             */
            $sapOrder = $this->_sapOrderFactory->create();
            $this->_sapOrderResource->load($sapOrder, $order->getId(), 'order_id');

            // get the billing doc number
            $referenceDocNum = $sapOrder->getData('sap_billing_doc_number');
        }

        // return
        return array_map('trim', array(
            self::ORDER_NUMBER => $order->getIncrementId(),
            self::DATE_PLACED => $order->getData('created_at'),
            self::SAP_DELIVERY_DATE => $tomorrow,
            self::CUSTOMER_NAME => $order->getData('customer_firstname') . ' ' . $order->getData('customer_lastname'),
            self::ADDRESS_STREET => $address->getStreetLine(1),
            self::ADDRESS_CITY => $address->getCity(),
            self::ADDRESS_STATE => $address->getRegion(),
            self::ADDRESS_ZIP => $address->getPostcode(),
            self::SMG_SKU => $orderItem->getSku(),
            self::WEB_SKU => $orderItem->getSku(),
            self::QUANTITY => $quantity,
            self::UNIT => 'EA',
            self::UNIT_PRICE => $orderItem->getPrice(),
            self::GROSS_SALES => $order->getData('grand_total'),
            self::SHIPPING_AMOUNT => $shippingAmount,
            self::EXEMPT_AMOUNT => '0',
            self::HDR_DISC_FIXED_AMOUNT => $hdrDiscFixedAmount,
            self::HDR_DISC_PERC => $hdrDiscPerc,
            self::HDR_DISC_COND_CODE => $hdrDiscCondCode,
            self::HDR_SURCH_FIXED_AMOUNT => $hdrSurchFixedAmount,
            self::HDR_SURCH_PERC => $hdrSurchPerc,
            self::HDR_SURCH_COND_CODE => $hdrSurchCondCode,
            self::DISCOUNT_AMOUNT => '',
            self::SUBTOTAL => $order->getData('subtotal'),
            self::TAX_RATE => $orderItem->getTaxPercent(),
            self::SALES_TAX => $taxAmount,
            self::INVOICE_AMOUNT => $invoiceAmount,
            self::DELIVERY_LOCATION => '',
            self::EMAIL => $order->getData('customer_email'),
            self::PHONE => $address->getTelephone(),
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
            self::DISCOUNT_REASON => $orderItem->getReasonCode()
        ));
    }

    /**
     * Get the array of credit orders
     *
     * @return array
     */
    private function getCreditOrderData()
    {
        $ordersArray = array();

        // get the orders that are ready to be sent to SAP
        $sapOrderBatchItems = $this->_sapOrderBatchItemCollectionFactory->create();
        $sapOrderBatchItems->addFieldToFilter('is_credit', ['eq' => true]);
        $sapOrderBatchItems->addFieldToFilter('credit_process_date', ['null' => true]);

        // check if there are orders to process
        if ($sapOrderBatchItems->count() > 0)
        {
            /**
             * @var \SMG\Sap\Model\SapOrderBatchItem $sapOrderBatchItem
             */
            foreach ($sapOrderBatchItems as $sapOrderBatchItem)
            {
                // get the required fields needed for processing
                $orderId = $sapOrderBatchItem->getData('order_id');
                $orderItemId = $sapOrderBatchItem->getData('order_item_id');
                $creditmemoId = $sapOrderBatchItem->getData('creditmemo_order_id');
                $sku = $sapOrderBatchItem->getData('sku');

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

    private function getRmaOrderData()
    {
        $ordersArray = array();

        // get the orders that are ready to be sent to SAP
        $sapOrderBatchRmas = $this->_sapOrderBatchRmaCollectionFactory->create();
        $sapOrderBatchRmas->addFieldToFilter('is_return', ['eq' => true]);
        $sapOrderBatchRmas->addFieldToFilter('return_process_date', ['null' => true]);

        // check if there are orders to process
        if ($sapOrderBatchRmas->count() > 0)
        {
            /**
             * @var \SMG\Sap\Model\SapOrderBatchRma $sapOrderBatchRma
             */
            foreach ($sapOrderBatchRmas as $sapOrderBatchRma)
            {
                // get the required fields needed for processing
                $orderId = $sapOrderBatchRma->getData('order_id');
                $orderItemId = $sapOrderBatchRma->getData('order_item_id');
                $rmaId = $sapOrderBatchRma->getData('rma_id');
                $sku = $sapOrderBatchRma->getData('sku');

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
                $rma = $this->_rmaRespository->get($rmaId);

                // Get the credit memo items
                $rmaItems = $rma->getItems();

                foreach ($rmaItems as $rmaItem)
                {
                    // see if the sku is the same as the sku that we are looking for
                    if ($sku === $rmaItem->getProductSku())
                    {
                        // add the record to the orders array
                        $ordersArray[] = $this->addRecordToOrdersArray($order, $orderItem, null, null, $rmaItem, $sapOrderBatchRma);

                        // get out of the loop as we found it
                        break;
                    }
                }
            }
        }

        // return
        return $ordersArray;
    }
}
