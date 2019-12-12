<?php

namespace SMG\SubscriptionApi\Model;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Model\ProductRepository;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Data\Form\FormKey;
use Magento\Framework\Registry;
use Magento\Checkout\Model\Cart;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote;
use Magento\Store\Model\StoreManager;
use SMG\SubscriptionApi\Helper\SubscriptionHelper;
use SMG\SubscriptionApi\Model\ResourceModel\SubscriptionOrder\Collection\Interceptor as SubscriptionOrderCollectionInterceptor;
use SMG\SubscriptionApi\Model\ResourceModel\SubscriptionOrder\CollectionFactory as SubscriptionOrderCollectionFactory;
use SMG\SubscriptionApi\Model\ResourceModel\SubscriptionAddonOrder\Collection\Interceptor as SubscriptionAddonOrderCollectionInterceptor;
use SMG\SubscriptionApi\Model\ResourceModel\SubscriptionAddonOrder\CollectionFactory as SubscriptionAddonOrderCollectionFactory;

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
    private $_cartManagementInterface;

    /** @var CartRepositoryInterface */
    private $_cartRepositoryInterface;

    /** @var StoreManager */
    private $_storeManager;

    /** @var Quote */
    private $_quote;

    /** @var Product */
    private $_product;

    /**  @var ProductFactory */
    private $_productFactory;

    /** @var ProductRepository */
    private $_productRepository;

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
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
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
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);

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
    }

    /**
     * Get subscription orders
     * @return mixed
     */
    public function getSubscriptionOrders()
    {

        // Make sure we have an actual subscription
        if ( is_null( $this->getEntityId() ) ) {
            return false;
        }

        // If subscription orders is local, send them, if not, pull them and send them
        if ( ! isset($this->_subscriptionOrders) ) {
            $subscriptionOrders = $this->_subscriptionOrderCollectionFactory->create();
            $subscriptionOrders->addFieldToFilter( 'subscription_entity_id', $this->getEntityId() );
            $this->_subscriptionOrders = $subscriptionOrders;
        }

        return $this->_subscriptionOrders;
    }


    /**
     * Get subscription addon orders
     * @return mixed
     */
    protected function getSubscriptionAddonOrders()
    {

        // Make sure we have an actual subscription
        if ( is_null( $this->getEntityId() ) ) {
            return false;
        }

        // If subscription orders is local, send them, if not, pull them and send them
        if ( ! isset( $this->_subscriptionAddonOrders ) ) {
            $subscriptionAddonOrders = $this->_subscriptionAddonOrderCollectionFactory->create();
            $subscriptionAddonOrders->addFieldToFilter( 'subscription_entity_id', $this->getEntityId() );
            $this->_subscriptionAddonOrders = $subscriptionAddonOrders;
        }

        return $this->_subscriptionAddonOrders;
    }

    /**
     * Generate the shipment dates for subscription orders associated with this subscription
     * @return bool
     * @throws \Exception
     */
    public function generateShipDates() {

        // Make sure we have an actual subscription and that we have a subscription type
        if ( is_null( $this->getEntityId() ) || is_null( $this->getSubscriptionType() ) ) {
            return false;
        }

        // For each subscription order, generate shipment dates
        /** @var SubscriptionOrder $subscriptionOrder */
        foreach ( $this->getSubscriptionOrders() as $subscriptionOrder ) {
            $subscriptionOrder->generateShipDates();
        }

        // For each subscription order, generate shipment dates
        /** @var SubscriptionAddonOrder $subscriptionAddonOrder */
        foreach ( $this->getSubscriptionAddonOrders() as $subscriptionAddonOrder ) {
            $subscriptionAddonOrder->generateShipDates();
        }
    }

    public function addSubscriptionToCart( $addons ) {

        // Before starting to add new products, let's clear customer's cart
        $quote = $this->_checkoutSession->getQuote();
        $quoteItems = $quote->getItemsCollection();
        foreach( $quoteItems as $item ) {
            $this->_cart->removeItem( $item->getItemId() )->save();
        }
        $cartId = $this->_cartManagementInterface->createEmptyCart();
        $this->_quote = $this->_cartRepositoryInterface->get( $cartId );

        // We will have to calculate the price differently for the subscription than we normally would
        $totalSubscriptionPrice = 0;

        // Add "Annual Subscription" product if the customer selected the annual subscription plan
        if ($this->getSubscriptionType() == 'annual') {
            try {
                $_product = $this->_productRepository->get('annual');
                $productId = $_product->getId();
                $params = [
                    'form_key'  => $this->_formKey->getFormKey(),
                    'qty'       => 1,
                ];
                $this->_cart->addProduct($productId, $params);
            } catch (Exception $e) {
                throw $e;
            }
        }

        // Go through all the core products, add them to cart and calculate
        // the total subscription price which will be applied to the Annual Subscription product
        foreach ( $this->getSubscriptionOrders() as $subscriptionOrder ) {

            foreach ($subscriptionOrder->getSubscriptionOrderItems() as $subscriptionOrderItem) {
                $product = $subscriptionOrderItem->getProduct();
                $product = $this->_productRepository->get($product->getSku());
                $totalSubscriptionPrice += $product->getPrice() * $subscriptionOrderItem->getQty();
            }

            if ($this->getSubscriptionType() == 'seasonal') {
                break;
            }
        }

        try {
            $seasonalProduct = $this->_productRepository->get( 'annual' );
            $params = [
                'form_key'  => $this->_formKey->getFormKey(),
                'qty'       => 1
            ];
            $this->_cart->addProduct($seasonalProduct->getId(), $params);
        } catch (Exception $e) {
            throw $e;
        }

        // Go through all selected AddOn Products and add them to the cart
        foreach ( $this->getSubscriptionAddonOrders() as $subscriptionAddonOrder ) {

            foreach ( $subscriptionAddonOrder->getSubscriptionAddonOrderItems() as $subscriptionAddonOrderItem ) {

                if (in_array($subscriptionAddonOrderItem->getCatalogProductSku(), $addons)) {
                    try {
                        $product = $subscriptionAddonOrderItem->getProduct();
                        $product = $this->_productRepository->get($product->getSku());
                        $productId = $product->getId();
                        $params = [
                            'form_key' => $this->_formKey->getFormKey(),
                            'qty' => 1,
                        ];
                        $this->_cart->addProduct($productId, $params);

                        $price = (float) $product->getPrice() * (int) $subscriptionAddonOrderItem->getQty();
                        $item = $this->_cart->getQuote()->getItemByProduct( $product );
                        $item->setCustomPrice( (float) $price );
                        $item->setOriginalCustomPrice( (float) $price );
                        $item->getProduct()->setIsSuperMode( true );
                    } catch (Exception $e) {
                        throw $e;
                    }
                    $subscriptionAddonOrderItem->setSelected(1);
                } else {
                    $subscriptionAddonOrderItem->setSelected(0);
                }
                $subscriptionAddonOrderItem->save();
            }
        }

        // Apply discount code for all annual subscriptions
        if ( $this->getSubscriptionType() == 'annual') {
            $this->_cart->getQuote()->setCouponCode('annual_discount')->collectTotals()->save();
        }

        // Save cart
        $this->_cart->save();

        // Go through the cart items and modify their prices for the current customer order
        $item = $this->_cart->getQuote()->getItemByProduct( $seasonalProduct );
        $item->setCustomPrice( (float) $totalSubscriptionPrice );
        $item->setOriginalCustomPrice( (float) $totalSubscriptionPrice );
        $item->getProduct()->setIsSuperMode( true );

        // Update Cart
        $this->_cart->getQuote()->collectTotals()->save();
        $this->_cart->save();

        $items = $this->_cart->getQuote()->getItems();
        foreach ($items as $item) {
            var_dump([
                $item->getName(),
                $item->getSku(),
                $item->getQty(),
                $item->getCustomPrice()
            ]);
        }

        var_dump([
            $this->_cart->getQuote()->getSubtotal(),
            $this->_cart->getQuote()->getSubtotalWithDiscount(),
            $this->_cart->getQuote()->getGrandTotal(),
            $this->_cart->getQuote()->getItemsCount(),
            $this->_cart->getQuote()->getCouponCode()
        ]);
        die;
    }

//    /**
//     * @return bool|false|string
//     * @throws \Magento\Framework\Exception\LocalizedException
//     * @throws \Exception
//     */
//    public function addSubscriptionToCart()
//    {
//
//        // Make sure we have an actual subscription
//        if (is_null($this->getEntityId())) {
//            return false;
//        }
//
//        $store = $this->_storeManager->getStore();
//
//        // Before starting to add new products, let's clear customer's cart
//        $mainQuote = $this->_checkoutSession->getQuote();
//        $quoteItems = $mainQuote->getItemsCollection();
//        foreach( $quoteItems as $item ) {
//            $this->_cart->removeItem($item->getItemId())->save();
//        }
//        $cartId = $this->_cartManagementInterface->createEmptyCart();
//        $this->_quote = $this->_cartRepositoryInterface->get($cartId);
//        $this->_quote->setStore($store);
//
//        if ($this->getSubscriptionType() == 'annual') {
//            $this->addSubscriptionToCartAnnual();
//            $quoteItems = $this->_quote->getItemsCollection();
//            /** @var Product $item */
//            foreach( $quoteItems as $item ) {
//                var_dump([$item->getSku(), $item->getQty(), $item->getPrice()]);
//            }
//            $this->_checkoutSession->getQuote()->setCouponCode('annual_discount')->collectTotals()->save();
//
//        } else {
//            $this->addSubscriptionToCartSeasonal();
//            $quoteItems = $this->_quote->getItemsCollection();
//            /** @var Product $item */
//            foreach( $quoteItems as $item ) {
//                var_dump([$item->getSku(), $item->getQty(), $item->getPrice()]);
//            }
//        }
//
//        $this->_checkoutSession->getQuote()->collectTotals()->save();
//        var_dump([$this->_quote->getBaseSubtotal(), $this->_quote->getBaseSubtotalWithDiscount(), $this->_quote->getGrandTotal(), $this->_quote->getCouponCode()]);
//
//    }
//
//    /**
//     * Add Subscription to cart for Annual Subscriptions
//     *
//     * If a subscription is of type annual, we will add all of the items to the cart for consideration.
//     *
//     * @throws \Magento\Framework\Exception\LocalizedException
//     * @throws \Magento\Framework\Exception\NoSuchEntityException
//     */
//    private function addSubscriptionToCartAnnual() {
//
//        $totalSubscriptionPrice = 0;
//
//        // Add a dumb product to the cart
//        $product = $this->_productRepository->get('annual');
//        $this->_quote->addProduct( $product , 1 );
//
//        /** @var SubscriptionOrder $subscriptionOrder */
//        foreach ( $this->getSubscriptionOrders() as $subscriptionOrder ) {
//
//            /** @var SubscriptionOrderItem $subscriptionOrderItem */
//            foreach ( $subscriptionOrder->getSubscriptionOrderItems() as $subscriptionOrderItem ) {
//
//                $product = $subscriptionOrderItem->getProduct();
//                $totalSubscriptionPrice += ( (float)$product->getPrice() * (int)$subscriptionOrderItem->getQty() );
//                var_dump([$product->getSku(), $subscriptionOrderItem->getQty(), $product->getPrice(), $totalSubscriptionPrice]);
//            }
//        }
//
//        // Overwrite the values with the calculations we just camp up with
//        $items = $this->_quote->getItemsCollection();
//        $item = $items->getFirstItem();
//        $item->setCustomPrice($totalSubscriptionPrice);
//        $item->setOriginalCustomPrice($totalSubscriptionPrice);
//        $item->getProduct()->setIsSuperMode(true);
//        $this->_quote->collectTotals();
//        $this->_quote->save();
//    }
//
//    /**
//     * Add Subscription to Cart for Seasonal Subscriptions
//     *
//     * If the subscription type is seasonal, we will add only the first seasonal subscriptions items, and only if the
//     * shipment date is today or before.
//     *
//     * @throws \Magento\Framework\Exception\LocalizedException
//     * @throws \Magento\Framework\Exception\NoSuchEntityException
//     */
//    private function addSubscriptionToCartSeasonal() {
//
//        /** @var SubscriptionOrderCollectionInterceptor $subscriptionOrders */
//        $subscriptionOrders = $this->getSubscriptionOrders();
//        /** @var SubscriptionOrder $firstSubscriptionOrder */
//        $firstSubscriptionOrder = $subscriptionOrders->fetchItem();
//        $shipmentDate = new \DateTime( date( $firstSubscriptionOrder->getShipStartDate() ) );
//        $todayDate = new \DateTime(date('Y-m-d 00:00:00'));
//
//        $totalSubscriptionPrice = 0;
//
//        // Add a dumb product to the cart
//        $product = $this->_productRepository->get('annual');
//        $this->_quote->addProduct( $product , 1 )->save();
//
//        // Add items to cart for the first shipment if it's today or in the past
//        if ( $todayDate >= $shipmentDate ) {
//
//            /** @var SubscriptionOrderItem $subscriptionOrderItem */
//            foreach ( $firstSubscriptionOrder->getSubscriptionOrderItems() as $subscriptionOrderItem ) {
//
//                $product = $subscriptionOrderItem->getProduct();
//                $totalSubscriptionPrice += ( (float)$product->getPrice() * (int)$subscriptionOrderItem->getQty() );
//                var_dump([$product->getSku(), $subscriptionOrderItem->getQty(), $product->getPrice(), $totalSubscriptionPrice]);
//
//            }
//        }
//
//        // Overwrite the values with the calculations we just camp up with
//        $items = $this->_quote->getItemsCollection();
//        $item = $items->getFirstItem();
//        $item->setCustomPrice($totalSubscriptionPrice);
//        $item->setOriginalCustomPrice($totalSubscriptionPrice);
//        $item->getProduct()->setIsSuperMode(true);
//        $this->_quote->collectTotals();
//        $this->_quote->save();
//    }
//
//    /**
//     * Add Addon Items to Subscription
//     * @param array $addons
//     * @throws \Magento\Framework\Exception\LocalizedException
//     */
//    public function addAddons( array $addons )
//    {
//        /** @var SubscriptionAddonOrderCollectionInterceptor $subscriptionAddonOrders */
//        $subscriptionAddonOrders = $this->getSubscriptionAddonOrders();
//
//        /** @var SubscriptionAddonOrder $subscriptionAddonOrder */
//        foreach ( $subscriptionAddonOrders as $subscriptionAddonOrder ) {
//
//            /** @var SubscriptionAddonOrderItem $subscriptionAddonOrderItem */
//            foreach ( $subscriptionAddonOrder->getSubscriptionAddonOrderItems() as $subscriptionAddonOrderItem ) {
//
//                // Mark as selected in the DB
//                if ( in_array( $subscriptionAddonOrderItem->getCatalogProductSku(), $addons ) ) {
//                    $subscriptionAddonOrderItem->setSelected(1);
//                    $subscriptionAddonOrderItem->save();
//                }
//
//                $product = $this->_productRepository->get( $subscriptionAddonOrderItem->getProduct()->getSku() );
//                $this->_quote->addProduct( $product , $subscriptionAddonOrderItem->getQty() )->save();
//                $price = (float) $product->getPrice() * (int) $product->getQty();
//                $items = $this->_quote->getItemsCollection();
//                $item = $items->getFirstItem();
//                $item->setCustomPrice( (float)$price );
//                $item->setOriginalCustomPrice( (float)$price );
////                $item->getProduct()->setIsSuperMode(true);
//            }
//        }
//
//        $quoteItems = $this->_quote->getItemsCollection();
//        /** @var Product $item */
//        foreach( $quoteItems as $item ) {
//            var_dump([$item->getSku(), $item->getQty(), $item->getPrice()]);
//        }
//
//        $this->_checkoutSession->getQuote()->collectTotals()->save();
//        var_dump([$this->_quote->getBaseSubtotal(), $this->_quote->getBaseSubtotalWithDiscount(), $this->_quote->getGrandTotal(), $this->_quote->getCouponCode()]);
//
//    }

}