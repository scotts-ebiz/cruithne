<?php

namespace SMG\SubscriptionApi\Helper;

use DateInterval;
use Recurly_Client;
use DateTimeImmutable;
use Recurly_Subscription;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Psr\Log\LoggerInterface;
use SMG\SubscriptionApi\Model\ResourceModel\Subscription\CollectionFactory as SubscriptionCollectionFactory;
use SMG\SubscriptionApi\Model\ResourceModel\SubscriptionAddonOrder;
use SMG\SubscriptionApi\Model\ResourceModel\SubscriptionAddonOrder\CollectionFactory as SubscriptionAddonOrderCollectionFactory;
use SMG\SubscriptionApi\Model\ResourceModel\SubscriptionOrder\CollectionFactory as SubscriptionOrderCollectionFactory;
use SMG\SubscriptionApi\Model\SubscriptionOrder;

class SeasonalHelper extends AbstractHelper
{
    /** @var LoggerInterface **/
    protected $_logger;

    /**
     * @var SubscriptionCollectionFactory
     */
    protected $_subscriptionCollectionFactory;

    /**
     * @var SubscriptionOrderCollectionFactory
     */
    protected $_subscriptionOrderCollectionFactory;

    /**
     * @var SubscriptionAddonOrderCollectionFactory
     */
    protected $_subscriptionAddonOrderCollectionFactory;
    /**
     * @var SubscriptionOrderHelper
     */
    protected $_subscriptionOrderHelper;
    /**
     * @var RecurlyHelper
     */
    protected $_recurlyHelper;

    /**
     * @var DateTimeImmutable
     */
    protected $_today;

    /**
     * @var DateTimeImmutable
     */
    protected $_failDate;

    /**
     * SeasonalHelper constructor.
     * @param Context $context
     * @param LoggerInterface $logger
     * @param RecurlyHelper $recurlyHelper
     * @param SubscriptionOrderHelper $subscriptionOrderHelper
     * @param SubscriptionCollectionFactory $subscriptionCollectionFactory
     * @param SubscriptionOrderCollectionFactory $subscriptionOrderCollectionFactory
     * @param SubscriptionAddonOrderCollectionFactory $subscriptionAddonOrderCollectionFactory
     * @throws \Exception
     */
    public function __construct(
        Context $context,
        LoggerInterface $logger,
        RecurlyHelper $recurlyHelper,
        SubscriptionOrderHelper $subscriptionOrderHelper,
        SubscriptionCollectionFactory $subscriptionCollectionFactory,
        SubscriptionOrderCollectionFactory $subscriptionOrderCollectionFactory,
        SubscriptionAddonOrderCollectionFactory $subscriptionAddonOrderCollectionFactory
    ) {
        parent::__construct($context);

        $this->_logger = $logger;
        $this->_recurlyHelper = $recurlyHelper;
        $this->_subscriptionOrderHelper = $subscriptionOrderHelper;
        $this->_subscriptionCollectionFactory = $subscriptionCollectionFactory;
        $this->_subscriptionOrderCollectionFactory = $subscriptionOrderCollectionFactory;
        $this->_subscriptionAddonOrderCollectionFactory = $subscriptionAddonOrderCollectionFactory;

        $this->_today = new DateTimeImmutable();

        // Give 10 days to have a successful process.
        $this->_failDate = $this->_today->sub(new DateInterval('P10D'));

        Recurly_Client::$apiKey = $this->_recurlyHelper->getRecurlyPrivateApiKey();
        Recurly_Client::$subdomain = $this->_recurlyHelper->getRecurlySubdomain();
    }

    public function processSeasonalOrders()
    {
        $orders = $this->getOrders();

        if (empty($orders)) {
            // We have nothing to process so end.
            $this->_logger->info('No seasonal orders required processing.');
            exit;
        }

        foreach ($orders as $order) {
            if (! $this->verifyRecurlySeasonalOrder($order)) {
                // Order is not ready to process, set a timestamp to be
                // available the next day.
                $cronDate = $order->getData('next_cron_date')
                    ? $this->_today->add(new DateInterval('P1D'))->format('Y-m-d H:i:s')
                    : $this->_today->add(new DateInterval('PT3H'))->format('Y-m-d H:i:s');

                $order->setData(
                    'next_cron_date',
                    $cronDate
                )->save();

                continue;
            }

            try {
                // Process the seasonal subscription.
                $this->_subscriptionOrderHelper->processInvoiceWithSubscriptionId($order->getData('subscription_id'));
            } catch (\Exception $e) {
                $this->_logger->error("Subscription Order: {$order->getData('subscription_id')} has failed to process. - " . $e->getMessage());
                $order->setData('subscription_order_status', 'failed')->save();

                continue;
            }
        }
    }

    /**
     * Get the subscription and subscription add-on orders within the ship date.
     *
     * @return SubscriptionOrder[]|SubscriptionAddonOrder[];
     * @throws \Exception
     */
    protected function getOrders()
    {
        $subscriptionOrderCollection = $this->_subscriptionOrderCollectionFactory->create();
        $subscriptionAddonOrderCollection = $this->_subscriptionAddonOrderCollectionFactory->create();

        $subscriptionOrders = $subscriptionOrderCollection
            ->addFilter('subscription_order_status', 'pending')
            ->addFieldToFilter('subscription_id', ['notnull' => true])
            ->addFieldToFilter('ship_start_date', ['lteq' => $this->_today->format('Y-m-d H:i:s')])
            ->addFieldToFilter(['next_cron_date', 'next_cron_date'], [['lteq' => $this->_today->format('Y-m-d H:i:s')], ['null' => true]])
            ->getItems();

        $subscriptionAddonOrders = $subscriptionAddonOrderCollection
            ->addFilter('subscription_order_status', 'pending')
            ->addFieldToFilter('subscription_id', ['notnull' => true])
            ->addFieldToFilter('ship_start_date', ['lteq' => $this->_today->format('Y-m-d H:i:s')])
            ->addFieldToFilter(['next_cron_date', 'next_cron_date'], [['lteq' => $this->_today->format('Y-m-d H:i:s')], ['null' => true]])
            ->getItems();

        $combinedOrders = array_merge($subscriptionOrders, $subscriptionAddonOrders);
        $orders = [];
        $count = 0;

        foreach ($combinedOrders as $order) {
            /* @var SubscriptionOrder|SubscriptionAddonOrder $order */
            if ($order->getSubscriptionType() == 'annual') {
                continue;
            }

            $shipDate = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $order->getData('ship_start_date'));

            // If a ship date is older than 10 days, it means something is
            // causing the process to fail, so lets mark it as such.
            if ($shipDate < $this->_failDate) {
                $this->_logger->error("Subscription order {$order->getData('subscription_id')} has failed to process.");
                $order->setData('subscription_order_status', 'failed')->save();
                continue;
            }

            $orders[] = $order;
            $count++;

            // We have 25 records to work with, so break the loop.
            if ($count == 25) {
                break;
            }
        }

        return $orders;
    }

    /**
     * Get the Recurly Subscription for the order.
     *
     * @param SubscriptionOrder|SubscriptionAddonOrder $order
     * @return Recurly_Subscription
     * @throws \Exception
     */
    protected function getRecurlySubscriptionFromOrder($order)
    {
        try {
            return Recurly_Subscription::get($order->getData('subscription_id'));
        } catch (\Recurly_Error $error) {
            $this->_logger->error($error->getMessage());
            throw new \Exception($error->getMessage());
        }
    }

    /**
     * Verify the Recurly subscription was activated within the shipping window.
     *
     * @param SubscriptionOrder|SubscriptionAddonOrder $order
     * @return bool
     */
    protected function verifyRecurlySeasonalOrder($order)
    {
        try {
            $recurlySubscription = $this->getRecurlySubscriptionFromOrder($order);

            if ($recurlySubscription
                && $recurlySubscription->state == 'active'
                && $recurlySubscription->activated_at < $this->_today
                && $recurlySubscription->activated_at > $this->_failDate
            ) {
                return true;
            }

            return false;
        } catch (\Exception $e) {
            return false;
        }
    }
}
