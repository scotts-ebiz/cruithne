<?php

namespace SMG\SubscriptionApi\Model;

use DateInterval;
use DateTime;
use Exception;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\DB\Transaction;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\ResourceModel\Order as OrderResource;
use Magento\Sales\Model\ResourceModel\Order\Creditmemo as CreditmemoResource;
use Magento\Sales\Model\Service\InvoiceService;
use Psr\Log\LoggerInterface;
use SMG\Api\Helper\OrderStatusHelper;
use SMG\Sap\Model\ResourceModel\SapOrderBatch as SapOrderBatchResource;
use SMG\Sap\Model\SapOrderBatch;
use SMG\Sap\Model\SapOrderBatchFactory;
use SMG\SubscriptionApi\Helper\SubscriptionHelper;
use SMG\SubscriptionApi\Model\ResourceModel\Subscription as SubscriptionResource;
use SMG\SubscriptionApi\Model\ResourceModel\SubscriptionAddonOrder as SubscriptionAddonOrderResource;
use SMG\SubscriptionApi\Model\ResourceModel\SubscriptionAddonOrderItem\CollectionFactory as SubscriptionAddonOrderItemCollectionFactory;
use Magento\Sales\Api\RefundOrderInterface;
use Magento\Sales\Model\Order\Creditmemo\ItemCreationFactory;

/**
 * Class SubscriptionAddonOrder
 * @package SMG\SubscriptionApi\Model
 */
class SubscriptionAddonOrder extends AbstractModel
{
    /** @var SubscriptionHelper */
    protected $_subscriptionHelper;

    /** @var SubscriptionAddonOrderItemCollectionFactory */
    protected $_subscriptionAddonOrderItemCollectionFactory;

    /** @var InvoiceService */
    protected $_invoiceService;

    /** @var Transaction */
    protected $_transaction;

    /** @var InvoiceSender */
    protected $_invoiceSender;

    /**
     * @var LoggerInterface
     */
    protected $_logger;

    /**
     * @var SapOrderBatchResource
     */
    protected $_sapOrderBatchResource;

    /**
     * @var OrderResource
     */
    protected $_orderResource;
    /**
     * @var OrderStatusHelper
     */
    protected $_orderStatusHelper;

    /**
     * @var OrderFactory
     */
    protected $_orderFactory;

    /**
     * @var SapOrderBatchFactory
     */
    protected $_sapOrderBatchFactory;

    /**
     * @var SubscriptionResource
     */
    protected $_subscriptionResource;

    /**
     * @var SubscriptionFactory
     */
    protected $_subscriptionFactory;

    /**
     * @var CreditmemoResource
     */
    protected $_creditmemoResource;

    /**
     * @var ItemCreationFactory
     */
    protected $_itemCreationFactory;

    /**
     * @var RefundOrderInterface
     */
    protected $_refundOrder;

    /**
     * Constructor.
     */
    protected function _construct()
    {
        $this->_init(
            \SMG\SubscriptionApi\Model\ResourceModel\SubscriptionAddonOrder::class
        );
    }

    /**
     * SubscriptionOrder constructor.
     * @param Context $context
     * @param Registry $registry
     * @param LoggerInterface $logger
     * @param SubscriptionFactory $subscriptionFactory
     * @param SubscriptionResource $subscriptionResource
     * @param SubscriptionHelper $subscriptionHelper
     * @param SubscriptionAddonOrderItemCollectionFactory $subscriptionAddonOrderItemCollectionFactory
     * @param OrderFactory $orderFactory
     * @param OrderResource $orderResource
     * @param InvoiceService $invoiceService
     * @param Transaction $transaction
     * @param InvoiceSender $invoiceSender
     * @param SapOrderBatchFactory $sapOrderBatchFactory
     * @param SapOrderBatchResource $sapOrderBatchResource
     * @param OrderStatusHelper $orderStatusHelper
     * @param SubscriptionAddonOrderResource $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        LoggerInterface $logger,
        SubscriptionFactory $subscriptionFactory,
        SubscriptionResource $subscriptionResource,
        SubscriptionHelper $subscriptionHelper,
        SubscriptionAddonOrderItemCollectionFactory $subscriptionAddonOrderItemCollectionFactory,
        OrderFactory $orderFactory,
        OrderResource $orderResource,
        InvoiceService $invoiceService,
        Transaction $transaction,
        InvoiceSender $invoiceSender,
        SapOrderBatchFactory $sapOrderBatchFactory,
        SapOrderBatchResource $sapOrderBatchResource,
        OrderStatusHelper $orderStatusHelper,
        RefundOrderInterface $refundOrder,
        ItemCreationFactory $itemCreationFactory,
        SubscriptionAddonOrderResource $resource,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $registry,
            $resource,
            $resourceCollection,
            $data
        );

        $this->_logger = $logger;
        $this->_subscriptionHelper = $subscriptionHelper;
        $this->_subscriptionFactory = $subscriptionFactory;
        $this->_subscriptionResource = $subscriptionResource;
        $this->_subscriptionAddonOrderItemCollectionFactory = $subscriptionAddonOrderItemCollectionFactory;
        $this->_orderFactory = $orderFactory;
        $this->_orderResource = $orderResource;
        $this->_invoiceService = $invoiceService;
        $this->_transaction = $transaction;
        $this->_invoiceSender = $invoiceSender;
        $this->_sapOrderBatchFactory = $sapOrderBatchFactory;
        $this->_sapOrderBatchResource = $sapOrderBatchResource;
        $this->_orderStatusHelper = $orderStatusHelper;
        $this->_refundOrder = $refundOrder;
        $this->_itemCreationFactory = $itemCreationFactory;
    }

    /**
     * Generate the shipment dates for the subscription order
     * @throws Exception
     */
    public function generateShipDates()
    {
        if (empty($this->getShipStartDate()) || $this->getShipStartDate() == '0000-00-00 00:00:00') {
            $this->generateShipStartDate();
            $this->generateShipEndDate();
        }
    }

    /**
     * Get the subscription for this order.
     *
     * @return Subscription|bool
     */
    public function getSubscription()
    {
        $subscription = $this->_subscriptionFactory->create();
        $this->_subscriptionResource->load($subscription, $this->getData('subscription_entity_id'));

        if (!$subscription->getId()) {
            return false;
        }

        return $subscription;
    }

    /**
     * Get the subscription master ID.
     *
     * @return string
     */
    public function getMasterSubscriptionId()
    {
        $subscription = $this->getSubscription();

        if ($subscription) {
            return $subscription->getData('subscription_id');
        }

        return '';
    }

    /**
     * Get the subscription type.
     *
     * @return string
     */
    public function getSubscriptionType()
    {
        $subscription = $this->getSubscription();

        if ($subscription) {
            return $subscription->getSubscriptionType();
        }

        return '';
    }

    /**
     * Get subscription addon orders
     * @param bool $selectedOnly
     * @return bool|ResourceModel\SubscriptionAddonOrderItem\Collection
     */
    public function getOrderItems(bool $selectedOnly = false)
    {
        // Make sure we have an actual subscription
        if (empty($this->getEntityId())) {
            return false;
        }

        // If subscription orders is local, send them, if not, pull them and send them
        $subscriptionAddonOrderItems = $this->_subscriptionAddonOrderItemCollectionFactory->create();
        $subscriptionAddonOrderItems->addFieldToFilter('subscription_addon_order_entity_id', $this->getEntityId());

        if ($selectedOnly) {
            $subscriptionAddonOrderItems->addFieldToFilter('selected', 1);
        }

        return $subscriptionAddonOrderItems;
    }

    /**
     * Create an invoice for the order.
     *
     * @return bool
     * @throws LocalizedException
     */
    public function createInvoice()
    {
        $order = $this->getOrder();
        $sapOrderBatch = $this->getSapOrderBatch();

        if (!$order || $order->hasInvoices() || !$order->canInvoice() || !$sapOrderBatch) {
            return false;
        }

        $this->_sapOrderBatchResource->save($sapOrderBatch);

        return true;
    }

    /**
     * Get the related sales order record.
     *
     * @return Order|null
     */
    public function getOrder()
    {
        /** @var Order $order */
        $order = $this->_orderFactory->create();
        $this->_orderResource->load($order, $this->getData('sales_order_id'));

        if (!$order->getId()) {
            return null;
        }

        return $order;
    }

    /**
     * Get the related SAP order batch record.
     *
     * @return SapOrderBatch|null
     */
    public function getSapOrderBatch()
    {
        if (!$this->getData('sales_order_id')) {
            return null;
        }

        $sapOrderBatch = $this->_sapOrderBatchFactory->create();
        $this->_sapOrderBatchResource->load($sapOrderBatch, $this->getData('sales_order_id'), 'order_id');

        if (!$sapOrderBatch->getId()) {
            return null;
        }

        return $sapOrderBatch;
    }

    /**
     * See if any add-on items were selected for the subscription.
     *
     * @return bool
     */
    public function isSelected()
    {
        $addOnItem = $this->getOrderItems(true);

        return $addOnItem->count() > 0;
    }

    /**
     * @return string
     */
    public function type()
    {
        return 'addon';
    }

    /**
     * Generate Ship Start Date
     * @throws Exception
     */
    private function generateShipStartDate()
    {
        // Grab the shipment open window from the admin
        $shippingOpenWindow = 0;

        if (!empty($this->_subscriptionHelper->getShipDaysStart())) {
            $shippingOpenWindow = filter_var($this->_subscriptionHelper->getShipDaysStart(), FILTER_SANITIZE_NUMBER_INT);
        }

        // Calculate the Earliest Ship Start Date
        $earliestShipStartDate = new DateTime($this->getData('application_start_date'));
        $earliestShipStartDate->sub(new DateInterval('P' . $shippingOpenWindow . 'D'));

        // Get the subscription and earliest order.
        $subscription = $this->getSubscription();
        $subscriptionOrder = $subscription->getSubscriptionOrders()
            ->setOrder('ship_start_date', 'asc')
            ->fetchItem();

        // If we have an order, update the earliest ship date to match the first
        // order.
        if ($subscriptionOrder && $subscriptionOrder->getId()) {
            $earliestShipStartDate = DateTime::createFromFormat('Y-m-d H:i:s', $subscriptionOrder->getData('ship_start_date'));
        }

        $todayDate = new DateTime(date('Y-m-d 00:00:00'));

        // Take either Earliest Ship Start date or Today, whichever is greater
        $this->setData(
            'ship_start_date',
            $todayDate <= $earliestShipStartDate ? $earliestShipStartDate : $todayDate
        );

        $this->_resource->save($this);
    }

    /**
     * Is Order Currently Shippable
     * @return bool
     * @throws Exception
     */
    public function isCurrentlyShippable()
    {
        // Seasonal subscription, so base ship date on first seasonal order.
        $shipStart = DateTime::createFromFormat('Y-m-d H:i:s', $this->getData('ship_start_date'));
        $today = new DateTime();

        return $today >= $shipStart;
    }

    /**
     * Generate Ship End Date
     * @throws Exception
     */
    private function generateShipEndDate()
    {

        // Grab the shipment open window from the admin
        $shippingCloseWindow = 0;
        if (!empty($this->_subscriptionHelper->getShipDaysEnd())) {
            $shippingCloseWindow = filter_var($this->_subscriptionHelper->getShipDaysEnd(), FILTER_SANITIZE_NUMBER_INT);
        }

        // Calculate the Earliest Ship Start Date
        $earliestShipEndDate = new DateTime($this->getData('application_end_date'));
        $earliestShipEndDate->sub(new DateInterval('P' . $shippingCloseWindow . 'D'));
        $todayDate = new DateTime(date('Y-m-d 00:00:00'));

        // Take either Earliest Ship Start date of Today, whichever is greater
        $shipDate = $todayDate <= $earliestShipEndDate ? $earliestShipEndDate : $todayDate;
        $this->setData('ship_end_date', $shipDate)->save();
    }

    /**
     * Create Credit Memo for Order
     * @throws LocalizedException
     */
    public function createCreditMemo()
    {
        try {
            $order = $this->getOrder();

            if (! $order) {
                // No order, so nothing to credit memo.
                return;
            }

            $creditMemoItems = [];
            $orderItems = $order->getAllItems();

            // Loop through the order items to create credit memo items to
            // refund.
            foreach ($orderItems as $orderItem) {
                $creditMemoItem = $this->_itemCreationFactory->create();
                $creditMemoItem->setQty($orderItem->getQtyInvoiced());
                $creditMemoItem->setOrderItemId($orderItem->getId());

                $creditMemoItems[] = $creditMemoItem;
            }

            // Refund the order.
            $this->_refundOrder->execute($order->getId(), $creditMemoItems);
        } catch (Exception $e) {
            $error = 'Could not create credit memo for order.';
            $this->_logger->error($e->getMessage() . ' - ' . $error);
            throw new LocalizedException(__($error));
        }
    }
}
