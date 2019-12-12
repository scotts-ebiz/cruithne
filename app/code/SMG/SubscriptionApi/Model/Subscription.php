<?php

namespace SMG\SubscriptionApi\Model;

use Magento\Catalog\Model\Product;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Data\Form\FormKey;
use Magento\Framework\Registry;
use Magento\Checkout\Model\Cart;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use SMG\SubscriptionApi\Helper\SubscriptionHelper;
use SMG\SubscriptionApi\Model\ResourceModel\SubscriptionOrder\Collection\Interceptor;
use SMG\SubscriptionApi\Model\ResourceModel\SubscriptionOrder\CollectionFactory as SubscriptionOrderCollectionFactory;

/**
 * Class Subscription
 * @package SMG\SubscriptionApi\Model
 */
class Subscription extends AbstractModel
{

    /** @var SubscriptionOrderCollectionFactory */
    protected $_subscriptionOrderCollectionFactory;

    /** @var Cart */
    protected $_cart;

    /**  @var SubscriptionHelper */
    protected $_subscriptionHelper;

    /** @var array */
    protected $_subscriptionOrders;

    /** @var FormKey */
    protected $_formKey;

    /** @var CheckoutSession */
    protected $_checkoutSession;

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
     * @param Cart $cart
     * @param SubscriptionHelper $subscriptionHelper
     * @param FormKey $formKey
     * @param CheckoutSession $checkoutSession
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        SubscriptionOrderCollectionFactory $subscriptionOrderCollectionFactory,
        Cart $cart,
        SubscriptionHelper $subscriptionHelper,
        FormKey $formKey,
        CheckoutSession $checkoutSession,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);

        $this->_subscriptionOrderCollectionFactory = $subscriptionOrderCollectionFactory;
        $this->_cart = $cart;
        $this->_subscriptionHelper = $subscriptionHelper;
        $this->_formKey = $formKey;
        $this->_checkoutSession = $checkoutSession;
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
        foreach ( $this->getSubscriptionOrders() as $subscriptionOrder ) {

            /**
             * @var SubscriptionOrder $subscriptionOrder
             */
            $subscriptionOrder->generateShipDates();
        }
    }

    /**
     * @return bool|false|string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function addSubscriptionToCart()
    {

        // Make sure we have an actual subscription
        if (is_null($this->getEntityId())) {
            return false;
        }

        // Before starting to add new products, let's clear customer's cart
        $this->_cart->truncate();
        $this->_cart->getQuote()->delete();

        if ($this->getSubscriptionType() == 'annual') {
            $this->addSubscriptionToCartAnnual();
            $this->addAnnualDiscount();
        } else {
            $this->addSubscriptionToCartSeasonal();
        }
    }

    /**
     * Add Subscription to cart for Annual Subscriptions
     *
     * If a subscription is of type annual, we will add all of the items to the cart for consideration.
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function addSubscriptionToCartAnnual() {

        /** @var SubscriptionOrder $subscriptionOrder */
        foreach ( $this->getSubscriptionOrders() as $subscriptionOrder ) {

            /** @var SubscriptionOrderItem $subscriptionOrderItem */
            foreach ( $subscriptionOrder->getSubscriptionOrderItems() as $subscriptionOrderItem ) {

                /** @var Product $product */
                $product = $subscriptionOrderItem->getProduct();
                $params = [
                    'form_key'  => $this->_formKey->getFormKey(),
                    'qty'       => $subscriptionOrderItem->getQty(),
                ];
                $this->_cart->addProduct( $product->getEntityId(), $params );
            }
        }

        $this->_cart->save();
    }

    /**
     * Add Subscription to Cart for Seasonal Subscriptions
     *
     * If the subscription type is seasonal, we will add only the first seasonal subscriptions items, and only if the
     * shipment date is today or before.
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function addSubscriptionToCartSeasonal() {

        /** @var Interceptor $subscriptionOrders */
        $subscriptionOrders = $this->getSubscriptionOrders();
        /** @var SubscriptionOrder $firstSubscriptionOrder */
        $firstSubscriptionOrder = $subscriptionOrders->fetchItem();
        $shipmentDate = new \DateTime( date( $firstSubscriptionOrder->getShipStartDate() ) );
        $todayDate = new \DateTime(date('Y-m-d 00:00:00'));

        // Add items to cart for the first shipment if it's today or in the past
        if ( $todayDate >= $shipmentDate ) {

            /** @var SubscriptionOrderItem $subscriptionOrderItem */
            foreach ( $firstSubscriptionOrder->getSubscriptionOrderItems() as $subscriptionOrderItem ) {

                /** @var Product $product */
                $product = $subscriptionOrderItem->getProduct();
                $params = [
                    'form_key'  => $this->_formKey->getFormKey(),
                    'qty'       => $subscriptionOrderItem->getQty(),
                ];
                $this->_cart->addProduct( $product->getEntityId(), $params );
            }
        }

        $this->_cart->save();
    }

    /**
     * Add the annual discount to only the items currently in the cart
     */
    public function addAnnualDiscount() {
        $this->_checkoutSession->getQuote()->setCouponCode('annual_discount')->collectTotals()->save();
    }


        // Go through all selected AddOn Products and add them to the cart
//        foreach ($addons as $addon) {
//            try {
//                $_product = $this->_productRepository->get($addon);
//                $productId = $_product->getId();
//                $params = [
//                    'form_key'  => $this->_formKey->getFormKey(),
//                    'qty'       => 1,
//                ];
//                $this->_cart->addProduct($productId, $params);
//            } catch (Exception $e) {
//                $response = [ 'success' => false, 'code' => $e->getCode(), 'message' => $e->getMessage()];
//                return json_encode($response);
//            }
//        }

//            // Apply discount code for all annual subscriptions
//            if ($subscription_plan == 'annual') {
//                $this->_checkoutSession->getQuote()->setCouponCode('annual_discount')->collectTotals()->save();
//            }
//
//            // Save cart
//            $this->_cart->save();
//    }
//
//    public function addAddon() {
//
//        // Make sure we have an actual subscription
//        if ( is_null( $this->getEntityId() ) ) {
//            return false;
//        }
//
//        // Go through the cart items and modify their prices for the current customer order
//        $quoteItems = $this->_checkoutSession->getQuote()->getItemsCollection();
//        foreach ($quoteItems as $item) {
//            // Apply the total price from the core products to the annual subscription product
//            if ($subscription_plan == 'annual') {
//                if ($item->getSku() == 'annual') {
//                    $item->setCustomPrice($totalSubscriptionPrice);
//                    $item->setOriginalCustomPrice($totalSubscriptionPrice);
//                    $item->getProduct()->setIsSuperMode(true);
//                }
//            } else {
//                $seasonalSkus = [ 'early-spring', 'late-spring', 'early-summer', 'early-fall' ];
//                if (in_array($item->getSku(), $seasonalSkus)) {
//                    $item->setCustomPrice($totalSubscriptionPrice);
//                    $item->setOriginalCustomPrice($totalSubscriptionPrice);
//                    $item->getProduct()->setIsSuperMode(true);
//                }
//            }
//        }
//
//        // Update Cart
//        $this->_cart->save();
//    }

}