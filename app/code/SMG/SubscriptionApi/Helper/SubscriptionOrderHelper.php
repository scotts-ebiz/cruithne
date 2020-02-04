<?php

namespace SMG\SubscriptionApi\Helper;

use Magento\Directory\Model\ResourceModel\Region\Collection as RegionCollection;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Model\QuoteFactory;
use Magento\Quote\Model\QuoteRepository;
use Magento\Customer\Model\AddressFactory;
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\CustomerFactory;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Quote\Model\QuoteManagement;
use Magento\Sales\Model\Order;
use Magento\Store\Model\StoreManagerInterface;
use SMG\Sap\Model\ResourceModel\SapOrderBatch\CollectionFactory as SapOrderBatchCollectionFactory;
use SMG\SubscriptionApi\Exception\SubscriptionException;
use SMG\SubscriptionApi\Model\ResourceModel\SubscriptionAddonOrder\CollectionFactory as SubscriptionAddonOrderCollectionFactory;
use SMG\SubscriptionApi\Model\ResourceModel\SubscriptionOrder\Collection as SubscriptionOrderCollection;
use SMG\SubscriptionApi\Model\ResourceModel\SubscriptionOrder\CollectionFactory as SubscriptionOrderCollectionFactory;
use SMG\SubscriptionApi\Model\SubscriptionAddonOrder;
use SMG\SubscriptionApi\Model\SubscriptionAddonOrderItem;
use SMG\SubscriptionApi\Model\SubscriptionOrder;
use SMG\SubscriptionApi\Model\SubscriptionOrderItem;

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
     * @var Customer
     */
    protected $_customer;
    /**
     * @var int
     */
    private $_websiteId;
    /**
     * @var \Magento\Store\Api\Data\StoreInterface
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
     * @var QuoteManagement
     */
    protected $_quoteManagement;

    /**
     * @var CustomerFactory
     */
    protected $_customerFactory;

    /**
     * @var QuoteFactory
     */
    protected $_quoteFactory;

    /**
     * @var QuoteRepository
     */
    protected $_quoteRepository;

    /**
     * @var RegionCollection
     */
    protected $_regionCollection;

    /**
     * @var SapOrderBatchCollectionFactory
     */
    protected $_sapOrderBatchCollectionFactory;

    /**
     * SubscriptionOrderHelper constructor.
     * @param Context $context
     * @param AddressFactory $addressFactory
     * @param Customer $customer
     * @param CustomerFactory $customerFactory
     * @param Order $order
     * @param QuoteFactory $quoteFactory
     * @param QuoteManagement $quoteManagement
     * @param QuoteRepository $quoteRepository
     * @param StoreManagerInterface $storeManager
     * @param SubscriptionAddonOrder $subscriptionAddonOrder
     * @param SubscriptionAddonOrderCollectionFactory $subscriptionAddonOrderCollectionFactory
     * @param SubscriptionOrder $subscriptionOrder
     * @param SubscriptionOrderCollectionFactory $subscriptionOrderCollectionFactory
     * @param RegionCollection $regionCollection
     * @param SapOrderBatchCollectionFactory $sapOrderBatchCollectionFactory
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function __construct(
        Context $context,
        AddressFactory $addressFactory,
        Customer $customer,
        CustomerFactory $customerFactory,
        Order $order,
        QuoteFactory $quoteFactory,
        QuoteManagement $quoteManagement,
        QuoteRepository $quoteRepository,
        StoreManagerInterface $storeManager,
        SubscriptionAddonOrder $subscriptionAddonOrder,
        SubscriptionAddonOrderCollectionFactory $subscriptionAddonOrderCollectionFactory,
        SubscriptionOrder $subscriptionOrder,
        SubscriptionOrderCollectionFactory $subscriptionOrderCollectionFactory,
        RegionCollection $regionCollection,
        SapOrderBatchCollectionFactory $sapOrderBatchCollectionFactory
    ) {
        parent::__construct($context);

        $this->_addressFactory = $addressFactory;
        $this->_customer = $customer;
        $this->_customerFactory = $customerFactory;
        $this->_order = $order;
        $this->_quoteFactory = $quoteFactory;
        $this->_quoteManagement = $quoteManagement;
        $this->_quoteRepository = $quoteRepository;
        $this->_storeManager = $storeManager;
        $this->_subscriptionAddonOrder = $subscriptionAddonOrder;
        $this->_subscriptionAddonOrderCollectionFactory = $subscriptionAddonOrderCollectionFactory;
        $this->_subscriptionOrder = $subscriptionOrder;
        $this->_subscriptionOrderCollectionFactory = $subscriptionOrderCollectionFactory;
        $this->_regionCollection = $regionCollection;
        $this->_sapOrderBatchCollectionFactory = $sapOrderBatchCollectionFactory;

        $this->_store = $storeManager->getStore();
        $this->_websiteId = $this->_store->getWebsiteId();
    }

    /**
     * Process orders with the given subscription ID.
     *
     * @param SubscriptionOrder|SubscriptionAddonOrder|string $subscriptionId
     * @throws LocalizedException
     * @throws SubscriptionException
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
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
                "Could not find a subscription order with subscription ID: {$subscriptionOrder->getSubscriptionId()}",
                404
            );
        }

        // Check if the subscription order is valid.
        if ($subscriptionOrder->getSubscriptionOrderStatus() != 'pending') {
            $this->errorResponse(
                "Subscription order with subscription ID {$subscriptionOrder->getSubscriptionId()} has already been completed or canceled.",
                400
            );
        }

        // Get the subscription.
        $subscription = $subscriptionOrder->getSubscription();

        // Get the customer.
        $customer = $this->_customerFactory->create();
        $customer->load($subscription->getData('customer_id'));
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

        if (! $this->processOrder($customer, $subscriptionOrder)) {
            $this->errorResponse(
                "Could not complete subscription order with subscription ID: {$subscriptionOrder->getId()}",
                400
            );
        }
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
            'postcode' => $address->getPostcode(),
            'telephone' => $address->getTelephone(),
            'save_in_address_book' => 1,
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
            ->getItemByColumnValue('subscription_id', $subscriptionId);

        if (! $subscriptionOrder || ! $subscriptionOrder->getId()) {
            // Lets see if it is an add-on order.
            $subscriptionOrderCollection = $this->_subscriptionAddonOrderCollectionFactory->create();
            $subscriptionOrder = $subscriptionOrderCollection
                ->getItemByColumnValue('subscription_id', $subscriptionId);
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
     * @return bool
     * @throws LocalizedException
     * @throws SubscriptionException
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function processOrder(Customer $customer, $subscriptionOrder)
    {
        $quoteID = $this->_quoteManagement->createEmptyCartForCustomer($customer->getId());
        $quote = $this->_quoteRepository->get($quoteID);
        $quote->setStore($this->_store);
        $quote->setCurrency();
        $quote->assignCustomer($customer->getDataModel());

        foreach ($subscriptionOrder->getOrderItems() as $item) {
            // Check if the item has the selected field and if it is set.
            /**
             * @var SubscriptionOrderItem|SubscriptionAddonOrderItem $item
             */
            if ($item->hasData('selected') && ! $item->getSelected()) {
                // This is an add-on product and is not selected, so continue.
                continue;
            }

            $isAddon = $item->hasData('selected') && $item->getSelected();

            // Add the annual discount for annual subscription items that are
            // not add-ons.
            if (! $isAddon && $subscriptionOrder->getSubscriptionType() == 'annual') {
                $quote->setCouponCode('annual_discount_order');
            }

            // Add product to the cart
            try {
                $product = $item->getProduct();
            } catch (\Exception $e) {
                $this->errorResponse(
                    'Could not find product: ' . $item->getCatalogProductSku(),
                    404
                );
            }

            $quote->addProduct($product, (int) $item->getQty());
        }

        // Set addressees.
        $customerBilling = $this->_addressFactory->create()->load($customer->getDefaultBilling());
        $customerShipping = $this->_addressFactory->create()->load($customer->getDefaultShipping());

        $quote->getBillingAddress()->addData($this->formatAddress($customerBilling));
        $quote->getShippingAddress()->addData($this->formatAddress($customerShipping));

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
        $quote->save();

        // Set sales order payment.
        $quote->getPayment()->importData(['method' => 'recurly']);

        // Collect the totals and save the quote.
        $quote->collectTotals()->save();

        // Create an order from the quote.
        $order = $this->_quoteManagement->submit($quote);
        $order->setEmailSent(0);

        // Set customer Gigya ID
        $order->setGigyaId($customer->getGigyaUid());

        // Set ship date for the subscription/order
        $order->setShipStartDate($subscriptionOrder->getShipStartDate());
        $order->setShipEndDate($subscriptionOrder->getShipEndDate());
        $order->setSubscriptionAddon($subscriptionOrder->type() == 'addon' ? 1 : 0);
        $order->setSubscriptionType($subscriptionOrder->getSubscriptionType());

        // Save order
        $order->save();

        // Add the order ID to teh subscription order.
        $subscriptionOrder->setSalesOrderId($order->getEntityId());
        $subscriptionOrder->save();

        if ($subscriptionOrder->isCurrentlyShippable()) {
            // Complete the order
            $subscriptionOrder->setData('subscription_order_status', 'complete')->save();

            // Create the order invoice.
            $subscriptionOrder->createInvoice();
        } else {
            $sapOrderBatch = $this->_sapOrderBatchCollectionFactory
                ->create()
                ->addFilter('order_id', $order->getId())
                ->getFirstItem();

            if (is_null($sapOrderBatch)) {
                $error = 'Create Orders: Failed to find Sap Batch Order for order ' . $order->getId();
                $this->_logger->error($error);
                throw new LocalizedException(__($error));
            }

            // Prevent SAP from processing
            $sapOrderBatch
                ->setData('is_order', 0)
                ->setData('order_process_date', null)
                ->save();
        }

        return true;
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
}
