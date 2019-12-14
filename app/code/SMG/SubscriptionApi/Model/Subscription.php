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
use Magento\Setup\Exception;
use Magento\Store\Model\StoreManager;
use SMG\SubscriptionApi\Helper\SubscriptionHelper;
use SMG\SubscriptionApi\Model\ResourceModel\SubscriptionOrder\Collection as SubscriptionOrderCollection;
use SMG\SubscriptionApi\Model\ResourceModel\SubscriptionAddonOrder\Collection as SubscriptionAddonOrderCollection;
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
     * @return SubscriptionOrderCollection|mixed
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
     * @return SubscriptionAddonOrderCollection|mixed
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

    /**
     * @param $addons
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function addSubscriptionToCart( $addons ) {

        try {
            $quote = $this->_checkoutSession->getQuote();
            $quoteItems = $quote->getItemsCollection();
            foreach( $quoteItems as $item ) {
                $this->_cart->removeItem( $item->getItemId() );
            }

        } catch ( \Exception $e ) {
            throw new \Exception("Oops 1: " . $e->getMessage() );
        }

        // We will have to calculate the price differently for the subscription than we normally would
        $totalSubscriptionPrice = 0;

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

            // Get the first seasonal product or annual depending on type
            $subscriptionOrders = $this->getSubscriptionOrders();
            $planSku = 'annual';
            if ($this->getSubscriptionType() !== 'annual') {
                $firstSeason = $subscriptionOrders->getFirstItem();
                $planSku = $this->getPlanCodeByName( $firstSeason->getSeasonName() );
            }

            $seasonalProduct = $this->_productRepository->get( $planSku );
            $params = [
                'form_key'  => $this->_formKey->getFormKey(),
                'qty'       => 1
            ];
            $this->_cart->addProduct($seasonalProduct->getId(), $params);

            // Add the discount if it's annual
            if ( $this->getSubscriptionType() == 'annual') {
                $this->_cart->getQuote()->setCouponCode('annual_discount')->collectTotals()->save();
            }

        } catch ( \Exception $e ) {
            throw new \Exception("Oops 2: " . $e->getMessage() );
        }

        try {
            // Go through all selected AddOn Products and add them to the cart
            foreach ($this->getSubscriptionAddonOrders() as $subscriptionAddonOrder) {

                foreach ($subscriptionAddonOrder->getSubscriptionAddonOrderItems() as $subscriptionAddonOrderItem) {

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

                            $price = (float)$product->getPrice() * (int)$subscriptionAddonOrderItem->getQty();
                            $item = $quote->getItemByProduct($product);
                            $item->setCustomPrice((float)$price);
                            $item->setOriginalCustomPrice((float)$price);
                            $item->getProduct()->setIsSuperMode(true);

                        } catch (\Exception $e) {
                            throw new \Exception("Oops 3: " . $e->getMessage());
                        }

                        $subscriptionAddonOrderItem->setSelected(1);
                    } else {
                        $subscriptionAddonOrderItem->setSelected(0);
                    }
                    $subscriptionAddonOrderItem->save();
                }
            }
        } catch (\Exception $e) {
            throw new \Exception("Oops Subscr: " . $e->getMessage());
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

        } catch ( \Exception $e ) {
            throw new \Exception("Oops 4: " . $e->getMessage() );
        }

//        $items = $quote->getItems();
//        foreach ( (Array)$items as $item) {
//            var_dump([
//                'currently_shippable' => $this->currentlyShippable(),
//                'product_name' => $item->getName(),
//                'product_sku' => $item->getSku(),
//                'product_qty' => $item->getQty(),
//                'product_custom_price' => $item->getCustomPrice(),
//                'product_price' => $item->getPrice(),
//                'product_base_price' => $item->getBasePrice()
//            ]);
//        }
//
//        var_dump([
//            'subtotal' => $quote->getSubtotal(),
//            'subtotal_with_discount' => $quote->getSubtotalWithDiscount(),
//            'grand_total' => $quote->getGrandTotal(),
//            'items_count' => $quote->getItemsCount(),
//            'coupon_code' => $quote->getCouponCode()
//        ]);
//        die;
    }

    /**
     * @return bool
     * @throws \Exception
     */
    public function isCurrentlyShippable() {
        if ( $this->getSubscriptionType() !== 'annual' ) {

            // Test of seasonal is not shippable
            $today = new \DateTime();
            return $today >= $this->getFirstSubscriptionOrder()->getShipStartDate();
        }
        return true;
    }

    /**
     * Return the first subscription order
     * @return SubscriptionOrder|mixed
     */
    public function getFirstSubscriptionOrder() {
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
        $addOnOrders = $this->getSubscriptionOrders();

        if (! $addOnOrders) {
            return false;
        }

        $order = $addOnOrders->getFirstItem();

        if (! $order) {
            return false;
        }

        $items = $order->getSubscriptionOrderItems();

        if (! $items) {
            return false;
        }

        $addOn = $items->getFirstItem();

        return $addOn ? $addOn->getProduct() : false;
    }

    /**
     * Get Plan Code by Name
     * @param $name
     * @return string
     */
    private function getPlanCodeByName($name) {
        switch($name) {
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

}
