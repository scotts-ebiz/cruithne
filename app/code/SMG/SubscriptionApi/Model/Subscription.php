<?php

namespace SMG\SubscriptionApi\Model;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Model\ProductRepository;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Data\Form\FormKey;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\QuoteManagement;
use Magento\Quote\Model\QuoteRepository;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Magento\Store\Model\StoreManager;
use Psr\Log\LoggerInterface;
use SMG\SubscriptionApi\Helper\RecurlyHelper;
use SMG\SubscriptionApi\Helper\SubscriptionHelper;
use SMG\SubscriptionApi\Model\ResourceModel\SubscriptionAddonOrder\Collection as SubscriptionAddonOrderCollection;
use SMG\SubscriptionApi\Model\ResourceModel\SubscriptionAddonOrder\CollectionFactory as SubscriptionAddonOrderCollectionFactory;
use SMG\SubscriptionApi\Model\ResourceModel\SubscriptionOrder\Collection as SubscriptionOrderCollection;
use SMG\SubscriptionApi\Model\ResourceModel\SubscriptionOrder\CollectionFactory as SubscriptionOrderCollectionFactory;

/**
 * Class Subscription
 * @package SMG\SubscriptionApi\Model
 */
class Subscription extends AbstractModel
{
    /** @var SubscriptionOrderCollectionFactory */
    protected $_subscriptionOrderCollectionFactory;

    /** @var SubscriptionAddonOrderCollectionFactory */
    protected $_subscriptionAddonOrderCollectionFactory;

    /**  @var SubscriptionHelper */
    protected $_subscriptionHelper;

    /** @var SubscriptionOrderCollection */
    protected $_subscriptionOrders;

    /** @var SubscriptionAddonOrderCollection */
    protected $_subscriptionAddonOrders;

    /** @var FormKey */
    protected $_formKey;

    /** @var CheckoutSession */
    protected $_checkoutSession;

    /** @var StoreManager */
    protected $_storeManager;

    /** @var Quote */
    protected $_quote;

    /** @var Product */
    protected $_product;

    /**  @var ProductFactory */
    protected $_productFactory;

    /** @var ProductRepository */
    protected $_productRepository;

    /** @var OrderCollectionFactory */
    protected $_orderCollectionFactory;

    /**
     * @var LoggerInterface
     */
    protected $_logger;

    /**
     * @var RecurlyHelper
     */
    protected $_recurlyHelper;

    /**
     * @var QuoteManagement
     */
    protected $_quoteManagement;

    /**
     * @var QuoteRepository
     */
    protected $_quoteRepository;
    /**
     * @var CustomerSession
     */
    protected $_customerSession;

    /**
     * @var CustomerFactory
     */
    protected $_customerFactory;

    /**
     * Constructor.
     */
    protected function _construct()
    {
        $this->_init(
            \SMG\SubscriptionApi\Model\ResourceModel\Subscription::class
        );
    }

    /**
     * Subscription constructor.
     * @param Context $context
     * @param Registry $registry
     * @param LoggerInterface $logger
     * @param SubscriptionOrderCollectionFactory $subscriptionOrderCollectionFactory
     * @param SubscriptionAddonOrderCollectionFactory $subscriptionAddonOrderCollectionFactory
     * @param SubscriptionHelper $subscriptionHelper
     * @param FormKey $formKey
     * @param CheckoutSession $checkoutSession
     * @param CustomerSession $customerSession
     * @param StoreManager $storeManager
     * @param Product $product
     * @param ProductFactory $productFactory
     * @param ProductRepository $productRepository
     * @param OrderCollectionFactory $orderCollectionFactory
     * @param RecurlyHelper $recurlyHelper
     * @param QuoteManagement $quoteManagement
     * @param QuoteRepository $quoteRepository
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        LoggerInterface $logger,
        SubscriptionOrderCollectionFactory $subscriptionOrderCollectionFactory,
        SubscriptionAddonOrderCollectionFactory $subscriptionAddonOrderCollectionFactory,
        SubscriptionHelper $subscriptionHelper,
        FormKey $formKey,
        CheckoutSession $checkoutSession,
        CustomerSession $customerSession,
        CustomerFactory $customerFactory,
        StoreManager $storeManager,
        Product $product,
        ProductFactory $productFactory,
        ProductRepository $productRepository,
        OrderCollectionFactory $orderCollectionFactory,
        RecurlyHelper $recurlyHelper,
        QuoteManagement $quoteManagement,
        QuoteRepository $quoteRepository,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);

        $this->_logger = $logger;
        $this->_subscriptionOrderCollectionFactory = $subscriptionOrderCollectionFactory;
        $this->_subscriptionAddonOrderCollectionFactory = $subscriptionAddonOrderCollectionFactory;
        $this->_storeManager = $storeManager;
        $this->_subscriptionHelper = $subscriptionHelper;
        $this->_formKey = $formKey;
        $this->_checkoutSession = $checkoutSession;
        $this->_customerSession = $customerSession;
        $this->_customerFactory = $customerFactory;
        $this->_product = $product;
        $this->_productFactory = $productFactory;
        $this->_productRepository = $productRepository;
        $this->_orderCollectionFactory = $orderCollectionFactory;
        $this->_recurlyHelper = $recurlyHelper;
        $this->_quoteManagement = $quoteManagement;
        $this->_quoteRepository = $quoteRepository;
    }

    /**
     * Get the associated customer.
     *
     * @return \Magento\Customer\Model\Customer|null
     */
    public function getCustomer()
    {
        if (! $this->getData('customer_id')) {
            return null;
        }

        $customer = $this->_customerFactory->create();
        $customer->load($this->getData('customer_id'));

        if (! $customer->getId()) {
            return null;
        }

        return $customer;
    }

    /**
     * Get subscription orders
     * @return SubscriptionOrderCollection|mixed
     */
    public function getSubscriptionOrders()
    {
        // If subscription orders is local, send them, if not, pull them and send them
        if (! isset($this->_subscriptionOrders)) {
            $subscriptionOrders = $this->_subscriptionOrderCollectionFactory->create();
            $subscriptionOrders
                ->setOrder('ship_start_date', 'asc')
                ->addFieldToFilter('subscription_entity_id', $this->getEntityId());
            $this->_subscriptionOrders = $subscriptionOrders;
        }

        return $this->_subscriptionOrders;
    }

    /**
     * Get the core product order items for the subscription.
     *
     * @return SubscriptionOrderItem[]
     */
    public function getOrderItems()
    {
        $items = [];
        $orders = $this->getSubscriptionOrders() ?: [];

        foreach ($orders as $order) {
            /** @var SubscriptionOrder $order */
            $orderItems = $order->getOrderItems() ?: [];

            foreach ($orderItems as $item) {
                /** @var SubscriptionOrderItem $item */
                try {
                    $items[] = $item;
                } catch (\Exception $ex) {
                    continue;
                }
            }
        }

        return $items;
    }

    /**
     * Get the add-on order items for the subscription.
     *
     * @return SubscriptionAddonOrderItem[]
     */
    public function getAddOnOrderItems()
    {
        $items = [];
        $orders = $this->getSubscriptionAddonOrders() ?: [];

        foreach ($orders as $order) {
            /** @var SubscriptionAddonOrder $order */
            $orderItems = $order->getOrderItems() ?: [];

            foreach ($orderItems as $item) {
                /** @var SubscriptionOrderItem $item */
                try {
                    $items[] = $item;
                } catch (\Exception $ex) {
                    continue;
                }
            }
        }

        return $items;
    }

    /**
     * Get the core products for the subscription.
     *
     * @return Product[]
     */
    public function getCoreProducts()
    {
        $coreProducts = [];
        $orderItems = $this->getOrderItems();

        foreach ($orderItems as $item) {
            /** @var SubscriptionOrderItem $item */
            try {
                $product = $item->getProduct();
                $coreProducts[] = $product;
            } catch (\Exception $ex) {
                continue;
            }
        }

        return $coreProducts;
    }

    /**
     * Get the add-on products for the subscription.
     *
     * @return Product[]
     */
    public function getAddOnProducts()
    {
        $addOnProducts = [];
        $orderItems = $this->getAddOnOrderItems();

        foreach ($orderItems as $item) {
            /** @var SubscriptionAddonOrderItem $item */
            try {
                $product = $item->getProduct();
                $addOnProducts[] = $product;
            } catch (\Exception $ex) {
                continue;
            }
        }

        return $addOnProducts;
    }

    /**
     * Get subscription order by season slug
     * @param string $seasonSlug
     * @return SubscriptionOrderCollection|mixed
     */
    public function getSubscriptionOrderBySeasonSlug(string $seasonSlug)
    {

        // Make sure we have an actual subscription
        if (empty($this->getEntityId())) {
            return false;
        }

        $subscriptionOrders = $this->_subscriptionOrderCollectionFactory->create();
        $subscriptionOrders->addFieldToFilter('subscription_entity_id', $this->getEntityId());
        $subscriptionOrders->addFieldToFilter('season_slug', $seasonSlug);

        return $subscriptionOrders->fetchItem();
    }

    /**
     * Get subscription addon orders
     * @return SubscriptionAddonOrderCollection|mixed
     */
    public function getSubscriptionAddonOrders()
    {
        // If subscription orders is local, send them, if not, pull them and send them
        $subscriptionAddonOrders = $this->_subscriptionAddonOrderCollectionFactory->create();
        $subscriptionAddonOrders->addFieldToFilter('subscription_entity_id', $this->getEntityId());
        $this->_subscriptionAddonOrders = $subscriptionAddonOrders;

        return $this->_subscriptionAddonOrders;
    }

    /**
     * Generate the shipment dates for subscription orders associated with this subscription
     * @return bool
     * @throws \Exception
     */
    public function generateShipDates()
    {

        // Make sure we have an actual subscription and that we have a subscription type
        if (empty($this->getEntityId()) || empty($this->getSubscriptionType())) {
            return false;
        }

        // For each subscription order, generate shipment dates
        /** @var SubscriptionOrder $subscriptionOrder */
        foreach ($this->getSubscriptionOrders() as $subscriptionOrder) {
            $subscriptionOrder->generateShipDates();
        }

        // For each subscription order, generate shipment dates
        /** @var SubscriptionAddonOrder $subscriptionAddonOrder */
        foreach ($this->getSubscriptionAddonOrders() as $subscriptionAddonOrder) {
            $subscriptionAddonOrder->generateShipDates();
        }
    }

    /**
     * @param $addons
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function addSubscriptionToCart($addons)
    {
        try {
            $this->_checkoutSession->resetCheckout();
            $this->_checkoutSession->clearQuote();
            $quoteID = $this->_quoteManagement->createEmptyCartForCustomer($this->_customerSession->getCustomerId());
            $quote = $this->_quoteRepository->get($quoteID);
            $quote->removeAllAddresses();
        } catch (\Exception $e) {
            $error = 'Could not create empty cart for customer. - ' . $e->getMessage();
            $this->_logger->error($error);

            throw new \Exception($error);
        }

        // We will have to calculate the price differently for the subscription than we normally would
        $totalSubscriptionPrice = 0;

        // Go through all the core products, add them to cart and calculate
        // the total subscription price which will be applied to the Annual Subscription product
        foreach ($this->getSubscriptionOrders() as $subscriptionOrder) {
            foreach ($subscriptionOrder->getOrderItems() as $subscriptionOrderItem) {
                $product = $subscriptionOrderItem->getProduct();
                $product = $this->_productRepository->get($product->getSku());
                $totalSubscriptionPrice += $product->getPrice() * $subscriptionOrderItem->getQty();
            }

            if ($this->getSubscriptionType() == 'seasonal') {
                break;
            }
        }

        try {
            // Get the first seasonal product or annual depending on type
            $subscriptionOrders = $this->getSubscriptionOrders();
            $planSku = 'annual';
            if ($this->getSubscriptionType() !== 'annual') {
                $firstSeason = $subscriptionOrders->getFirstItem();
                $planSku = $this->getPlanCodeByName($firstSeason->getSeasonName());
            }

            $seasonalProduct = $this->_productRepository->get($planSku);
            $quote->addProduct($seasonalProduct, 1)->save();

            // Add the discount if it's annual
            if ($this->getSubscriptionType() == 'annual') {
                $quote->setCouponCode('annual_discount');
            }

            $quote->save();
        } catch (\Exception $e) {
            $error = 'There was an issue adding the subscription to the cart. - ' . $e->getMessage();
            $this->_logger->error($error);

            throw new \Exception($error);
        }

        try {
            // Go through all selected AddOn Products and add them to the cart
            foreach ($this->getSubscriptionAddonOrders() as $subscriptionAddonOrder) {
                foreach ($subscriptionAddonOrder->getOrderItems() as $subscriptionAddonOrderItem) {
                    if (in_array($subscriptionAddonOrderItem->getCatalogProductSku(), $addons)) {
                        try {
                            $product = $subscriptionAddonOrderItem->getProduct();
                            $quote->addProduct($product, 1)->save();

                            $price = (float)$product->getPrice() * (int)$subscriptionAddonOrderItem->getQty();
                            $item = $quote->getItemByProduct($product);
                            $item->setCustomPrice((float)$price);
                            $item->setOriginalCustomPrice((float)$price);
                            $item->getProduct()->setIsSuperMode(true);
                        } catch (\Exception $e) {
                            $error = 'There were issues adding add-on products to the cart. - ' . $e->getMessage();
                            $this->_logger->error($error);

                            throw new \Exception($error);
                        }

                        $subscriptionAddonOrderItem->setSelected(1);
                    } else {
                        $subscriptionAddonOrderItem->setSelected(0);
                    }
                    $subscriptionAddonOrderItem->save();
                }
            }

            $quote->save();
            $this->_checkoutSession->replaceQuote($quote);
        } catch (\Exception $e) {
            $error = 'There was an issue saving the quote. - ' . $e->getMessage();
            $this->_logger->error($error);

            throw new \Exception($error);
        }

        // Go through the cart items and modify their prices for the current customer order
        try {

            // If future shipment, don't add a dollar value to the cart
            $item = $quote->getItemByProduct($seasonalProduct);
            $item->setStoreId($this->_storeManager->getStore()->getStoreId());
            $item->setCustomPrice((float)$totalSubscriptionPrice);
            $item->setOriginalCustomPrice($item->getCustomPrice());
            $item->setBasePrice($item->getCustomPrice());
            $item->setPrice($item->getCustomPrice());
            $item->getProduct()->setIsSuperMode(true);
            $item->calcRowTotal()->save();

            // Update Cart
            $quote->collectTotals()->save();
        } catch (\Exception $e) {
            $error = 'There was an issue updating the cart item pricing. - ' . $e->getMessage();
            $this->_logger->error($error);

            throw new \Exception($error);
        }
    }

    /**
     * @return bool
     * @throws \Exception
     */
    public function isCurrentlyShippable()
    {
        if ($this->getSubscriptionType() !== 'annual') {

            // Test of seasonal is not shippable
            $today = new \DateTime();
            $shipStart = \DateTime::createFromFormat('Y-m-d H:i:s', $this->getFirstSubscriptionOrder()->getShipStartDate());
            return $today >= $shipStart;
        }
        return true;
    }

    /**
     * Return the first subscription order
     * @return SubscriptionOrder|mixed
     */
    public function getFirstSubscriptionOrder()
    {
        $subscriptionOrders = $this->getSubscriptionOrders();
        if (! $subscriptionOrders) {
            return false;
        }
        return $subscriptionOrders->getFirstItem();
    }

    /**
     * Get the first add on product.
     *
     * @return Product|false
     */
    public function getAddOn()
    {
        $addOnOrders = $this->getSubscriptionAddonOrders();

        if (! $addOnOrders) {
            return false;
        }

        $order = $addOnOrders->getFirstItem();

        if (! $order) {
            return false;
        }

        $items = $order->getOrderItems();

        if (! $items) {
            return false;
        }

        $addOn = $items->getFirstItem();

        return $addOn ? $addOn->getProduct() : false;
    }

    /**
     * Creates the subscription using subscription service
     * @param $token
     * @param $service
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function createSubscriptionService($token, $service)
    {

        /** @var RecurlySubscription $service */
        $service->createSubscription($token, $this);
    }

    /**
     * Get Plan Code by Name
     * @param $name
     * @return string
     */
    private function getPlanCodeByName($name)
    {
        return $this->_recurlyHelper->getSeasonSlugByName($name);
    }

    /**
     * Cancel Subscriptions
     * @param $service
     * @throws LocalizedException
     */
    public function cancelSubscriptions($service)
    {
        $subscriptionOrders = [];

        // Get orders that apply
        $orders = $this->getOrders(true, false);

        // Create Credit Memos
        foreach ($orders as $order) {
            try {
                /** @var SubscriptionOrder $subscriptionOrder */
                if ($order->getSubscriptionAddon()) {
                    $subscriptionOrder = $this->_subscriptionAddonOrderCollectionFactory->create()->addFieldToFilter('sales_order_id', $order->getEntityId())->getFirstItem();
                } else {
                    $subscriptionOrder = $this->_subscriptionOrderCollectionFactory->create()->addFieldToFilter('sales_order_id', $order->getEntityId())->getFirstItem();
                }
                $subscriptionOrder->createCreditMemo();
                $subscriptionOrders[] = $subscriptionOrder;
            } catch (\Exception $e) {
                $error = 'There was a problem making a credit memo for subscription cancellation. ' . $e->getMessage();
                $this->_logger->error($error);
                throw new LocalizedException(__($error));
            }
        }

        // Generate Refund
        try {
            $this->generateRefund($orders, $service);
        } catch (\Exception $e) {
            $error = 'There was a problem generating a refund.' . $e->getMessage();
            $this->_logger->error($error);
            throw new LocalizedException(__($error));
        }

        // Update Subscription statuses
        try {
            $this->updateCanceledStatuses($subscriptionOrders);
        } catch (\Exception $e) {
            $error = 'There was a problem updating statuses.' . $e->getMessage();
            $this->_logger->error($error);
            throw new LocalizedException(__($error));
        }
    }

    /**
     * Get the total number of orders for this subscription.
     * @return int
     */
    public function getOrderCount()
    {
        return $this->_orderCollectionFactory->create()->addFieldToFilter('master_subscription_id', $this->getSubscriptionId())->count();
    }

    /**
     * Return a filtered array of Orders associated with this subscription
     * @param bool|null $filterByInvoiced null to ignore filter, true to filter positively (has invoices), false to
     *  filter negatively
     * @param bool|null $filterByShipped null to ignore filter, true to filter positively (has shipments), false to
     *  filter negatively
     * @return array
     * @throws LocalizedException
     */
    public function getOrders(bool $filterByInvoiced = null, bool $filterByShipped = null)
    {
        $ordersArray = [];

        try {
            $orders = $this->_orderCollectionFactory->create()->addFieldToFilter('master_subscription_id', $this->getSubscriptionId());
        } catch (\Exception $e) {
            $error = 'There was an issue returning orders to cancel.';
            $this->_logger->error($error);
            throw new LocalizedException(__($error));
        }

        /** @var Order $order */
        foreach ($orders as $order) {
            $hasInvoices = $order->getInvoiceCollection()->count() > 0;
            $hasShipments = $order->getShipmentsCollection()->count() > 0;

            if (
                (is_null($filterByInvoiced) || $filterByInvoiced === $hasInvoices)
                    &&
                (is_null($filterByShipped) || $filterByShipped === $hasShipments)
            ) {
                $ordersArray[] = $order;
            }
        }

        return $ordersArray;
    }

    /**
     * Generate Refund
     * @param array $orders
     * @param $service
     * @throws LocalizedException
     */
    private function generateRefund($orders, $service)
    {
        $refundingEntireSubscription = count($orders) == $this->getOrderCount();

        try {
            $totalRefund = 0;

            if ($refundingEntireSubscription) {
                $this->setData('is_full_refund', 1);
                $totalRefund = $this->getPaid();
            } else {
                /** @var \SMG\Sales\Model\Order $order */
                foreach ($orders as $order) {
                    $totalRefund += $order->getSubtotal() + $order->getDiscountAmount();
                }
            }

            /** @var RecurlySubscription $service */
            $service->createCredit($this->getGigyaId(), $totalRefund);
            $this->save();
        } catch (\Exeception $e) {
            $error = 'Cannot generate refund.';
            $this->_logger->error($error);
            throw new LocalizedException(__($error));
        }
    }

    /**
     * Update Canceled Statuses
     * @param $subscriptionOrders
     */
    private function updateCanceledStatuses($subscriptionOrders)
    {
        foreach ($subscriptionOrders as $subscriptionOrder) {
            $subscriptionOrder->setSubscriptionOrderStatus('canceled')->save();
        }
        $this->setSubscriptionStatus('canceled')->save();
    }
}
