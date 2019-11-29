<?php

namespace SMG\SubscriptionApi\Model;

use Magento\Framework\Exception\SecurityViolationException;
use SMG\SubscriptionApi\Api\SubscriptionInterface;

class Subscription implements SubscriptionInterface
{

    /**
     * @var /SMG/Api/Helper/QuizHelper
     */
    protected $_helper;
    protected $_customerSession;
    protected $_formKey;
    protected $_cart;
    protected $_product;
    protected $_productRepository;
    protected $_resultJsonFactory;
    protected $_checkoutSession;
    protected $_storeManager;
    protected $_cartRepositoryInterface;
    protected $_cartManagementInterface;
    protected $_customerFactory;
    protected $_customerRepository;
    protected $_order;

    public function __construct(
        \SMG\RecommendationApi\Helper\RecommendationHelper $helper,
        \Magento\Checkout\Model\Session $customerSession,
        \Magento\Framework\Data\Form\FormKey $formKey,
        \Magento\Checkout\Model\Cart $cart,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Catalog\Model\Product $product,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Quote\Api\CartRepositoryInterface $cartRepositoryInterface,
        \Magento\Quote\Api\CartManagementInterface $cartManagementInterface,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\Sales\Model\Order $order,
        \Magento\Directory\Model\AllowedCountries $allowedCountries
    ) {
        $this->_helper = $helper;
        $this->_customerSession = $customerSession;
        $this->_formKey = $formKey;
        $this->_cart = $cart;
        $this->_checkoutSession = $checkoutSession;
        $this->_product = $product;
        $this->_productRepository = $productRepository;
        $this->_storeManager = $storeManager;
        $this->_cartRepositoryInterface = $cartRepositoryInterface;
        $this->_cartManagementInterface = $cartManagementInterface;
        $this->_customerFactory = $customerFactory;
        $this->_customerRepository = $customerRepository;
        $this->_order = $order;
        $this->_allowedCountries = $allowedCountries;
    }

    /**
     * Process quiz data, build order object and send customer to checkout. Note that we are hijacking the cart for
     * the addition of subscriptions and to make the display easier.
     * @todo Wes this needs to be refactored. We should be able to just add all of the orders for any
     *
     * @param string $key
     * @param string $subscription_plan
     * @param mixed $data
     * @param mixed $addons
     * @return array|false|string
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     *
     * @api
     */
    public function addSubscriptionToCart($key, $subscription_plan, $data, $addons)
    {

        // Test the form key
        if ($this->formValidation($key)) {
            throw new SecurityViolationException(__('Unauthorized'));
        }

        // Before starting to add new products, let's clear customer's cart
        $quoteItems = $this->_checkoutSession->getQuote()->getItemsCollection();
        foreach( $quoteItems as $item ) {
            $this->_cart->removeItem($item->getId())->save();
        }

        // We will have to calculate the price differently for the subscription than we normally would
        $totalSubscriptionPrice = 0;

        // Add "Annual Subscription" product if the customer selected the annual subscription plan
        if( $subscription_plan == 'annual' ) {
            try {
                $_product = $this->_productRepository->get( 'annual' );
                $productId = $_product->getId();
                $params = array(
                    'form_key'  => $this->_formKey->getFormKey(),
                    'qty'       => 1,
                );
                $this->_cart->addProduct( $productId, $params );
            } catch( Exception $e ) {
                $response = array( 'success' => false, 'code' => $e->getCode(), 'message' => $e->getMessage());
                return json_encode( $response );
            }
        }

        // Go through all the core products, add them to cart and calculate
        // the total subscription price which will be applied to the Annual Subscription product
        if( ! empty( $data['plan']['coreProducts'] ) ) {
            
            $coreProducts = $data['plan']['coreProducts'];
            $firstApplicationStartDate = $coreProducts[0]['applicationStartDate'];

            foreach( $coreProducts as $product ) {
                
                try {
                    $_product = $this->_productRepository->get( $product['sku'] );
                    $totalSubscriptionPrice += $_product->getPrice();
                    $seasonalProductSku = $this->getSeasonalProductSku( $product['season'] );
                    $seasonalProduct = $this->_productRepository->get( $seasonalProductSku );
                    $params = array(
                        'form_key'  => $this->_formKey->getFormKey(),
                        'qty'       => 1,
                    );
                    $this->_cart->addProduct( $seasonalProduct->getId(), $params );
                
                    // If Seasonal subscription, add only the first core product
                    if( $subscription_plan == 'seasonal' ) {
                        break;
                    }
                } catch( Exception $e ) {
                    $response = array( 'success' => false, 'code' => $e->getCode(), 'message' => $e->getMessage());
                    return json_encode( $response );
                }
            }
        }

        // Go through all selected AddOn Products and add them to the cart
        foreach( $addons as $addon ) {
            try {
                $_product = $this->_productRepository->get( $addon );
                $productId = $_product->getId();
                $params = array(
                    'form_key'  => $this->_formKey->getFormKey(),
                    'qty'       => 1,
                );
                $this->_cart->addProduct( $productId, $params );
            } catch( Exception $e ) {
                $response = array( 'success' => false, 'code' => $e->getCode(), 'message' => $e->getMessage());
                return json_encode( $response );
            }
        }

        // Apply discount code for all annual subscriptions
        if( $subscription_plan == 'annual' ) {
            $this->_checkoutSession->getQuote()->setCouponCode('annual_discount')->collectTotals()->save();
        }

        // Save cart
        $this->_cart->save();

        // Go through the cart items and modify their prices for the current customer order
        $quoteItems = $this->_checkoutSession->getQuote()->getItemsCollection();
        foreach( $quoteItems as $item ) {
            // Apply the total price from the core products to the annual subscription product
            if( $subscription_plan == 'annual' ) {
                if( $item->getSku() == 'annual' ) {
                    $item->setCustomPrice($totalSubscriptionPrice);
                    $item->setOriginalCustomPrice($totalSubscriptionPrice);
                    $item->getProduct()->setIsSuperMode(true);
                }
            } else {
                $seasonalSkus = array( 'early-spring', 'late-spring', 'early-summer', 'early-fall' );
                if( in_array( $item->getSku(), $seasonalSkus ) ) {
                    $item->setCustomPrice($totalSubscriptionPrice);
                    $item->setOriginalCustomPrice($totalSubscriptionPrice);
                    $item->getProduct()->setIsSuperMode(true);
                }
            }
        }

        // Update Cart
        $this->_cart->save();

        foreach ( $this->_cart->getItems() as $item ) {
            $items[] = $item->getName() . " " . $item->getSku() . " qty: " . $item->getQty() . " addon: " . (String)$item->getAddon() . " price: " .  $item->getPrice();
        }

        $response = array( 'success' => true, 'estimated_arrival' => $this->getEstimatedArrivalDate($firstApplicationStartDate) );

        return json_encode( $response );
    }

    /**
     * Process cart products and create multiple orders
     * 
     * @param string $key
     * @return array|false|string
     * 
     * @api
     */
    public function createOrders($key) {
        // Get all items in the cart
        $quoteItems = $this->_checkoutSession->getQuote()->getItemsCollection();

        $seasonalSkus = array( 'early-spring', 'late-spring', 'early-summer', 'early-fall', 'annual' );
        $seasonalOrderData = array();
        $addonOrderData = array();
        $seasonal_counter = 0;
        $addon_counter = 0;

        // Separate seasonal with addon products and remove them from the current cart,
        foreach( $quoteItems as $item ) {
            if( in_array( $item->getSku(), $seasonalSkus) ) {
                $seasonalOrderData[$seasonal_counter]['sku'] = $item->getSku();
                $seasonalOrderData[$seasonal_counter]['price'] = $item->getPrice();
                $seasonalOrderData[$seasonal_counter]['id'] = $item->getId();
                $seasonal_counter++;
            } else {
                $addonOrderData[$addon_counter]['sku'] = $item->getSku();
                $addonOrderData[$addon_counter]['price'] = $item->getPrice();
                $addonOrderData[$addon_counter]['id'] = $item->getId();
                $addon_counter++;
            }

            // Remove item from the quote, because there will be duplicate orders created
            $this->_cart->removeItem($item->getId())->save();
        }

        // Get store and website information
        $store = $this->_storeManager->getStore();
        $websiteId = $this->_storeManager->getStore()->getWebsiteId();

        // Get customer
        $customer = $this->_customerFactory->create();
        $customer->setWebsiteId($websiteId);
        $customer->loadByEmail( $this->_checkoutSession->getQuote()->getCustomerEmail() );
        $customerId = $customer->getId();
        $customer = $this->_customerRepository->getById( $customerId );

        // Go through the seasonal products
        foreach( $seasonalOrderData as $item ) {
            // Create empty cart for every seasonal product
            $cartId = $this->_cartManagementInterface->createEmptyCartForCustomer($customerId);
            $quote = $this->_cartRepositoryInterface->get($cartId);
            $quote->setStore($store);
            $quote->setCurrency();
            $quote->assignCustomer($customer);

            // Add product to the cart
            $_product = $this->_productRepository->get( $item['sku'] );
            $product = $this->_product->load( $_product->getId() );
            $product->setPrice($item['price']);
            $quote->addProduct( $product, 1 );

            // Set shipping information
            $shippingAddress = $quote->getShippingAddress();
            $shippingAddress->setCollectShippingRates(true)->collectShippingRates()->setShippingMethod('freeshipping_freeshipping');

            // Don't process inventory on seasonal products
            $quote->setInventoryProcessed(false);

            // Set payment information
            $quote->setPaymentMethod('recurly');
            $quote->getPayment()->importData(['method' => 'recurly']);

            // Save quote
            $quote->save();
     
            // Collect totals
            $quote->collectTotals();

            // Create order from the quote
            $quote = $this->_cartRepositoryInterface->get($quote->getId());
            $orderId = $this->_cartManagementInterface->placeOrder($quote->getId());
            $order = $this->_order->load($orderId);
            $order->setEmailSent(0);
            $increment_id = $order->getRealOrderId();
        }

        // Create cart for the addons
        $addonCartId = $this->_cartManagementInterface->createEmptyCartForCustomer( $customerId );
        $addonQuote = $this->_cartRepositoryInterface->get( $addonCartId );
        $addonQuote->setStore( $store );
        $addonQuote->setCurrency();
        $addonQuote->assignCustomer( $customer );

        // Go through the addon products
        foreach( $addonOrderData as $addon ) {
            // Create cart for the addon
            $addonCartId = $this->_cartManagementInterface->createEmptyCartForCustomer( $customerId );
            $addonQuote = $this->_cartRepositoryInterface->get( $addonCartId );
            $addonQuote->setStore( $store );
            $addonQuote->setCurrency();
            $addonQuote->assignCustomer( $customer );

            // Add addon products to the cart
            $_product = $this->_productRepository->get( $addon['sku'] );
            $product = $this->_product->load( $_product->getId() );
            $product->setPrice( $addon['price'] );
            $addonQuote->addProduct( $product, 1 );
            // Set shipping address for the cart
            $shippingAddress = $addonQuote->getShippingAddress();
            $shippingAddress->setCollectShippingRates(true)->collectShippingRates()->setShippingMethod('freeshipping_freeshipping');
            
            // Update inventory for the addon products
            $addonQuote->setInventoryProcessed(true);

            // Set payment method
            $addonQuote->setPaymentMethod('recurly');
            $addonQuote->getPayment()->importData( [ 'method' => 'recurly' ] );

            // Save quote
            $addonQuote->save();

            // Collect totals
            $addonQuote->collectTotals();
        }

        // Create order
        $addonQuote = $this->_cartRepositoryInterface->get( $addonQuote->getId() );
        $addonOrderId = $this->_cartManagementInterface->placeOrder( $addonQuote->getId() );       
        $addonOrder = $this->_order->load( $addonOrderId );
        $addonOrder->setEmailSent(0);
        $increment_id = $addonOrder->getRealOrderId();

        return array( 'success' => true, 'message' => 'Magento orders created' );
    }

    /**
     * Calculate estimated arrival date
     * 
     * @param DateTime $start_date
     * @return DateTime
     */
    private function getEstimatedArrivalDate($start_date)
    {
        $applicationStartDate = new \DateTime($start_date);
        $applicationStartDate->sub(new \DateInterval('P9D'));
        $todayDate = new \DateTime(date('Y-m-d 00:00:00'));

        return ( $todayDate <= $applicationStartDate ) ? $applicationStartDate->format('m/d/Y') : $todayDate->format('m/d/Y');
    }

    /**
     * Return SKU code for the product based on the season name
     * 
     * @param string $season_name
     * @return string
     */
    private function getSeasonalProductSku($season_name)
    {
        switch($season_name) {
            case 'Early Spring Feeding':
                return 'early-spring';
            case 'Late Spring Feeding':
                return 'late-spring';
            case 'Early Summer Feeding':
                return 'early-summer';
            case 'Early Fall Feeding':
                return 'early-fall';
            default:
                return '';
        }
    }

    /**
     * Test the form key for CSRF form validation
     *
     * @param $key
     * @return bool
     */
    public function formValidation($key) {
        return $this->_formKey->getFormKey() !== $key;
    }

}
