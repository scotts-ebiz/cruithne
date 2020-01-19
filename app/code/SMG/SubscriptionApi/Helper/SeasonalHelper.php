<?php

namespace SMG\SubscriptionApi\Helper;

use DateInterval;
use Exception;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Api\Data\AddressInterfaceFactory;
use Magento\Customer\Api\Data\RegionInterface;
use Magento\Customer\Model\AddressFactory;
use Magento\Customer\Model\ResourceModel\Address\Collection as AddressCollection;
use Magento\Directory\Model\RegionFactory;
use Magento\Directory\Model\ResourceModel\Region\Collection as RegionCollection;
use Magento\Framework\Exception\LocalizedException;
use Recurly_Client;
use DateTimeImmutable;
use Recurly_Subscription;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Psr\Log\LoggerInterface;
use SMG\SubscriptionApi\Model\RecurlySubscription;
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
     * @var DateTimeImmutable|false
     */
    protected $_maxShipDate;

    /**
     * @var RegionCollection
     */
    protected $_regionCollection;

    /**
     * @var RegionFactory
     */
    protected $_regionFactory;

    /**
     * @var RegionInterface
     */
    protected $_regionInterface;

    /**
     * @var RecurlySubscription
     */
    protected $_recurlySubscription;

    /**
     * @var AddressRepositoryInterface
     */
    protected $_addressRepository;

    /**
     * @var AddressInterfaceFactory
     */
    protected $_addressInterfaceFactory;

    /**
     * @var AddressFactory
     */
    protected $_addressFactory;

    /**
     * SeasonalHelper constructor.
     * @param Context $context
     * @param LoggerInterface $logger
     * @param RecurlyHelper $recurlyHelper
     * @param SubscriptionOrderHelper $subscriptionOrderHelper
     * @param SubscriptionCollectionFactory $subscriptionCollectionFactory
     * @param SubscriptionOrderCollectionFactory $subscriptionOrderCollectionFactory
     * @param SubscriptionAddonOrderCollectionFactory $subscriptionAddonOrderCollectionFactory
     * @param RecurlySubscription $recurlySubscription
     * @param RegionCollection $regionCollection
     * @param RegionFactory $regionFactory
     * @param RegionInterface $regionInterface
     * @param AddressRepositoryInterface $addressRepository
     * @param AddressInterfaceFactory $addressInterfaceFactory
     * @param AddressFactory $addressFactory
     * @throws Exception
     */
    public function __construct(
        Context $context,
        LoggerInterface $logger,
        RecurlyHelper $recurlyHelper,
        SubscriptionOrderHelper $subscriptionOrderHelper,
        SubscriptionCollectionFactory $subscriptionCollectionFactory,
        SubscriptionOrderCollectionFactory $subscriptionOrderCollectionFactory,
        SubscriptionAddonOrderCollectionFactory $subscriptionAddonOrderCollectionFactory,
        RecurlySubscription $recurlySubscription,
        RegionCollection $regionCollection,
        RegionFactory $regionFactory,
        RegionInterface $regionInterface,
        AddressRepositoryInterface $addressRepository,
        AddressInterfaceFactory $addressInterfaceFactory,
        AddressFactory $addressFactory
    ) {
        parent::__construct($context);

        $this->_logger = $logger;
        $this->_recurlyHelper = $recurlyHelper;
        $this->_subscriptionOrderHelper = $subscriptionOrderHelper;
        $this->_subscriptionCollectionFactory = $subscriptionCollectionFactory;
        $this->_subscriptionOrderCollectionFactory = $subscriptionOrderCollectionFactory;
        $this->_subscriptionAddonOrderCollectionFactory = $subscriptionAddonOrderCollectionFactory;
        $this->_recurlySubscription = $recurlySubscription;
        $this->_regionCollection = $regionCollection;
        $this->_regionFactory = $regionFactory;
        $this->_regionInterface = $regionInterface;
        $this->_addressRepository = $addressRepository;
        $this->_addressInterfaceFactory = $addressInterfaceFactory;
        $this->_addressFactory = $addressFactory;

        $this->_today = new DateTimeImmutable();
        $this->_maxShipDate = $this->_today->sub(new DateInterval('PT90M'));

        // Give 10 days to have a successful process.
        $this->_failDate = $this->_today->sub(new DateInterval('P10D'));

        Recurly_Client::$apiKey = $this->_recurlyHelper->getRecurlyPrivateApiKey();
        Recurly_Client::$subdomain = $this->_recurlyHelper->getRecurlySubdomain();
    }

    /**
     * Process Seasonal Orders
     * @throws Exception
     */
    public function processSeasonalOrders()
    {
        $subscriptionOrders = $this->getSubscriptionOrders();

        if (empty($subscriptionOrders)) {
            // We have nothing to process so end.
            $this->_logger->info('No seasonal orders required processing.');
            return;
        }

        foreach ($subscriptionOrders as $subscriptionOrder) {

            // Check to make sure the order is active (invoiced)
            if (! $this->verifyRecurlySeasonalOrder($subscriptionOrder)) {
                // Order is not ready to process, set a timestamp to be
                // available the next day.
                $cronDate = $subscriptionOrder->getData('next_cron_date')
                    ? $this->_today->add(new DateInterval('P1D'))->format('Y-m-d H:i:s')
                    : $this->_today->add(new DateInterval('PT3H'))->format('Y-m-d H:i:s');

                $subscriptionOrder->setData(
                    'next_cron_date',
                    $cronDate
                )->save();

                continue;
            }

            try {

                // Get the master subscription
                $masterSubscription = $subscriptionOrder->getMasterSubscription();
                if (is_null($masterSubscription)) {
                    throw new LocalizedException(__('Master Subscription could not be found in the database for subscription order / addon_order id ' . $subscriptionOrder->getData('subscription_id')));
                }

                // Get customer from order
                $customer = $masterSubscription->getCustomer();
                if (is_null($customer)) {
                    throw new LocalizedException(__('Customer could not be found in the database for master subscription id ' . $masterSubscription->getData('subscription_id')));
                }

                // Does customer have default billing address
                $defaultBillingAddress = $customer->getDefaultBillingAddress();
                if (is_null($defaultBillingAddress)) {
                    throw new LocalizedException(__('Customer does not have a default billing address. Master subscription id ' . $masterSubscription->getData('subscription_id')));
                }

                // Patch up regionId if null
                if (is_null($defaultBillingAddress->getData('region_id'))) {
                    $this->_logger->info("Subscription {$masterSubscription->getData('subscription_id')} billing address had no regionId. Setting now.");
                    $region = $this->_regionCollection
                        ->addRegionNameFilter($defaultBillingAddress->getData('region'))
                        ->getFirstItem();
                    $defaultBillingAddress->setData('region_id', $region->getId());
                    $defaultBillingAddress->save();
                }

                // Does customer have default shipping address
                $defaultShippingAddress = $customer->getDefaultShippingAddress();
                if (is_null($defaultShippingAddress)) {
                    throw new LocalizedException(__('Customer does not have a default billing address. Master subscription id ' . $masterSubscription->getData('subscription_id')));
                }

                // Patch up regionId if null
                if (is_null($defaultShippingAddress->getData('region_id'))) {
                    $this->_logger->info("Subscription {$masterSubscription->getData('subscription_id')} shipping address had no regionId. Setting now.");
                    $region = $this->_regionCollection
                        ->addRegionNameFilter($defaultShippingAddress->getData('region'))
                        ->getFirstItem();
                    $defaultShippingAddress->setData('region_id', $region->getId());
                    $defaultShippingAddress->save();
                }

                // Process the seasonal subscription.
                $this->_subscriptionOrderHelper->processInvoiceWithSubscriptionId($subscriptionOrder->getData('subscription_id'));
                $this->_logger->debug("Subscription Order: {$subscriptionOrder->getData('subscription_id')} has successfully processed.");
            } catch (Exception $e) {
                $this->_logger->error("Subscription Order: {$subscriptionOrder->getData('subscription_id')} has failed to process. - " . $e->getMessage());
                $subscriptionOrder->setData('subscription_order_status', 'failed')->save();
            }
        }
    }

    /**
     * Get the subscription and subscription add-on orders within the ship date.
     *
     * @return SubscriptionOrder[]|SubscriptionAddonOrder[];
     * @throws Exception
     */
    protected function getSubscriptionOrders()
    {
        $subscriptionOrderCollection = $this->_subscriptionOrderCollectionFactory->create();
        $subscriptionAddonOrderCollection = $this->_subscriptionAddonOrderCollectionFactory->create();

        $subscriptionOrders = $subscriptionOrderCollection
            ->addFilter('subscription_order_status', 'pending')
            ->addFieldToFilter('subscription_id', ['notnull' => true])
            ->addFieldToFilter('ship_start_date', ['notnull' => true])
            ->addFieldToFilter('ship_start_date', ['lteq' => $this->_maxShipDate->format('Y-m-d H:i:s')])
            ->addFieldToFilter(['next_cron_date', 'next_cron_date'], [['lteq' => $this->_today->format('Y-m-d H:i:s')], ['null' => true]])
            ->getItems();

        $subscriptionAddonOrders = $subscriptionAddonOrderCollection
            ->addFilter('subscription_order_status', 'pending')
            ->addFieldToFilter('subscription_id', ['notnull' => true])
            ->addFieldToFilter('ship_start_date', ['notnull' => true])
            ->addFieldToFilter('ship_start_date', ['lteq' => $this->_maxShipDate->format('Y-m-d H:i:s')])
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
     * @throws Exception
     */
    protected function getRecurlySubscriptionFromOrder($order)
    {
        try {
            return Recurly_Subscription::get($order->getData('subscription_id'));
        } catch (\Recurly_Error $error) {
            $this->_logger->error($error->getMessage());
            throw new Exception($error->getMessage());
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
        } catch (Exception $e) {
            $this->_logger->error('Could not verify Recurly subscription - ' . $e->getMessage());

            return false;
        }
    }
}
