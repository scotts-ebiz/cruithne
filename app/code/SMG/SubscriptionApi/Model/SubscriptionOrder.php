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
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\ResourceModel\Order as OrderResource;
use Magento\Sales\Model\Service\InvoiceService;
use Psr\Log\LoggerInterface;
use SMG\Api\Helper\OrderStatusHelper;
use SMG\Sap\Model\ResourceModel\SapOrderBatch as SapOrderBatchResource;
use SMG\Sap\Model\SapOrderBatch;
use SMG\Sap\Model\SapOrderBatchFactory;
use SMG\SubscriptionApi\Helper\SubscriptionHelper;
use SMG\SubscriptionApi\Model\ResourceModel\Subscription as SubscriptionResource;
use SMG\SubscriptionApi\Model\ResourceModel\SubscriptionOrder as SubscriptionOrderResource;
use SMG\SubscriptionApi\Model\ResourceModel\SubscriptionOrderItem\CollectionFactory as SubscriptionOrderItemCollectionFactory;
use Magento\Sales\Api\RefundOrderInterface;
use Magento\Sales\Model\Order\Creditmemo\ItemCreationFactory;

/**
 * Class SubscriptionOrder
 * @package SMG\SubscriptionApi\Model
 */
class SubscriptionOrder extends AbstractModel
{
    /** @var SubscriptionHelper */
    protected $_subscriptionHelper;

    /** @var SubscriptionOrderItemCollectionFactory */
    protected $_subscriptionOrderItemCollectionFactory;

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
     * @var ItemCreationFactory
     */
    protected $_itemCreationFactory;

    /**
     * @var RefundOrderInterface
     */
    protected $_refundOrder;

    /*** Subscription order states */

    const STATE_CANCELED = 'canceled';

    const STATE_COMPLETE = 'complete';

    const STATE_PENDING = 'pending';

    const STATE_FAILED = 'failed';

    /**
     * Constructor.
     */
    protected function _construct()
    {
        $this->_init(
            \SMG\SubscriptionApi\Model\ResourceModel\SubscriptionOrder::class
        );
    }

    /**
     * SubscriptionOrder constructor.
     * @param Context $context
     * @param Registry $registry
     * @param LoggerInterface $logger
     * @param SubscriptionHelper $subscriptionHelper
     * @param SubscriptionFactory $subscriptionFactory
     * @param SubscriptionResource $subscriptionResource
     * @param SubscriptionOrderItemCollectionFactory $subscriptionOrderItemCollectionFactory
     * @param OrderFactory $orderFactory
     * @param OrderResource $orderResource
     * @param InvoiceService $invoiceService
     * @param Transaction $transaction
     * @param InvoiceSender $invoiceSender
     * @param SapOrderBatchFactory $sapOrderBatchFactory
     * @param SapOrderBatchResource $sapOrderBatchResource
     * @param OrderStatusHelper $orderStatusHelper
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        LoggerInterface $logger,
        SubscriptionHelper $subscriptionHelper,
        SubscriptionFactory $subscriptionFactory,
        SubscriptionResource $subscriptionResource,
        SubscriptionOrderItemCollectionFactory $subscriptionOrderItemCollectionFactory,
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
        SubscriptionOrderResource $resource,
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
        $this->_subscriptionOrderItemCollectionFactory = $subscriptionOrderItemCollectionFactory;
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
     * Get the subscription type.
     *
     * @return string
     */
    public function getSubscriptionType()
    {
        $subscription = $this->getSubscription();

        if ($subscription) {
            return $subscription->getData('subscription_type');
        }

        return '';
    }

    /**
     * Get subscription orders
     * @return mixed
     */
    public function getOrderItems()
    {

        // Make sure we have an actual subscription
        if (empty($this->getEntityId())) {
            return false;
        }

        // If subscription orders is local, send them, if not, pull them and send them
        if (!isset($this->_subscriptionOrderItems)) {
            $subscriptionOrderItems = $this->_subscriptionOrderItemCollectionFactory->create();
            $subscriptionOrderItems->addFieldToFilter('subscription_order_entity_id', $this->getEntityId());
            $this->_subscriptionOrderItems = $subscriptionOrderItems;
        }

        return $this->_subscriptionOrderItems;
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

        $this->_orderStatusHelper->invoiceOffline($order, $sapOrderBatch);
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
        $salesOrderId = $this->getData('sales_order_id');

        if (!empty($salesOrderId)) {
            $this->_orderResource->load($order, $this->getData('sales_order_id'));
            if ($order->getId()) {
                return $order;
            }
        }

        return null;
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
     * @return string
     */
    public function type()
    {
        return 'seasonal';
    }

    /**
     * Generate Ship Start Date
     * @throws Exception
     */
    protected function generateShipStartDate()
    {
        // Grab the shipment open window from the admin
        $shippingOpenWindow = 0;

        if (!empty($this->_subscriptionHelper->getShipDaysStart())) {
            $shippingOpenWindow = filter_var($this->_subscriptionHelper->getShipDaysStart(), FILTER_SANITIZE_NUMBER_INT);
        }

        // Calculate the Earliest Ship Start Date
        $earliestShipStartDate = new DateTime($this->getApplicationStartDate());
        $earliestShipStartDate->sub(new DateInterval('P' . $shippingOpenWindow . 'D'));
        $todayDate = new DateTime(date('Y-m-d 00:00:00'));

        // Take either Earliest Ship Start date of Today, whichever is greater
        if ($todayDate <= $earliestShipStartDate) {
            $this->setShipStartDate($earliestShipStartDate);
        } else {
            $this->setShipStartDate($todayDate);
        }
    }

    /**
     * Is Order Currently Shippable
     * @return bool
     * @throws Exception
     */
    public function isCurrentlyShippable()
    {
        $today = new DateTime();
        $shipStart = DateTime::createFromFormat('Y-m-d H:i:s', $this->getShipStartDate());

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
        $earliestShipEndDate = new DateTime($this->getApplicationEndDate());
        $earliestShipEndDate->sub(new DateInterval('P' . $shippingCloseWindow . 'D'));
        $todayDate = new DateTime(date('Y-m-d 00:00:00'));

        // Take either Earliest Ship Start date of Today, whichever is greater
        if ($todayDate <= $earliestShipEndDate) {
            $this->setShipEndDate($earliestShipEndDate->format('Y-m-d H:i:s'));
        } else {
            $this->setShipEndDate($todayDate->format('Y-m-d H:i:s'));
        }

        $this->_resource->save($this);
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
