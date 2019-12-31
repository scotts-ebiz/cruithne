<?php

namespace SMG\SubscriptionApi\Helper;

use Magento\Customer\Model\Customer;
use Magento\Quote\Model\QuoteFactory;
use Magento\Quote\Model\QuoteManagement;
use Magento\Customer\Model\AddressFactory;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Sales\Model\Order;
use Magento\Store\Model\StoreManagerInterface;
use SMG\SubscriptionApi\Exception\SubscriptionException;
use SMG\SubscriptionApi\Model\ResourceModel\SubscriptionAddonOrder\CollectionFactory as SubscriptionAddonOrderCollectionFactory;
use SMG\SubscriptionApi\Model\ResourceModel\SubscriptionOrder\Collection as SubscriptionOrderCollection;
use SMG\SubscriptionApi\Model\ResourceModel\SubscriptionOrder\CollectionFactory as SubscriptionOrderCollectionFactory;
use SMG\SubscriptionApi\Model\SubscriptionAddonOrder;
use SMG\SubscriptionApi\Model\SubscriptionOrder;

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
     * @var QuoteFactory
     */
    protected $_quoteFactory;

    /**
     * SubscriptionOrderHelper constructor.
     * @param Context $context
     * @param Customer $customer
     * @param Order $order
     * @param QuoteFactory $quoteFactory
     * @param QuoteManagement $quoteManagement
     * @param StoreManagerInterface $storeManager
     * @param SubscriptionAddonOrder $subscriptionAddonOrder
     * @param SubscriptionAddonOrderCollectionFactory $subscriptionAddonOrderCollectionFactory
     * @param SubscriptionOrder $subscriptionOrder
     * @param SubscriptionOrderCollectionFactory $subscriptionOrderCollectionFactory
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function __construct(
        Context $context,
        AddressFactory $addressFactory,
        Customer $customer,
        Order $order,
        QuoteFactory $quoteFactory,
        QuoteManagement $quoteManagement,
        StoreManagerInterface $storeManager,
        SubscriptionAddonOrder $subscriptionAddonOrder,
        SubscriptionAddonOrderCollectionFactory $subscriptionAddonOrderCollectionFactory,
        SubscriptionOrder $subscriptionOrder,
        SubscriptionOrderCollectionFactory $subscriptionOrderCollectionFactory
    ) {
        parent::__construct($context);

        $this->_addressFactory = $addressFactory;
        $this->_customer = $customer;
        $this->_order = $order;
        $this->_quoteFactory = $quoteFactory;
        $this->_quoteManagement = $quoteManagement;
        $this->_storeManager = $storeManager;
        $this->_subscriptionAddonOrder = $subscriptionAddonOrder;
        $this->_subscriptionAddonOrderCollectionFactory = $subscriptionAddonOrderCollectionFactory;
        $this->_subscriptionOrder = $subscriptionOrder;
        $this->_subscriptionOrderCollectionFactory = $subscriptionOrderCollectionFactory;

        $this->_store = $storeManager->getStore();
        $this->_websiteId = $this->_store->getWebsiteId();
    }

    /**
     * Process orders with the given subscription ID.
     *
     * @param SubscriptionOrder|SubscriptionAddonOrder|string $subscriptionId
     * @throws SubscriptionException
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\LocalizedException
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
        $customer = $this->_customer->load($subscription->getCustomerId());
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
     * Get the subscription order by subscription ID.
     *
     * @param string $subscriptionId
     *
     * @return \Magento\Framework\DataObject|SubscriptionOrder|SubscriptionAddonOrder
     */
    protected function getSubscriptionOrderBySubscriptionId($subscriptionId)
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
     * @return \Magento\Quote\Model\Quote
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws SubscriptionException
     */
    protected function processOrder(Customer $customer, $subscriptionOrder)
    {
        // Create a new quote.
        $quote = $this->_quoteFactory->create();
        $quote->setStore($this->_store);
        $quote->setCurrency();
        $quote->assignCustomer($customer->getDataModel());

        foreach ($subscriptionOrder->getOrderItems() as $item) {
            // Check if the item has the selected field and if it is set.
            if ($item->hasData('selected') && ! $item->getSelected()) {
                // This is an add-on product and is not selected, so continue.
                continue;
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

        // Set master subscription id based on the Recurly subscription plan code
        $order->setMasterSubscriptionId($subscriptionOrder->getMasterSubscriptionId());

        // Set subscription ID
        $order->setSubscriptionId($subscriptionOrder->getSubscriptionId());

        // Set ship date for the subscription/order
        $order->setShipStartDate($subscriptionOrder->getShipStartDate());
        $order->setShipEndDate($subscriptionOrder->getShipEndDate());
        $order->setSubscriptionAddon($subscriptionOrder->type() == 'addon' ? 1 : 0);
        $order->setSubscriptionType($subscriptionOrder->getSubscriptionType());

        // Save order
        $order->save();

        // Add the order ID to teh subscription order.
        $subscriptionOrder->setSalesOrderId($order->getEntityId());
        $subscriptionOrder->setSubscriptionOrderStatus('complete');
        $subscriptionOrder->save();

        // Create the order invoice.
        $subscriptionOrder->createInvoice();

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

    protected function formatAddress($address)
    {
        return [
            'firstname' => $address->getFirstname(),
            'lastname' => $address->getLastname(),
            'street' => $address->getStreet(),
            'city' => $address->getCity(),
            'county_id' => $address->getCountryId(),
            'region' => $address->getRegion(),
            'postcode' => $address->getPostcode(),
            'telephone' => $address->getTelephone(),
            'save_in_address_book' => 1,
        ];
    }
}
