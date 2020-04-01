<?php

namespace SMG\SubscriptionApi\Helper;

use Exception;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\ResourceModel\Order as OrderResource;
use Magento\Sales\Model\ResourceModel\Order\Collection as OrderCollection;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Psr\Log\LoggerInterface;
use Recurly_Client;
use Recurly_Error;
use Recurly_Invoice;
use Recurly_Subscription;
use SMG\Sap\Model\ResourceModel\SapOrderBatch as SapOrderBatchResource;
use SMG\Sap\Model\ResourceModel\SapOrderBatch\CollectionFactory as SapOrderBatchCollectionFactory;
use SMG\SubscriptionApi\Model\ResourceModel\Subscription as SubscriptionResource;
use SMG\SubscriptionApi\Model\ResourceModel\SubscriptionAddonOrder as SubscriptionAddonOrderResource;
use SMG\SubscriptionApi\Model\ResourceModel\SubscriptionAddonOrder\CollectionFactory as SubscriptionAddonOrderCollectionFactory;
use SMG\SubscriptionApi\Model\ResourceModel\SubscriptionOrder as SubscriptionOrderResource;
use SMG\SubscriptionApi\Model\ResourceModel\SubscriptionOrder\CollectionFactory as SubscriptionOrderCollectionFactory;
use SMG\SubscriptionApi\Model\Subscription;
use SMG\SubscriptionApi\Model\SubscriptionAddonOrder;
use SMG\SubscriptionApi\Model\SubscriptionOrder;

class CancelSubscriptionHelper extends AbstractHelper
{
    /**
     * @var LoggerInterface
     */
    protected $_logger;

    /**
     * @var OrderCollectionFactory
     */
    protected $_orderCollectionFactory;

    /**
     * @var RecurlyHelper
     */
    protected $_recurlyHelper;

    /**
     * @var SapOrderBatchCollectionFactory
     */
    protected $_sapOrderBatchCollectionFactory;

    /**
     * @var SapOrderBatchResource
     */
    protected $_sapOrderBatchResource;

    /**
     * @var SubscriptionOrderCollectionFactory
     */
    protected $_subscriptionOrderCollectionFactory;

    /**
     * @var SubscriptionAddonOrderCollectionFactory
     */
    protected $_subscriptionAddonOrderCollectionFactory;

    /**
     * @var OrderResource
     */
    protected $_orderResource;

    /**
     * @var SubscriptionResource
     */
    protected $_subscriptionResource;

    /**
     * @var SubscriptionOrderResource
     */
    protected $_subscriptionOrderResource;

    /**
     * @var SubscriptionAddonOrderResource
     */
    protected $_subscriptionAddonOrderResource;

    /**
     * CancelHelper constructor.
     *
     * @param Context $context
     * @param LoggerInterface $logger
     * @param OrderCollectionFactory $orderCollectionFactory
     * @param OrderResource $orderResource
     * @param RecurlyHelper $recurlyHelper
     * @param SapOrderBatchCollectionFactory $sapOrderBatchCollectionFactory
     * @param SapOrderBatchResource $sapOrderBatchResource
     * @param SubscriptionOrderCollectionFactory $subscriptionOrderCollectionFactory
     * @param SubscriptionAddonOrderCollectionFactory $subscriptionAddonOrderCollectionFactory
     * @param SubscriptionResource $subscriptionResource
     * @param SubscriptionOrderResource $subscriptionOrderResource
     * @param SubscriptionAddonOrderResource $subscriptionAddonOrderResource
     */
    public function __construct(
        Context $context,
        LoggerInterface $logger,
        OrderCollectionFactory $orderCollectionFactory,
        OrderResource $orderResource,
        RecurlyHelper $recurlyHelper,
        SapOrderBatchCollectionFactory $sapOrderBatchCollectionFactory,
        SapOrderBatchResource $sapOrderBatchResource,
        SubscriptionOrderCollectionFactory $subscriptionOrderCollectionFactory,
        SubscriptionAddonOrderCollectionFactory $subscriptionAddonOrderCollectionFactory,
        SubscriptionResource $subscriptionResource,
        SubscriptionOrderResource $subscriptionOrderResource,
        SubscriptionAddonOrderResource $subscriptionAddonOrderResource
    ) {
        parent::__construct($context);

        $this->_logger = $logger;
        $this->_orderCollectionFactory = $orderCollectionFactory;
        $this->_orderResource = $orderResource;
        $this->_recurlyHelper = $recurlyHelper;
        $this->_sapOrderBatchCollectionFactory = $sapOrderBatchCollectionFactory;
        $this->_sapOrderBatchResource = $sapOrderBatchResource;
        $this->_subscriptionOrderCollectionFactory = $subscriptionOrderCollectionFactory;
        $this->_subscriptionAddonOrderCollectionFactory = $subscriptionAddonOrderCollectionFactory;
        $this->_subscriptionResource = $subscriptionResource;
        $this->_subscriptionOrderResource = $subscriptionOrderResource;
        $this->_subscriptionAddonOrderResource = $subscriptionAddonOrderResource;

        // Configure Recurly Client
        Recurly_Client::$apiKey = $this->_recurlyHelper->getRecurlyPrivateApiKey();
        Recurly_Client::$subdomain = $this->_recurlyHelper->getRecurlySubdomain();
    }

    /**
     * Cancel the given subscription.
     *
     * @param Subscription $subscription
     * @throws LocalizedException
     */
    public function cancel($subscription)
    {
        // Subscription is not active so nothing to cancel.
        if ($subscription->getData('subscription_status' != 'active')) {
            return;
        }

        try {
            $orders = $this->getOrders($subscription);
            $isAnnual = $subscription->getData('subscription_type') == 'annual';
            $refundAmount = 0;
            $ordersRefunded = 0;

            // Loop through the orders and refund/cancel as necessary.
            foreach ($orders as $order) {
                // Get the subscription order.
                try {
                    /** @var Order $order */
                    if ($order->getData('subscription_addon')) {
                        /** @var SubscriptionAddonOrder $subscriptionOrder */
                        $subscriptionOrder = $this->_subscriptionAddonOrderCollectionFactory
                            ->create()
                            ->addFieldToFilter('sales_order_id', $order->getEntityId())
                            ->getFirstItem();
                    } else {
                        /** @var SubscriptionOrder $subscriptionOrder */
                        $subscriptionOrder = $this->_subscriptionOrderCollectionFactory
                            ->create()
                            ->addFieldToFilter('sales_order_id', $order->getEntityId())
                            ->getFirstItem();
                    }
                } catch (Exception $e) {
                    // Could not find the subscription order, so continue.
                    $error = 'Could not find subscription order to cancel. Sales Order: ' . $order->getEntityId();
                    $this->_logger->error($error);
                    $this->_logger->error($e->getMessage());

                    continue;
                }

                if ($order->hasShipments()) {
                    // Order has shipped and cannot be refunded, however, we
                    // should still cancel the Recurly subscription.
                    $this->cancelRecurlySubscription($subscriptionOrder, false);

                    continue;
                }

                if ($order->hasInvoices()) {
                    // Cancel the subscription.
                    $this->cancelRecurlySubscription($subscriptionOrder, !$isAnnual);

                    // Order has been invoiced, but not shipped, so let's make
                    // it refundable.
                    $refundAmount += $order->getGrandTotal();
                    $ordersRefunded++;

                    // Create the credit memo for the subscription order.
                    $subscriptionOrder->createCreditMemo();
                } else {
                    // Order has not shipped or been invoiced, so cancel the
                    // subscription. No refunds are necessary.
                    $this->cancelRecurlySubscription($subscriptionOrder, false);
                }

                // Mark subscription order as canceled.
                $this->setSubscriptionOrderCanceledStatus($subscriptionOrder);

                // Mark the order as canceled.
                $order->setData('status', 'canceled');
                $this->_orderResource->save($order);
            }

            // If this is an annual subscription
            // Seasonal master subscriptions should not have an amount to get
            // refunded.
            if ($ordersRefunded == $orders->count()) {
                // Refund full amount.
                $this->cancelMasterRecurlySubscription($subscription);
            } else {
                // Refund a partial amount.
                $this->cancelMasterRecurlySubscription($subscription, $refundAmount);
            }

            // Mark the subscription as canceled.
            $subscription->setData('subscription_status', 'canceled');
            $this->_subscriptionResource->save($subscription);
        } catch (Exception $e) {
            // Failed to cancel subscription.
            $error = 'Could not cancel subscription: "' . $subscription->getData('subscription_id') . '"';
            $this->_logger->error($e->getMessage());

            throw new LocalizedException(__($error));
        }
    }

    /**
     * Get the orders for the given subscription.
     *
     * @param Subscription $subscription
     * @return OrderCollection
     * @throws LocalizedException
     */
    protected function getOrders($subscription)
    {
        // Get the invoiced orders that have not yet shipped.
        try {
            return $this->_orderCollectionFactory
                ->create()
                ->addFieldToFilter(
                    'master_subscription_id',
                    $subscription->getData('subscription_id')
                );
        } catch (Exception $e) {
            $error = 'There was an issue finding orders for subscription ' . $subscription->getData('subscription_id');
            $this->_logger->error($e->getMessage());

            throw new LocalizedException(__($error));
        }
    }

    /**
     * Cancel the Recurly subscription for the given order.
     *
     * @param SubscriptionOrder|SubscriptionAddonOrder $subscriptionOrder
     * @param bool $refund
     * @throws LocalizedException
     */
    protected function cancelRecurlySubscription($subscriptionOrder, bool $refund = false)
    {
        try {
            $subscription = $this->loadRecurlySubscription($subscriptionOrder->getData('subscription_id'));

            // The subscription has already been terminated or completed.
            if (! in_array($subscription->state, ['active', 'future'])) {
                return;
            }

            // No refund needed, so just terminate the subscription.
            if (! $refund) {
                $subscription->terminateWithoutRefund();

                // Update the subscription order status.
                $this->setSubscriptionOrderCanceledStatus($subscriptionOrder);

                return;
            }

            // Load the invoice.
            $invoice = null;

            try {
                $invoice = $subscription->invoice->get();
            } catch (Exception $e) {
                $this->_logger->error("Could not find refundable invoice for subscription {$subscription->uuid}.");
            }

            // Find the invoice line item for the subscription.
            $lineItem = array_values(array_filter($invoice->line_items, function ($item) use ($subscription) {
                return $item->product_code == $subscription->plan->plan_code;
            }));

            if (count($lineItem)) {
                // Refund the line item.
                try {
                    $refundItem = $lineItem[0]->toRefundAttributes();
                    $invoice->refund([$refundItem], 'transaction_first');
                } catch (Exception $e) {
                    $this->_logger->error("Could not refund line item for subscription {$subscription->uuid}.");
                }
            } else {
                $this->_logger->error("Could not find invoice line item when refunding subscription {$subscription->uuid}.");
            }

            // Terminate the subscription.
            $subscription->terminateWithoutRefund();

            // Update the subscription order status.
            $this->setSubscriptionOrderCanceledStatus($subscriptionOrder);
        } catch (Exception $e) {
            // Could not terminate subscription.
            $error = 'Could not cancel subscription "' . $subscriptionOrder->getData('subscription_id') . '"';
            $this->_logger->error($e->getMessage());

            throw new LocalizedException(__($error));
        }
    }

    /**
     * Find the subscription with the given ID.
     *
     * @param string $subscriptionID
     *
     * @return Recurly_Subscription
     * @throws LocalizedException
     */
    protected function loadRecurlySubscription($subscriptionID)
    {
        try {
            $subscription = Recurly_Subscription::get($subscriptionID);

            if (! $subscription) {
                throw new Recurly_Error('');
            }

            return $subscription;
        } catch (Recurly_Error $e) {
            $error = 'Could not find Recurly subscription "' . $subscriptionID . '"';
            $this->_logger->error($e->getMessage());

            throw new LocalizedException(__($error));
        }
    }

    /**
     * @param Subscription $subscription
     * @param int $amount Passing null will refund entire subscription
     * @throws LocalizedException
     */
    protected function cancelMasterRecurlySubscription(Subscription $subscription, $amount = null)
    {
        // Convert the provided amount to cents if it exists.
        if ($amount > 0) {
            $amount = $this->convertAmountToCents($amount);
        }

        $subscriptionID = $subscription->getData('subscription_id');

        try {
            $recurlySubscription = $this->loadRecurlySubscription($subscriptionID);

            if (! in_array($recurlySubscription->state, ['active', 'future'])) {
                return;
            }

            // Terminate the subscription.
            // We cannot terminate and refund because the subscription may have
            // an add-on order which appears on the same invoice but on another
            // subscription plan in Recurly.
            $recurlySubscription->terminateWithoutRefund();

            if ($subscription->getData('subscription_type') == 'annual') {
                /** @var Recurly_Invoice $invoice */
                $invoice = Recurly_Invoice::get($subscription->getData('recurly_invoice'));

                if (is_null($amount)) {
                    $invoice->refundAmount($this->convertAmountToCents($subscription->getData('paid')), 'transaction_first');
                } else {
                    $invoice->refundAmount($amount, 'transaction_first');
                }
            }
        } catch (Exception $e) {
            $error = 'Could not terminate master Recurly subscription "' . $subscriptionID . '"';
            $this->_logger->error($e->getMessage());

            throw new LocalizedException(__($error));
        }
    }

    /**
     * Mark the SAP Order Batch as canceled.
     *
     * @param Order $order
     */
    protected function cancelSapOrder(Order $order)
    {
        $sapOrderBatchCollection = $this->_sapOrderBatchCollectionFactory->create();
        $sapOrderBatchCollection
            ->addFieldToFilter('order_id', $order->getEntityId())
            ->walk(function ($sapOrderBatch) {
                $sapOrderBatch->setData('is_order', 0);
                $this->_sapOrderBatchResource->save($sapOrderBatch);
            });
    }

    /**
     * Convert order grand total from dollars to cents
     *
     * @param int|float $amount
     * @return int
     */
    protected function convertAmountToCents($amount)
    {
        $cents = number_format((float) $amount * 100, 2, '.', '');

        if (explode('.', $cents)[1] > 0) {
            $cents = (int) $cents + 1;
        }

        return (int) $cents;
    }

    /**
     * Set the given order to canceled.
     *
     * @param SubscriptionOrder | SubscriptionAddonOrder $subscriptionOrder
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    protected function setSubscriptionOrderCanceledStatus($subscriptionOrder): void
    {
        $subscriptionOrder->setData('subscription_order_status', 'canceled');

        if ($subscriptionOrder instanceof SubscriptionAddonOrder) {
            $this->_subscriptionOrderResource->save($subscriptionOrder);
        } else {
            $this->_subscriptionAddonOrderResource->save($subscriptionOrder);
        }
    }
}
