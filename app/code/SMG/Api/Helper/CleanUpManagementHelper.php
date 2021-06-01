<?php

namespace SMG\Api\Helper;

use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Model\ResourceModel\Product as ProductResource;
use Magento\CatalogSearch\Model\AdvancedFactory;
use Magento\CatalogSearch\Model\ResourceModel\Advanced as AdvancedResource;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Model\ResourceModel\Customer as CustomerResource;
use Magento\Directory\Model\ResourceModel\Region\CollectionFactory as RegionCollectionFactory;
use Magento\Framework\DB\Transaction as Transaction;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Quote\Model\Quote\AddressFactory;
use Magento\Quote\Model\QuoteFactory;
use Magento\Quote\Model\ResourceModel\Quote as QuoteResource;
use Magento\Sales\Api\Data\ShipmentItemCreationInterfaceFactory;
use Magento\Sales\Api\Data\ShipmentTrackCreationInterfaceFactory;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\ShipOrderInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\AddressRepository as RepositoryAddress;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender as InvoiceSender;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\ResourceModel\Order as OrderResource;
use Magento\Sales\Model\ResourceModel\Order\Item as ItemResource;
use Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory as OrderItemCollectionFactory;
use Magento\Sales\Model\Service\InvoiceService as InvoiceService;
use Psr\Log\LoggerInterface;
use SMG\BackendService\Helper\Data as Config;
use SMG\OfflineShipping\Model\ResourceModel\ShippingConditionCode as ShippingConditionCodeResource;
use SMG\OfflineShipping\Model\ShippingConditionCodeFactory;
use SMG\OrderDiscount\Helper\Data as DiscountHelper;
use SMG\SubscriptionApi\Helper\RecurlyHelper;
use SMG\SubscriptionApi\Model\ResourceModel\Subscription as SubscriptionResource;
use SMG\SubscriptionApi\Model\ResourceModel\Subscription\CollectionFactory as SubscriptionCollectionFactory;
use SMG\SubscriptionApi\Model\ResourceModel\SubscriptionOrder as SubscriptionOrderResource;
use SMG\SubscriptionApi\Model\Subscription;
use SMG\SubscriptionApi\Model\SubscriptionAddonOrderFactory;
use SMG\SubscriptionApi\Model\SubscriptionAddonOrderItemFactory;
use SMG\SubscriptionApi\Model\SubscriptionFactory;
use SMG\SubscriptionApi\Model\SubscriptionOrder;
use SMG\SubscriptionApi\Model\SubscriptionOrderFactory;
use SMG\SubscriptionApi\Model\SubscriptionOrderItemFactory;

class CleanUpManagementHelper
{
    /** @var LoggerInterface */
    protected $_logger;

    /** @var ResponseHelper */
    protected $_responseHelper;

    /** @var OrderFactory */
    protected $_orderFactory;

    /** @var OrderResource */
    protected $_orderResource;

    /** @var DiscountHelper */
    protected $_discountHelper;

    /** @var InvoiceService */
    protected $_invoiceService;

    /** @var Transaction */
    protected $_transaction;

    /** @var InvoiceSender */
    protected $_invoiceSender;

    /** @var CustomerResource */
    protected $_customerResource;

    /** @var CustomerFactory */
    protected $_customerFactory;

    /** @var QuoteFactory */
    protected $_quoteFactory;

    /** @var QuoteResource */
    protected $_quoteResource;

    /** @var AddressRepositoryInterface */
    protected $_addressRepository;

    /** @var ProductFactory */
    protected $_productFactory;

    /** @var ProductResource */
    protected $_productResource;

    /**@var AdvancedFactory */
    protected $_advancedFactory;

    /** @var AdvancedResource */
    protected $_advancedResource;

    /** @var SubscriptionFactory */
    protected $_subscriptionFactory;

    /** @var SubscriptionResource */
    protected $_subscriptionResource;

    /** @var SubscriptionOrderFactory */
    protected $_subscriptionOrderFactory;

    /** @var SubscriptionOrderItemFactory */
    protected $_subscriptionOrderItemFactory;

    /** @var SubscriptionAddonOrderFactory */
    protected $_subscriptionAddonOrderFactory;

    /** @var SubscriptionAddonOrderItemFactory */
    protected $_subscriptionAddonOrderItemFactory;

    /** @var RecurlyHelper */
    protected $_recurlyHelper;

    /** @var SubscriptionOrderResource */
    protected $_subscriptionOrderResource;

    /** @var string */
    protected $_loggerPrefix;

    /** @var ItemResource */
    protected $_itemResource;

    /** @var \Magento\Quote\Model\Quote\AddressFactory */
    private $_addressFactory;

    /** @var OrderRepositoryInterface */
    private $_orderRepository;

    /** @var ShippingConditionCodeFactory */
    protected $_shippingConditionCodeFactory;

    /** @var ShippingConditionCodeResource */
    protected $_shippingConditionCodeResource;

    /**
     * @var SapOrderBatchCollectionFactory
     */
    protected $_sapOrderBatchCollectionFactory;

    /**
     * @var ShipOrderInterface
     */
    protected $_shipOrderInterface;

    /**
     * @var ShipmentItemCreationInterfaceFactory
     */
    protected $_shipmentItemCreationInterfaceFactory;

    /**
     * @var ShipmentTrackCreationInterfaceFactory
     */
    protected $_shipmentTrackCreationInterfaceFactory;

    /**
     * @var ShipmentHelper
     */
    protected $_shipmentHelper;

    /**
     * @var OrderItemCollectionFactory
     */
    protected $_orderItemCollectionFactory;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var string
     */
    private $imageUrl;

    /**
     * @var RepositoryAddress
     */
    private $repositoryAddress;

    /**
     * @var RegionCollectionFactory
     */
    private $regionCollection;

    /** @var SubscriptionCollectionFactory */
    protected $_subscriptionCollectionFactory;

    /**
     * OrderStatusHelper constructor.
     *
     * @param LoggerInterface $logger
     * @param ResponseHelper $responseHelper
     * @param OrderFactory $orderFactory
     * @param OrderResource $orderResource
     * @param DiscountHelper $discountHelper
     * @param InvoiceService $invoiceService
     * @param Transaction $transaction
     * @param InvoiceSender $invoiceSender
     * @param CustomerResource $customerResource
     * @param CustomerFactory $customerFactory
     * @param QuoteFactory $quoteFactory
     * @param AddressRepositoryInterface $addressRepository
     * @param ProductFactory $productFactory
     * @param ProductResource $productResource
     * @param AdvancedFactory $advancedFactory
     * @param AdvancedResource $advancedResource
     * @param AddressFactory $addressFactory
     * @param QuoteResource $quoteResource
     * @param OrderRepositoryInterface $orderRepository
     * @param SubscriptionFactory $subscriptionFactory
     * @param SubscriptionResource $subscriptionResource
     * @param SubscriptionOrderFactory $subscriptionOrderFactory
     * @param SubscriptionOrderItemFactory $subscriptionOrderItemFactory
     * @param SubscriptionAddonOrderFactory $subscriptionAddonOrderFactory
     * @param SubscriptionAddonOrderItemFactory $subscriptionAddonOrderItemFactory
     * @param RecurlyHelper $recurlyHelper
     * @param SubscriptionOrderResource $subscriptionOrderResource
     * @param ItemResource $itemResource
     * @param ShippingConditionCodeFactory $shippingConditionCodeFactory
     * @param ShippingConditionCodeResource $shippingConditionCodeResource
     * @param ShipOrderInterface $shipOrderInterface
     * @param ShipmentItemCreationInterfaceFactory $shipmentItemCreationInterfaceFactory
     * @param ShipmentTrackCreationInterfaceFactory $shipmentTrackCreationInterfaceFactory
     * @param ShipmentHelper $shipmentHelper
     * @param OrderItemCollectionFactory $orderItemCollectionFactory
     * @param Config $config
     * RepositoryAddress $repositoryAddress
     * @param RepositoryAddress $repositoryAddress
     * @param RegionCollectionFactory $regionCollection
     * @param SubscriptionCollectionFactory $subscriptionCollectionFactory
     */
    public function __construct(
        LoggerInterface $logger,
        ResponseHelper $responseHelper,
        OrderFactory $orderFactory,
        OrderResource $orderResource,
        DiscountHelper $discountHelper,
        InvoiceService $invoiceService,
        Transaction $transaction,
        InvoiceSender $invoiceSender,
        CustomerResource $customerResource,
        CustomerFactory $customerFactory,
        QuoteFactory $quoteFactory,
        AddressRepositoryInterface $addressRepository,
        ProductFactory $productFactory,
        ProductResource $productResource,
        AdvancedFactory $advancedFactory,
        AdvancedResource $advancedResource,
        AddressFactory $addressFactory,
        QuoteResource $quoteResource,
        OrderRepositoryInterface $orderRepository,
        SubscriptionFactory $subscriptionFactory,
        SubscriptionResource $subscriptionResource,
        SubscriptionOrderFactory $subscriptionOrderFactory,
        SubscriptionOrderItemFactory $subscriptionOrderItemFactory,
        SubscriptionAddonOrderFactory $subscriptionAddonOrderFactory,
        SubscriptionAddonOrderItemFactory $subscriptionAddonOrderItemFactory,
        RecurlyHelper $recurlyHelper,
        SubscriptionOrderResource $subscriptionOrderResource,
        ItemResource $itemResource,
        ShippingConditionCodeFactory $shippingConditionCodeFactory,
        ShippingConditionCodeResource $shippingConditionCodeResource,
        ShipOrderInterface $shipOrderInterface,
        ShipmentItemCreationInterfaceFactory $shipmentItemCreationInterfaceFactory,
        ShipmentTrackCreationInterfaceFactory $shipmentTrackCreationInterfaceFactory,
        ShipmentHelper $shipmentHelper,
        OrderItemCollectionFactory $orderItemCollectionFactory,
        Config $config,
        RepositoryAddress $repositoryAddress,
        RegionCollectionFactory $regionCollection,
        SubscriptionCollectionFactory $subscriptionCollectionFactory
    ) {
        $this->_logger = $logger;
        $host = gethostname();
        $ip = gethostbyname($host);
        $this->_loggerPrefix = 'SERVER: ' . $ip . ' SESSION: ' . session_id() . ' - ';
        $this->_responseHelper = $responseHelper;
        $this->_orderFactory = $orderFactory;
        $this->_orderResource = $orderResource;
        $this->_discountHelper = $discountHelper;
        $this->_invoiceService = $invoiceService;
        $this->_transaction = $transaction;
        $this->_invoiceSender = $invoiceSender;
        $this->_customerResource = $customerResource;
        $this->_customerFactory = $customerFactory;
        $this->_quoteFactory = $quoteFactory;
        $this->_addressRepository = $addressRepository;
        $this->_productFactory = $productFactory;
        $this->_productResource = $productResource;
        $this->_advancedFactory = $advancedFactory;
        $this->_advancedResource = $advancedResource;
        $this->_addressFactory = $addressFactory;
        $this->_quoteResource = $quoteResource;
        $this->_orderRepository = $orderRepository;
        $this->_subscriptionFactory = $subscriptionFactory;
        $this->_subscriptionResource = $subscriptionResource;
        $this->_subscriptionOrderFactory = $subscriptionOrderFactory;
        $this->_subscriptionOrderItemFactory = $subscriptionOrderItemFactory;
        $this->_subscriptionAddonOrderFactory = $subscriptionAddonOrderFactory;
        $this->_subscriptionAddonOrderItemFactory = $subscriptionAddonOrderItemFactory;
        $this->_recurlyHelper = $recurlyHelper;
        $this->_subscriptionOrderResource = $subscriptionOrderResource;
        $this->_itemResource = $itemResource;
        $this->_shippingConditionCodeFactory = $shippingConditionCodeFactory;
        $this->_shippingConditionCodeResource = $shippingConditionCodeResource;
        $this->_shipOrderInterface = $shipOrderInterface;
        $this->_shipmentItemCreationInterfaceFactory = $shipmentItemCreationInterfaceFactory;
        $this->_shipmentTrackCreationInterfaceFactory = $shipmentTrackCreationInterfaceFactory;
        $this->_shipmentHelper = $shipmentHelper;
        $this->_orderItemCollectionFactory = $orderItemCollectionFactory;
        $this->config = $config;
        $this->repositoryAddress = $repositoryAddress;
        $this->regionCollection = $regionCollection;
        $this->_subscriptionCollectionFactory = $subscriptionCollectionFactory;
    }

    /**
     * Determines are sets the appropriate statuses for duplicate subscriptions via provided gigya ids.
     *
     * @param $requestData: Contains a gigya_ids array with the gigya IDs desired to check for and clean up duplicates.
     * @throws AlreadyExistsException
     */
    public function cleanupDuplicateSubscriptions($requestData)
    {
        $this->_logger->info('Running cleanupDuplicateSubscriptions: ' . json_encode($requestData));

        // Ensure we are not given an empty request.
        if (empty($requestData)) {
            return $this->_responseHelper->createResponse(false, "Request cannot be empty.");
        }

        if (empty($requestData["gigya_ids"])) {
            return $this->_responseHelper->createResponse(false, "There must be a gigya id.");
        }
        if (!is_array($requestData["gigya_ids"])) {
            return $this->_responseHelper->createResponse(false, "gigya_ids field must be an array.");
        }

        if (count($requestData["gigya_ids"]) === 0) {
            return $this->_responseHelper->createResponse(false, "There must be at least one gigya id to process.");
        }

        foreach ($requestData["gigya_ids"] as $gigya_id) {
            $subscriptions = $this->_subscriptionCollectionFactory->create()
                                ->addFilter('gigya_id', $gigya_id)
                                ->getItems();

            // Go to next gigya id if no dupes were found.
            if (!$this->hasDuplicateSubscriptions($subscriptions)) {
                continue;
            }

            $this->cleanDuplicateSubscriptions($subscriptions);
        }
    }

    /**
     *
     * Determines whether or not a collection of subscriptions contains potential duplicates.
     *
     * @param $subscriptions
     * @return boolean
     */
    private function hasDuplicateSubscriptions($subscriptions)
    {
        // Grab subscriptions that have not been cancelled / renewal failure
        $not_cancelled_subscriptions = array_values(array_filter($subscriptions, function ($subscription) {
            /** @var Subscription $subscription */
            if ($subscription->getData('subscription_status') === Subscription::STATE_CANCELED ||
                $subscription->getData('subscription_status') === Subscription::STATE_RENEWAL_FAILED) {
                return false;
            }
            return true;
        }));

        // If only one subscription was found, then we know there are no dupes.
        if (count($not_cancelled_subscriptions) <= 1) {
            return false;
        }

        return true;
    }

    /**
     *
     * Determines which subscriptions provided are duplicate and cleans them up, setting the appropriate statuses.
     *
     * @param $subscriptions
     * @throws AlreadyExistsException
     */
    private function cleanDuplicateSubscriptions($subscriptions)
    {
        // There must be at least two subscriptions.
        if (count($subscriptions) <= 1) {
            return;
        }

        // Grab all renewed subscriptions
        $renewed_subscriptions = array_values(array_filter($subscriptions, function ($subscription) {
            /** @var Subscription $subscription */
            if ($subscription->getData('subscription_status') === Subscription::STATE_RENEWED) {
                return true;
            }
            return false;
        }));

        // Grab all active subscriptions.
        $active_subscriptions = array_values(array_filter($subscriptions, function ($subscription) {
            /** @var Subscription $subscription */
            if ($subscription->getData('subscription_status') === Subscription::STATE_ACTIVE) {
                return true;
            }
            return false;
        }));

        // Grab all cancelled subscriptions.
        $canceled_subscriptions = array_values(array_filter($subscriptions, function ($subscription) {
            /** @var Subscription $subscription */
            if ($subscription->getData('subscription_status') === Subscription::STATE_CANCELED) {
                return true;
            }
            return false;
        }));

        // Grab all pending subscriptions.
        $pending_subscriptions = array_values(array_filter($subscriptions, function ($subscription) {
            /** @var Subscription $subscription */
            if ($subscription->getData('subscription_status') === Subscription::STATE_PENDING) {
                return true;
            }
            return false;
        }));

        // Cancel the active subscription. Set the 2nd renewed subscription to active. Cancel all other renewed subscriptions except the 1st one.
        if (count($renewed_subscriptions) >= 2 &&
            count($active_subscriptions) === 1 &&
            count($pending_subscriptions) === 0) {
            $this->cancelSubscription($active_subscriptions[0]);

            $this->activateSubscription($renewed_subscriptions[1]);

            if (count($renewed_subscriptions) > 2) {
                foreach (array_slice($renewed_subscriptions, 2) as $renewed_subscription) {
                    $this->cancelSubscription($renewed_subscription);
                }
            }
            return;
        }

        // Cancel the 2nd renewed subscription if we have exactly 2 renewed and 1 canceled.
        if (count($renewed_subscriptions) === 2 &&
            count($canceled_subscriptions) === 1 &&
            count($active_subscriptions) === 0 &&
            count($pending_subscriptions) === 0) {
            $this->cancelSubscription($renewed_subscriptions[1]);
            return;
        }

        // TODO: Potentially cancel any pending if before the active subscription.
        if (count($renewed_subscriptions) === 0 &&
            count($active_subscriptions) === 1 &&
            count($pending_subscriptions) >= 1) {
            return;
        }

        // TODO: Potentially cancel all pending that are older than X days.
        if (count($renewed_subscriptions === 0 &&
            count($active_subscriptions) === 0 &&
            count($pending_subscriptions) >=1)) {
            return;
        }
    }

    /**
     *
     * Cancels a provided subscription via status updates to the subscription, subscription_order, and sales_order tables.
     *
     * @param Subscription $subscription
     * @throws AlreadyExistsException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function cancelSubscription($subscription)
    {
        // Grab the subscription orders.
        $subscription_orders = $subscription->getSubscriptionOrders();

        // Cancel the subscription orders
        /** @var SubscriptionOrder $subscription_order */
        foreach ($subscription_orders as $subscription_order) {
            $subscription_order->setData('subscription_order_status', SubscriptionOrder::STATE_CANCELED);
            $this->_subscriptionOrderResource->save($subscription_order);
        }

        // Cancel the current subscription by marking it as a renewal failure.
        $subscription->setData('subscription_status', Subscription::STATE_RENEWAL_FAILED);
        $this->_subscriptionResource->save($subscription);

        // Grab the sales orders
        $sales_orders = $subscription->getOrders();
        foreach ($sales_orders as $sales_order) {
            $sales_order->setStatus('canceled');
            $sales_order->setState('canceled');
            $this->_orderRepository->save($sales_order);
        }
    }

    /**
     *
     * Sets the subscription status to active.
     *
     * @param Subscription $subscription
     * @throws AlreadyExistsException
     */
    private function activateSubscription($subscription)
    {
        // Cancel the current subscription
        $subscription->setData('subscription_status', Subscription::STATE_ACTIVE);
        $this->_subscriptionResource->save($subscription);
    }
}
