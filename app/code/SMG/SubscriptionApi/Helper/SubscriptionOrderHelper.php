<?php

namespace SMG\SubscriptionApi\Helper;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\AddressFactory;
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\CustomerFactory;
use Magento\Directory\Model\ResourceModel\Region\Collection as RegionCollection;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\QuoteFactory;
use Magento\Sales\Model\Order;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use SMG\Sap\Model\ResourceModel\SapOrderBatch\CollectionFactory as SapOrderBatchCollectionFactory;
use Magento\Customer\Model\ResourceModel\Customer as CustomerResource;
use SMG\SubscriptionApi\Model\ResourceModel\SubscriptionOrder as SubscriptionOrderResource;
use SMG\SubscriptionApi\Model\ResourceModel\SubscriptionAddonOrder as SubscriptionAddonOrderResource;
use Magento\Sales\Model\ResourceModel\Order as OrderResource;
use SMG\SubscriptionApi\Exception\SubscriptionException;
use SMG\SubscriptionApi\Model\ResourceModel\SubscriptionAddonOrder\CollectionFactory as SubscriptionAddonOrderCollectionFactory;
use SMG\SubscriptionApi\Model\ResourceModel\SubscriptionOrder\Collection as SubscriptionOrderCollection;
use SMG\SubscriptionApi\Model\ResourceModel\SubscriptionOrder\CollectionFactory as SubscriptionOrderCollectionFactory;
use SMG\SubscriptionApi\Model\SubscriptionAddonOrder;
use SMG\SubscriptionApi\Model\SubscriptionAddonOrderItem;
use SMG\SubscriptionApi\Model\SubscriptionOrder;
use SMG\SubscriptionApi\Model\SubscriptionOrderItem;
use Magento\Quote\Model\ResourceModel\Quote as QuoteResource;

/**
 * Class SubscriptionOrderHelper
 *
 * @package SMG\SubscriptionApi\Helper
 */
class SubscriptionOrderHelper extends AbstractHelper
{
    /**
     * @var AddressFactory
     */
    protected $_addressFactory;

    /**
     * @var SubscriptionOrder
     */
    protected $_subscriptionOrder;

    /**
     * @var SubscriptionOrderCollectionFactory
     */
    protected $_subscriptionOrderCollectionFactory;

    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var SessionManagerInterface
     */
    protected $_session;

    /**
     * @var Customer
     */
    protected $_customer;

    /**
     * @var CustomerRepositoryInterface
     */
    protected $_customerRepository;

    /**
     * @var int
     */
    private $_websiteId;
    /**
     * @var StoreInterface
     */
    private $_store;

    /**
     * @var Order
     */
    protected $_order;

    /**
     * @var SubscriptionAddonOrder
     */
    protected $_subscriptionAddonOrder;

    /**
     * @var SubscriptionAddonOrderCollectionFactory
     */
    protected $_subscriptionAddonOrderCollectionFactory;

    /**
     * @var CartManagementInterface
     */
    protected $_cartManagement;

    /**
     * @var CartRepositoryInterface
     */
    protected $_cartRepository;

    /**
     * @var CustomerFactory
     */
    protected $_customerFactory;

    /**
     * @var QuoteFactory
     */
    protected $_quoteFactory;

    /**
     * @var RegionCollection
     */
    protected $_regionCollection;

    /**
     * @var SapOrderBatchCollectionFactory
     */
    protected $_sapOrderBatchCollectionFactory;

    /**
     * @var CustomerResource
     */
    protected $_customerResource;

    /**
     * @var OrderResource
     */
    protected $_orderResource;

    /**
     * @var SubscriptionAddonOrderResource
     */
    protected $_subscriptionAddonOrderResource;

    /**
     * @var SubscriptionOrderResource
     */
    protected $_subscriptionOrderResource;

    /**
     * @var QuoteResource
     */
    protected $_quoteResource;

    /**
     * SubscriptionOrderHelper constructor.
     * @param Context $context
     * @param AddressFactory $addressFactory
     * @param Customer $customer
     * @param CustomerFactory $customerFactory
     * @param CustomerRepositoryInterface $customerRepository
     * @param CustomerResource $customerResource
     * @param Order $order
     * @param OrderResource $orderResource
     * @param QuoteFactory $quoteFactory
     * @param QuoteResource $quoteResource
     * @param CartManagementInterface $cartManagement
     * @param CartRepositoryInterface $cartRepository
     * @param StoreManagerInterface $storeManager
     * @param SessionManagerInterface $session
     * @param SubscriptionAddonOrder $subscriptionAddonOrder
     * @param SubscriptionAddonOrderCollectionFactory $subscriptionAddonOrderCollectionFactory
     * @param SubscriptionAddonOrderResource $subscriptionAddonOrderResource
     * @param SubscriptionOrder $subscriptionOrder
     * @param SubscriptionOrderCollectionFactory $subscriptionOrderCollectionFactory
     * @param SubscriptionOrderResource $subscriptionOrderResource
     * @param RegionCollection $regionCollection
     * @param SapOrderBatchCollectionFactory $sapOrderBatchCollectionFactory
     * @throws NoSuchEntityException
     */
    public function __construct(
        Context $context,
        AddressFactory $addressFactory,
        Customer $customer,
        CustomerFactory $customerFactory,
        CustomerRepositoryInterface $customerRepository,
        CustomerResource $customerResource,
        Order $order,
        OrderResource $orderResource,
        QuoteFactory $quoteFactory,
        QuoteResource $quoteResource,
        CartManagementInterface $cartManagement,
        CartRepositoryInterface $cartRepository,
        StoreManagerInterface $storeManager,
        SessionManagerInterface $session,
        SubscriptionAddonOrder $subscriptionAddonOrder,
        SubscriptionAddonOrderCollectionFactory $subscriptionAddonOrderCollectionFactory,
        SubscriptionAddonOrderResource $subscriptionAddonOrderResource,
        SubscriptionOrder $subscriptionOrder,
        SubscriptionOrderCollectionFactory $subscriptionOrderCollectionFactory,
        SubscriptionOrderResource $subscriptionOrderResource,
        RegionCollection $regionCollection,
        SapOrderBatchCollectionFactory $sapOrderBatchCollectionFactory
    ) {
        parent::__construct($context);

        $this->_addressFactory = $addressFactory;
        $this->_customer = $customer;
        $this->_customerFactory = $customerFactory;
        $this->_customerRepository = $customerRepository;
        $this->_customerResource = $customerResource;
        $this->_order = $order;
        $this->_orderResource = $orderResource;
        $this->_quoteFactory = $quoteFactory;
        $this->_quoteResource = $quoteResource;
        $this->_cartManagement = $cartManagement;
        $this->_cartRepository = $cartRepository;
        $this->_storeManager = $storeManager;
        $this->_session = $session;
        $this->_subscriptionAddonOrder = $subscriptionAddonOrder;
        $this->_subscriptionAddonOrderCollectionFactory = $subscriptionAddonOrderCollectionFactory;
        $this->_subscriptionAddonOrderResource = $subscriptionAddonOrderResource;
        $this->_subscriptionOrder = $subscriptionOrder;
        $this->_subscriptionOrderCollectionFactory = $subscriptionOrderCollectionFactory;
        $this->_subscriptionOrderResource = $subscriptionOrderResource;
        $this->_regionCollection = $regionCollection;
        $this->_sapOrderBatchCollectionFactory = $sapOrderBatchCollectionFactory;

        $this->_store = $storeManager->getStore();
        $this->_websiteId = $this->_store->getWebsiteId();
    }

    /**
     * Process orders with the given subscription ID.
     *
     * @param SubscriptionOrder|SubscriptionAddonOrder|string $subscriptionId
     * @return Order
     * @throws LocalizedException
     * @throws SubscriptionException
     * @throws NoSuchEntityException
     */
    public function processInvoiceWithSubscriptionId($subscriptionId)
    {
        // Get the subscription order.
        if (is_string($subscriptionId)) {
            $subscriptionOrder = $this->getSubscriptionOrderBySubscriptionId($subscriptionId);
        } else {
            $subscriptionOrder = $subscriptionId;
        }

        // Check if we found a subscription order.
        if (! $subscriptionOrder) {
            $this->errorResponse(
                "Could not find a subscription order with subscription ID: {$subscriptionOrder->getData('subscription_id')}",
                404
            );
        }

        // Check if the subscription order is valid.
        if ($subscriptionOrder->getData('subscription_order_status') != 'pending' || $subscriptionOrder->getData('sales_order_id') > 0) {
            $this->errorResponse(
                "Subscription order with subscription ID {$subscriptionOrder->getData('subscription_id')} has already been completed or canceled.",
                400
            );
        }

        // Get the subscription.
        $subscription = $subscriptionOrder->getSubscription();

        // Get the customer.
        $customer = $this->_customerFactory->create();
        $this->_customerResource->load($customer, $subscription->getData('customer_id'));

        if (! $customer->getId()) {
            $this->errorResponse(
                "Customer {$customer->getId()} not found.",
                404
            );
        }

        $customer->setWebsiteId($this->_websiteId);
        $customerAddress = $customer->getAddressesCollection()->getFirstItem();

        if (! $customerAddress) {
            $this->errorResponse(
                "Customer {$customer->getId()} does not have an address.",
                404
            );
        }

        return $this->processOrder($customer, $subscriptionOrder);
    }

    /**
     * Format the provided address to use only the required fields.
     *
     * @param $address
     * @return array
     */
    public function formatAddress($address)
    {
        return [
            'firstname' => $address->getFirstname(),
            'lastname' => $address->getLastname(),
            'street' => $address->getStreet(),
            'city' => $address->getCity(),
            'country_id' => $address->getCountryId(),
            'region' => $address->getRegion(),
            'region_id' => $address->getRegionId(),
            'postcode' => substr($address->getPostcode(), 0, 5),
            'telephone' => $address->getTelephone(),
            'save_in_address_book' => 0,
        ];
    }

    /**
     * Get the subscription order by subscription ID.
     *
     * @param string $subscriptionId
     *
     * @return \Magento\Framework\DataObject|SubscriptionOrder|SubscriptionAddonOrder
     */
    public function getSubscriptionOrderBySubscriptionId($subscriptionId)
    {
        /** @var SubscriptionOrderCollection $subscriptionOrderCollection */
        $subscriptionOrderCollection = $this->_subscriptionOrderCollectionFactory->create();
        $addOnOrder = false;

        $subscriptionOrder = $subscriptionOrderCollection
            ->addFieldToFilter('subscription_id', $subscriptionId)
            ->getFirstItem();

        if (! $subscriptionOrder || ! $subscriptionOrder->getId()) {
            // Lets see if it is an add-on order.
            $subscriptionOrderCollection = $this->_subscriptionAddonOrderCollectionFactory->create();
            $subscriptionOrder = $subscriptionOrderCollection
                ->addFieldToFilter('subscription_id', $subscriptionId)
                ->getFirstItem();

            $addOnOrder = true;
        }

        if (! $subscriptionOrder || ! $subscriptionOrder->getId()) {
            return false;
        }

        if ($addOnOrder) {
            $subscriptionOrder = $this->_subscriptionAddonOrder->load($subscriptionOrder->getId());
        } else {
            $subscriptionOrder = $this->_subscriptionOrder->load($subscriptionOrder->getId());
        }

        return $subscriptionOrder;
    }

    /**
     * Get a new quote for the given customer.
     *
     * @param Customer $customer
     * @param SubscriptionOrder|SubscriptionAddonOrder $subscriptionOrder
     * @return Order
     * @throws LocalizedException
     * @throws SubscriptionException
     * @throws NoSuchEntityException
     */
    protected function processOrder(Customer $customer, $subscriptionOrder)
    {
        $this->_logger->info('Processing order...');
        $quote = $this->_quoteFactory->create();
        $quote->setStoreId($this->_store->getId());
        $quote->setCurrency();
        $customerData = $this->_customerRepository->getById($customer->getId());
        $this->_logger->info('Assign customer to quote...');
        $quote->assignCustomer($customerData);

        $this->_logger->info('Saving quote...');
        $this->_quoteResource->save($quote);

        $this->_logger->info('Adding items to order...');
        foreach ($subscriptionOrder->getOrderItems() as $item) {
            // Check if the item has the selected field and if it is set.
            /**
             * @var SubscriptionOrderItem|SubscriptionAddonOrderItem $item
             */
            if ($item->hasData('selected') && ! $item->getData('selected')) {
                // This is an add-on product and is not selected, so continue.
                continue;
            }

            $isAddon = $item->hasData('selected') && $item->getData('selected');

            // Add the annual discount for annual subscription items that are
            // not add-ons.
            if (! $isAddon && $subscriptionOrder->getSubscriptionType() == 'annual') {
                $this->_logger->debug('Setting annual discount coupon...');
                $quote->setCouponCode('annual_discount_order');
            }

            // Add product to the cart
            try {
                $product = $item->getProduct();

                $this->_logger->info('Adding product to quote...');
                $quote->addProduct($product, (int) $item->getQty());
            } catch (\Exception $e) {
                $this->errorResponse(
                    'Could not find product: ' . $item->getCatalogProductSku(),
                    404
                );
            }
        }

        // Set addresses.
        $this->_logger->info('Setting billing address...');
        $quote->getBillingAddress()->addData($this->_session->getCheckoutBilling())->save();
        $this->_logger->info('Setting shipping address...');
        $quote->getShippingAddress()->addData($this->_session->getCheckoutShipping())->save();

        // Collect rates and set shipping and payment method.
        $shippingAddress = $quote->getShippingAddress();
        $shippingAddress->unsetData('cached_items_nominal');
        $shippingAddress->unsetData('cached_items_nonnominal');
        $shippingAddress->unsetData('cached_items_all');
        $shippingAddress
            ->setCollectShippingRates(true)
            ->collectShippingRates()
            ->setShippingMethod('freeshipping_freeshipping');
        $quote->setPaymentMethod('recurly');
        $quote->setInventoryProcessed(false);
        $this->_logger->info('Saving quote after adding address information...');
        $this->_quoteResource->save($quote);

        // Set sales order payment.
        $quote->getPayment()->importData(['method' => 'recurly']);

        // Collect the totals and save the quote.
        $quote->collectTotals();
        $this->_logger->info('Saving quote after collecting totals...');
        $this->_quoteResource->save($quote);

        // Create an order from the quote.
        $this->_logger->info('Submitting quote...');
        /** @var Order $order */
        $order = $this->_cartManagement->submit($quote);
        $order->setEmailSent(0);

        // Set customer Gigya ID
        $order->setData('gigya_id', $customer->getData('gigya_uid'));

        // Set ship date for the subscription/order
        $order->addData([
            'ship_start_date' => $subscriptionOrder->getData('ship_start_date'),
            'ship_end_date' => $subscriptionOrder->getData('ship_end_date'),
            'subscription_addon' => $subscriptionOrder->type() == 'addon' ? 1 : 0,
            'subscription_type' => $subscriptionOrder->getSubscriptionType(),
        ]);

        // Save order
        $this->_logger->info('Saving order...');
        $this->_orderResource->save($order);

        // Add the order ID to teh subscription order.
        $subscriptionOrder->setData('sales_order_id', $order->getId());
        $this->saveSubscriptionOrder($subscriptionOrder);

        if ($subscriptionOrder->getSubscriptionType() == 'annual' || $subscriptionOrder->isCurrentlyShippable()) {
            // Complete the order
            $subscriptionOrder->setData('subscription_order_status', 'complete');
            $this->saveSubscriptionOrder($subscriptionOrder);

            // Create the order invoice.
            $this->_logger->info('Creating the invoice...');
            $subscriptionOrder->createInvoice();
        }

        return $order;
    }

    /**
     * Log an error.
     *
     * @param $error
     * @param int $status
     * @throws SubscriptionException
     */
    protected function errorResponse($error, $status = 400)
    {
        $this->_logger->error($error);
        http_response_code($status);

        throw new SubscriptionException($error);
    }

    /**
     * @param SubscriptionOrder | SubscriptionAddonOrder $subscriptionOrder
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    public function saveSubscriptionOrder($subscriptionOrder): void
    {
        if ($subscriptionOrder instanceof SubscriptionOrder) {
            $this->_subscriptionOrderResource->save($subscriptionOrder);
        } else {
            $this->_subscriptionAddonOrderResource->save($subscriptionOrder);
        }
    }
}
