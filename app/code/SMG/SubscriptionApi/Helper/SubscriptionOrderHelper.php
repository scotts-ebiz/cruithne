<?php

namespace SMG\SubscriptionApi\Helper;

use Magento\Customer\Model\Customer;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Api\CartRepositoryInterface;
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
     * @var CartRepositoryInterface
     */
    protected $_cartRepository;

    /**
     * @var CartManagementInterface
     */
    protected $_cartManagement;
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
     * SubscriptionOrderHelper constructor.
     * @param Context $context
     * @param CartRepositoryInterface $cartRepository
     * @param CartManagementInterface $cartManagement
     * @param Customer $customer
     * @param Order $order
     * @param StoreManagerInterface $storeManager
     * @param SubscriptionAddonOrder $subscriptionAddonOrder
     * @param SubscriptionAddonOrderCollectionFactory $subscriptionAddonOrderCollectionFactory
     * @param SubscriptionOrder $subscriptionOrder
     * @param SubscriptionOrderCollectionFactory $subscriptionOrderCollectionFactory
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function __construct(
        Context $context,
        CartRepositoryInterface $cartRepository,
        CartManagementInterface $cartManagement,
        Customer $customer,
        Order $order,
        StoreManagerInterface $storeManager,
        SubscriptionAddonOrder $subscriptionAddonOrder,
        SubscriptionAddonOrderCollectionFactory $subscriptionAddonOrderCollectionFactory,
        SubscriptionOrder $subscriptionOrder,
        SubscriptionOrderCollectionFactory $subscriptionOrderCollectionFactory
    ) {
        parent::__construct($context);

        $this->_cartManagement = $cartManagement;
        $this->_cartRepository = $cartRepository;
        $this->_customer = $customer;
        $this->_order = $order;
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
     * @param string $subscriptionId
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function processInvoiceWithSubscriptionId($subscriptionId)
    {
        // Get the subscription order.
        $subscriptionOrder = $this->getSubscriptionOrderBySubscriptionId($subscriptionId);

        // Check if we found a subscription order.
        if (! $subscriptionOrder) {
            $this->errorResponse(
                "Could not find a subscription order with subscription ID: {$subscriptionId}",
                404
            );
        }

        // Check if the subscription order is valid.
        if ($subscriptionOrder->getSubscriptionOrderStatus() != 'pending') {
            $this->errorResponse(
                "Subscription order with subscription ID {$subscriptionId} has already been completed or canceled.",
                400
            );
        }

        // Get the subscription.
        $subscription = $subscriptionOrder->getSubscription();

        // Get the customer.
        $customer = $this->_customer->load($subscription->getCustomerId());
        if (! $customer->getId()) {
            $this->errorResponse(
                "Customer {$customer->getId()} does not have an address.",
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

        if (! $subscriptionOrder->getId()) {
            // Lets see if it is an add-on order.
            $subscriptionOrderCollection = $this->_subscriptionAddonOrderCollectionFactory->create();
            $subscriptionOrder = $subscriptionOrderCollection
                ->getItemByColumnValue('subscription_id', $subscriptionId);
            $addOnOrder = true;
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
     */
    protected function processOrder(Customer $customer, $subscriptionOrder)
    {
        $cartId = $this->_cartManagement->createEmptyCartForCustomer($customer->getId());

        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $this->_cartRepository->get($cartId);
        $quote->setStore($this->_store);
        $quote->setCurrency();
        $quote->assignCustomer($customer->getDataModel());

        $customer->cleanAllAddresses();
        $customerShippingAddress = $customer->getDefaultShippingAddress();
        $customerBillingAddress = $customer->getDefaultBillingAddress();
        $quoteShipping = $quote->getShippingAddress();
        $quoteShipping->setData($customerShippingAddress->getData());
        $quoteShipping->setCustomerId($customer->getId());
        $quoteBilling = $quote->getBillingAddress();
        $quoteBilling->setData($customerBillingAddress->getData());
        $quoteBilling->setCustomerId($customer->getId());
        $quote->setShippingAddress($quoteShipping);
        $quote->setBillingAddress($quoteBilling);

        $shippingAddress = $quote->getShippingAddress();
        $shippingAddress->unsetData('cached_items_nominal');
        $shippingAddress->unsetData('cached_items_nonnominal');
        $shippingAddress->unsetData('cached_items_all');
        $shippingAddress
            ->setCollectShippingRates(true)
            ->collectShippingRates()
            ->setShippingMethod('freeshipping_freeshipping');

        foreach ($subscriptionOrder->getOrderItems() as $item) {
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

        $quote->setInventoryProcessed(true);
        $quote->setPaymentMethod('recurly');
        $quote->getPayment()->importData(['method' => 'recurly']);
        $quote->collectTotals();
        $quote->save();

        $quote = $this->_cartRepository->get($quote->getId());
        $orderId = $this->_cartManagement->placeOrder($quote->getId());
        $order = $this->_order->load($orderId);
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
}
