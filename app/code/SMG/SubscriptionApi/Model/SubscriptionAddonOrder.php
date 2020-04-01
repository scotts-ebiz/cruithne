<?php

namespace SMG\SubscriptionApi\Model;

use DateTime;
use Exception;
use DateInterval;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\DB\Transaction;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Creditmemo;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\OrderRepository;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Magento\Sales\Model\Service\InvoiceService;
use Psr\Log\LoggerInterface;
use SMG\Sap\Model\ResourceModel\SapOrderBatch\CollectionFactory as SapOrderBatchCollectionFactory;
use SMG\Sap\Model\SapOrderBatch;
use SMG\SubscriptionApi\Helper\SubscriptionHelper;
use SMG\SubscriptionApi\Model\ResourceModel\Subscription\CollectionFactory as SubscriptionCollectionFactory;
use SMG\SubscriptionApi\Model\ResourceModel\SubscriptionAddonOrderItem\CollectionFactory as SubscriptionAddonOrderItemCollectionFactory;

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

    /** @var OrderRepository */
    protected $_orderRepository;

    /** @var Order */
    protected $_order;

    /** @var OrderCollectionFactory */
    protected $_orderCollectionFactory;

    /** @var InvoiceService */
    protected $_invoiceService;

    /** @var Transaction */
    protected $_transaction;

    /** @var InvoiceSender */
    protected $_invoiceSender;

    /** @var SapOrderBatch */
    protected $_sapOrderBatch;

    /** @var SapOrderBatchCollectionFactory */
    protected $_sapOrderBatchCollectionFactory;

    /**
     * @var SubscriptionCollectionFactory
     */
    protected $_subscriptionCollectionFactory;

    /**
     * @var Subscription|null
     */
    protected $_subscription;

    /**
     * @var Order\CreditmemoFactory
     */
    protected $_creditmemoFactory;

    /**
     * @var LoggerInterface
     */
    protected $_logger;

    /**
     * @var Subscription
     */
    protected $_masterSubscription;

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
     * @param Order\CreditmemoFactory $creditmemoFactory
     * @param SubscriptionCollectionFactory $subscriptionCollectionFactory
     * @param SubscriptionHelper $subscriptionHelper
     * @param SubscriptionAddonOrderItemCollectionFactory $subscriptionAddonOrderItemCollectionFactory
     * @param OrderRepository $orderRepository
     * @param OrderCollectionFactory $orderCollectionFactory
     * @param InvoiceService $invoiceService
     * @param Transaction $transaction
     * @param InvoiceSender $invoiceSender
     * @param SapOrderBatchCollectionFactory $sapOrderBatchCollectionFactory
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        LoggerInterface $logger,
        Order\CreditmemoFactory $creditmemoFactory,
        SubscriptionCollectionFactory $subscriptionCollectionFactory,
        SubscriptionHelper $subscriptionHelper,
        SubscriptionAddonOrderItemCollectionFactory $subscriptionAddonOrderItemCollectionFactory,
        OrderRepository $orderRepository,
        OrderCollectionFactory $orderCollectionFactory,
        InvoiceService $invoiceService,
        Transaction $transaction,
        InvoiceSender $invoiceSender,
        SapOrderBatchCollectionFactory $sapOrderBatchCollectionFactory,
        AbstractResource $resource = null,
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
        $this->_creditmemoFactory = $creditmemoFactory;
        $this->_subscriptionHelper = $subscriptionHelper;
        $this->_subscriptionAddonOrderItemCollectionFactory = $subscriptionAddonOrderItemCollectionFactory;
        $this->_orderCollectionFactory = $orderCollectionFactory;
        $this->_invoiceService = $invoiceService;
        $this->_transaction = $transaction;
        $this->_invoiceSender = $invoiceSender;
        $this->_sapOrderBatchCollectionFactory = $sapOrderBatchCollectionFactory;
        $this->_orderRepository = $orderRepository;
        $this->_subscriptionCollectionFactory = $subscriptionCollectionFactory;
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
        if ($this->_subscription) {
            return $this->_subscription;
        }

        $subscription = $this->_subscriptionCollectionFactory
            ->create()
            ->addFieldToFilter('entity_id', $this->getData('subscription_entity_id'))
            ->getFirstItem();

        if (is_null($subscription) || ! $subscription->getId()) {
            return false;
        }

        $this->_subscription = $subscription;

        return $this->_subscription;
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
     * Get Master Subscription
     */
    public function getMasterSubscription()
    {
        $masterSubscriptionId = $this->getMasterSubscriptionId();

        if (is_null($this->_masterSubscription)) {
            $this->_masterSubscription = $this->_subscriptionCollectionFactory->create()
                ->addFilter('subscription_id', $masterSubscriptionId)
                ->getFirstItem();
        }

        return $this->_masterSubscription;
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
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function createInvoice()
    {
        $order = $this->getOrder();

        if (! $order || $order->hasInvoices() || ! $order->canInvoice()) {
            return false;
        }

        $invoice = $this->_invoiceService->prepareInvoice($order);

        if (! $invoice->getTotalQty()) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('You can\'t create an invoice without products.')
            );
        }

        $invoice->setRequestedCaptureCase(\Magento\Sales\Model\Order\Invoice::CAPTURE_OFFLINE);
        $invoice->register();
        $transaction = $this->_transaction
            ->addObject($invoice)
            ->addObject($invoice->getOrder());
        $transaction->save();

        // For some reason, this causes the response to come back with a 500 code.
        $this->_invoiceSender->send($invoice);

        $order->addStatusHistoryComment(
            __('Notified customer about invoice #%1.', $invoice->getId())
        )
            ->setIsCustomerNotified(false)
            ->save();

        $today = date('Y-m-d H:i:s');
        $sapOrderBatch = $this->getSapOrderBatch();

        if ($sapOrderBatch) {
            $sapOrderBatch->setData('is_capture', true);
            $sapOrderBatch->setData('capture_process_date', $today);
            $sapOrderBatch->save();
        }

        return true;
    }

    /**
     * Get the related sales order record.
     *
     * @return \Magento\Framework\DataObject|Order|null
     */
    public function getOrder()
    {
        if ($this->_order) {
            return $this->_order;
        }

        try {
            $this->_order = $this->_orderCollectionFactory
                ->create()
                ->addFieldToFilter('entity_id', $this->getData('sales_order_id'))
                ->getFirstItem();

            if (! $this->_order || ! $this->_order->getId()) {
                return null;
            }

            return $this->_order;
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Get the related SAP order batch record.
     *
     * @return SapOrderBatch|null
     */
    public function getSapOrderBatch()
    {
        if ($this->_sapOrderBatch) {
            return $this->_sapOrderBatch;
        }

        try {
            $this->_sapOrderBatch = $this->_sapOrderBatchCollectionFactory
                ->create()
                ->addFieldToFilter('sales_order_id', $this->getData('sales_order_id'))
                ->getFirstItem();

            if (! $this->_sapOrderBatch->getId()) {
                return null;
            }

            return $this->_sapOrderBatch;
        } catch (Exception $e) {
            return null;
        }
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

        if (! empty($this->_subscriptionHelper->getShipDaysStart())) {
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
        )->save();
    }

    /**
     * Is Order Currently Shippable
     * @return bool
     * @throws Exception
     */
    public function isCurrentlyShippable()
    {
        // Seasonal subscription, so base ship date on first seasonal order.
        if ($this->getSubscriptionType() == 'seasonal') {
            $shipStart = DateTime::createFromFormat('Y-m-d H:i:s', $this->getData('ship_start_date'));
            $today = new DateTime();

            return $today >= $shipStart;
        }

        return true;
    }

    /**
     * Generate Ship End Date
     * @throws Exception
     */
    private function generateShipEndDate()
    {

        // Grab the shipment open window from the admin
        $shippingCloseWindow = 0;
        if (! empty($this->_subscriptionHelper->getShipDaysEnd())) {
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
            /** @var Order $order */
            $order = $this->getOrder();
            $invoices = $order->getInvoiceCollection();

            /** @var Invoice $invoice */
            foreach ($invoices as $invoice) {
                /** @var Creditmemo $creditmemo */
                $creditmemo = $this->_creditmemoFactory->createByOrder($order);
                $creditmemo->setInvoice($invoice);
                $creditmemo->save();
            }
        } catch (Exception $e) {
            $error = 'Could not create credit memo for order.';
            $this->_logger->error($e->getMessage() . ' - ' . $error);
            throw new LocalizedException(__($error));
        }
    }
}
