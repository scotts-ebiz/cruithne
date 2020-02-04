<?php

namespace SMG\SubscriptionApi\Model;

use Psr\Log\LoggerInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Model\ProductRepository;
use Magento\Checkout\Model\Cart;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Data\Form\FormKey;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Magento\Store\Model\StoreManager;
use SMG\SubscriptionApi\Helper\SubscriptionHelper;
use SMG\SubscriptionApi\Model\ResourceModel\SubscriptionAddonOrder\Collection as SubscriptionAddonOrderCollection;
use SMG\SubscriptionApi\Model\ResourceModel\SubscriptionAddonOrder\Collection\Interceptor as SubscriptionAddonOrderCollectionInterceptor;
use SMG\SubscriptionApi\Model\ResourceModel\SubscriptionAddonOrder\CollectionFactory as SubscriptionAddonOrderCollectionFactory;
use SMG\SubscriptionApi\Model\ResourceModel\SubscriptionOrder\Collection as SubscriptionOrderCollection;
use SMG\SubscriptionApi\Model\ResourceModel\SubscriptionOrder\Collection\Interceptor as SubscriptionOrderCollectionInterceptor;
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

    /** @var Cart */
    protected $_cart;

    /**  @var SubscriptionHelper */
    protected $_subscriptionHelper;

    /** @var SubscriptionOrderCollectionInterceptor */
    protected $_subscriptionOrders;

    /** @var SubscriptionAddonOrderCollectionInterceptor */
    protected $_subscriptionAddonOrders;

    /** @var FormKey */
    protected $_formKey;

    /** @var CheckoutSession */
    protected $_checkoutSession;

    /**  @var CartManagementInterface */
    protected $_cartManagementInterface;

    /** @var CartRepositoryInterface */
    protected $_cartRepositoryInterface;

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
     * @param Cart $cart
     * @param SubscriptionHelper $subscriptionHelper
     * @param FormKey $formKey
     * @param CheckoutSession $checkoutSession
     * @param CartManagementInterface $cartManagementInterface
     * @param CartRepositoryInterface $cartRepositoryInterface
     * @param StoreManager $storeManager
     * @param Product $product
     * @param ProductFactory $productFactory
     * @param ProductRepository $productRepository
     * @param OrderCollectionFactory $orderCollectionFactory
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
        Cart $cart,
        SubscriptionHelper $subscriptionHelper,
        FormKey $formKey,
        CheckoutSession $checkoutSession,
        CartManagementInterface $cartManagementInterface,
        CartRepositoryInterface $cartRepositoryInterface,
        StoreManager $storeManager,
        Product $product,
        ProductFactory $productFactory,
        ProductRepository $productRepository,
        OrderCollectionFactory $orderCollectionFactory,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);

        $this->_logger = $logger;
        $this->_subscriptionOrderCollectionFactory = $subscriptionOrderCollectionFactory;
        $this->_subscriptionAddonOrderCollectionFactory = $subscriptionAddonOrderCollectionFactory;
        $this->_cart = $cart;
        $this->_cartManagementInterface = $cartManagementInterface;
        $this->_cartRepositoryInterface = $cartRepositoryInterface;
        $this->_storeManager = $storeManager;
        $this->_subscriptionHelper = $subscriptionHelper;
        $this->_formKey = $formKey;
        $this->_checkoutSession = $checkoutSession;
        $this->_product = $product;
        $this->_productFactory = $productFactory;
        $this->_productRepository = $productRepository;
        $this->_orderCollectionFactory = $orderCollectionFactory;
    }

    /**
     * Get subscription orders
     * @return SubscriptionOrderCollection|mixed
     */
    public function getSubscriptionOrders()
    {
        // Make sure we have an actual subscription
        if (empty($this->getEntityId())) {
            return false;
        }

        // If subscription orders is local, send them, if not, pull them and send them
        if (! isset($this->_subscriptionOrders)) {
            $subscriptionOrders = $this->_subscriptionOrderCollectionFactory->create();
            $subscriptionOrders->addFieldToFilter('subscription_entity_id', $this->getEntityId());
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

        // Make sure we have an actual subscription
        if (empty($this->getEntityId())) {
            return false;
        }

        // If subscription orders is local, send them, if not, pull them and send them
        if (! isset($this->_subscriptionAddonOrders)) {
            $subscriptionAddonOrders = $this->_subscriptionAddonOrderCollectionFactory->create();
            $subscriptionAddonOrders->addFieldToFilter('subscription_entity_id', $this->getEntityId());
            $this->_subscriptionAddonOrders = $subscriptionAddonOrders;
        }

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
            $quote = $this->_checkoutSession->getQuote();
            $quoteItems = $quote->getItemsCollection();
            foreach ($quoteItems as $item) {
                $this->_cart->removeItem($item->getItemId());
            }
        } catch (\Exception $e) {
            $error = 'Could not add remove items from cart. - ' . $e->getMessage();
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
            $params = [
                'form_key'  => $this->_formKey->getFormKey(),
                'qty'       => 1
            ];
            $this->_cart->addProduct($seasonalProduct->getId(), $params)->save();

            // Add the discount if it's annual
            if ($this->getSubscriptionType() == 'annual') {
                $this->_cart->getQuote()->setCouponCode('annual_discount')->collectTotals()->save();
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
                            $product = $this->_productRepository->get($product->getSku());
                            $productId = $product->getId();
                            $params = [
                                'form_key' => $this->_formKey->getFormKey(),
                                'qty' => 1,
                            ];
                            $this->_cart->addProduct($productId, $params)->save();

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
            $shipStart = new \DateTime($this->getFirstSubscriptionOrder()->getShipStartDate());
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
        switch ($name) {
            case 'Early Spring Feeding':
                return 'early-spring';
            case 'Late Spring Feeding':
                return 'late-spring';
            case 'Early Summer Feeding':
                return 'early-summer';
            case 'Early Fall Feeding':
                return 'early-fall';
            default:
                return false;
        }
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
                    $subscriptionOrder = $this->_subscriptionOrderCollectionFactory->create()->addFieldToFilter('sales_order_id', $order->getEntityId())->getFirstItem();
                } else {
                    $subscriptionOrder = $this->_subscriptionOrderCollectionFactory->create()->addFieldToFilter('sales_order_id', $order->getEntityId())->getFirstItem();
                }
                $subscriptionOrder->createCreditMemo();
                $subscriptionOrders[] = $subscriptionOrder;
            } catch (\Exception $e) {
                $error = 'There was a problem making a credit memo for subscription cancellation.' . $e->getMessage();
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
        try {
            $totalRefund = 0;
            /** @var \SMG\Sales\Model\Order $order */
            foreach ($orders as $order) {
                $totalRefund += (float) $order->getGrandTotal();
            }
            /** @var RecurlySubscription $service */
            $service->createCredit($totalRefund, $this->getGigyaId());
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
