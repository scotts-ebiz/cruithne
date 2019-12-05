<?php

namespace SMG\SubscriptionApi\Model;

use Magento\Framework\Exception\SecurityViolationException;
use SMG\SubscriptionApi\Api\SubscriptionInterface;
use Recurly_Client;
use Recurly_SubscriptionList;

class Subscription implements SubscriptionInterface
{

    /**
     * @var /SMG/Api/Helper/QuizHelper
     */
    protected $_helper;
    protected $_recommendationHelper;
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
    protected $_recurlyHelper;
    protected $_addressRepository;
    protected $_dataAddressFactory;
    protected $_customerAddress;

    public function __construct(
        \SMG\RecommendationApi\Helper\RecommendationHelper $helper,
        \SMG\RecommendationApi\Helper\RecommendationHelper $recommendationHelper,
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
        \SMG\SubscriptionApi\Helper\RecurlyHelper $recurlyHelper,
        \Magento\Customer\Api\AddressRepositoryInterface $addressRepository,
        \Magento\Customer\Api\Data\AddressInterfaceFactory $dataAddressFactory,
        \Magento\Customer\Model\Address $customerAddress
    ) {
        $this->_helper = $helper;
        $this->_recommendationHelper = $recommendationHelper;
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
        $this->_recurlyHelper = $recurlyHelper;
        $this->_addressRepository = $addressRepository;
        $this->_dataAddressFactory = $dataAddressFactory;
        $this->_customerAddress = $customerAddress;
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

        $response = array( 'success' => true );

        return json_encode( $response );
    }

    /**
     * Process cart products and create multiple orders
     * 
     * @param string $key
     * @param string $quiz_id
     * @param mixed $billing_address
     * @return array|false|string
     * 
     * @api
     */
    public function createOrders($key, $quiz_id, $billing_address) {
        // Get store and website information
        $store = $this->_storeManager->getStore();
        $websiteId = $this->_storeManager->getStore()->getWebsiteId();

        // Get customer
        $customer = $this->_customerFactory->create();
        $customer->setWebsiteId($websiteId);
        $customer->loadByEmail( $this->_checkoutSession->getQuote()->getCustomerEmail() );
        $customerData = $customer->getData();
        $customerGigyaId = $customerData['gigya_uid'];

        // Get customer's current subscriptions
        $recurlySubscriptions = $this->getAccountSubscriptions( $customer->getRecurlyAccountCode(), $quiz_id );

        // Get all items in the cart
        $mainQuote = $this->_checkoutSession->getQuote();
        $quoteItems = $mainQuote->getItemsCollection();

        // Remove items from the quote, because there will be duplicate orders create
        foreach( $quoteItems as $item ) {
            $this->_cart->removeItem($item->getId())->save();
        }

        // Get customer
        $customerId = $customer->getId();
        $customer = $this->_customerRepository->getById( $customerId );

        // Get customer shipping and billing address
        $orderShippingAddress = $mainQuote->getShippingAddress()->getData();
        $orderBillingAddress = $billing_address;
        
        // Get seasonal products
        $completedQuizUrl = $url = filter_var(
            trim(
                str_replace('{completedQuizId}', $quiz_id, $this->_recommendationHelper->getQuizResultApiPath()),
                '/'
            ),
            FILTER_SANITIZE_URL
        );

        $completedQuizResult = $this->_recommendationHelper->request($completedQuizUrl, '', 'GET' );
        $seasonalProducts = $completedQuizResult['plan']['coreProducts'];

        // Go through the seasonal products
        foreach( $seasonalProducts as $item ) {
            // Create empty cart for every seasonal product
            $cartId = $this->_cartManagementInterface->createEmptyCartForCustomer($customerId);
            $quote = $this->_cartRepositoryInterface->get($cartId);
            $quote->setStore($store);
            $quote->setCurrency();
            $quote->assignCustomer($customer);

            // Add product to the cart
            $_product = $this->_productRepository->get( $item['sku'] );
            $product = $this->_product->load( $_product->getId() );
            $quote->addProduct( $product, 1 );

            // Set shipping information
            $quote->getShippingAddress()->addData($orderShippingAddress);
            $quote->getBillingAddress()->addData($orderBillingAddress);
            $shippingAddress = $quote->getShippingAddress();
            $shippingAddress->setCollectShippingRates(true)->collectShippingRates()->setShippingMethod('freeshipping_freeshipping');

            // Update inventory of the products
            $quote->setInventoryProcessed(true);

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

            // Set customer gigya id
            $order->setGigyaId( $customerGigyaId );

            // Set master subscription id based on the recurly subscription plan code
            if( isset( $recurlySubscriptions['annual']['subscription_id'] ) ) {
                $order->setMasterSubscriptionId( $recurlySubscriptions['annual']['subscription_id'] );
            }
            if( isset( $recurlySubscriptions['seasonal']['subscription_id'] ) ) {
                $order->setMasterSubscriptionId( $recurlySubscriptions['seasonal']['subscription_id'] );
            }

            // Get Recurly plan code based on the season name, so we can map the subscription id and start date fields
            $seasonCode = $this->getSeasonalProductSku( $item['season'] );

            // Set subscription id
            $order->setSubscriptionId( $recurlySubscriptions[$seasonCode]['subscription_id'] );

            // Set ship date for the subscription/order
            $order->setShipDate( $recurlySubscriptions[$seasonCode]['starts_at'] );

            // Set is addon subscription flag
            $order->setSubscriptionAddon(false);

            // Save order
            $order->save();

            $increment_id = $order->getRealOrderId();
        }

        // Get all addon products from the quiz result
        $addOnProducts = $completedQuizResult['plan']['addOnProducts'];

        if( ! empty( $addOnProducts ) ) {
            // Create cart for the addons
            $addonCartId = $this->_cartManagementInterface->createEmptyCartForCustomer( $customerId );
            $addonQuote = $this->_cartRepositoryInterface->get( $addonCartId );
            $addonQuote->setStore( $store );
            $addonQuote->setCurrency();
            $addonQuote->assignCustomer( $customer );

            // Go through the addon products
            foreach( $addOnProducts as $addon ) {
                // Create cart for the addon
                $addonCartId = $this->_cartManagementInterface->createEmptyCartForCustomer( $customerId );
                $addonQuote = $this->_cartRepositoryInterface->get( $addonCartId );
                $addonQuote->setStore( $store );
                $addonQuote->setCurrency();
                $addonQuote->assignCustomer( $customer );

                // Add addon products to the cart
                $_product = $this->_productRepository->get( $addon['sku'] );
                $product = $this->_product->load( $_product->getId() );
                $addonQuote->addProduct( $product, 1 );

                // Save quote
                $addonQuote->save();

                // Collect totals
                $addonQuote->collectTotals();
            }

            // Set shipping address for the cart
            $addonQuote->getShippingAddress()->addData($orderShippingAddress);
            $addonQuote->getBillingAddress()->addData($orderBillingAddress);
            $shippingAddress = $addonQuote->getShippingAddress();
            $shippingAddress->setCollectShippingRates(true)->collectShippingRates()->setShippingMethod('freeshipping_freeshipping');
            
            // Update inventory for the addon products
            $addonQuote->setInventoryProcessed(true);

            // Set payment method
            $addonQuote->setPaymentMethod('recurly');
            $addonQuote->getPayment()->importData( [ 'method' => 'recurly' ] );

            // Create order
            $addonQuote = $this->_cartRepositoryInterface->get( $addonQuote->getId() );
            $addonOrderId = $this->_cartManagementInterface->placeOrder( $addonQuote->getId() );       
            $addonOrder = $this->_order->load( $addonOrderId );
            $addonOrder->setEmailSent(0);

            // Set customer gigya id
            $addonOrder->setGigyaId( $customerGigyaId );

            // Set master subscription id based on the recurly subscription plan code
            if( isset( $recurlySubscriptions['annual']['subscription_id'] ) ) {
                $order->setMasterSubscriptionId( $recurlySubscriptions['annual']['subscription_id'] );
            }
            if( isset( $recurlySubscriptions['seasonal']['subscription_id'] ) ) {
                $order->setMasterSubscriptionId( $recurlySubscriptions['seasonal']['subscription_id'] );
            }

            // Set subscription id
            $addonOrder->setSubscriptionId($recurlySubscriptions['add-ons']['subscription_id']);

            // Set ship date
            $addonOrder->setShipDate($recurlySubscriptions['add-ons']['starts_at']);

            // Set is addon subscription flag
            $addonOrder->setSubscriptionAddon(true);

            // Save order
            $addonOrder->save();

            $increment_id = $addonOrder->getRealOrderId();
        }

        // Delete customer addresses, because we don't want to store them in the address book,
        // so they will always need to enter their shipping/billing details on checkout
        foreach( $customer->getAddresses() as $adr ) {
            $this->_addressRepository->deleteById( $adr->getId() );
        }

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
     * Return all customer's subscriptions
     * 
     * @param string $account_code
     * @return array
     */
    private function getAccountSubscriptions( $account_code, $quiz_id )
    {
        Recurly_Client::$apiKey = $this->_recurlyHelper->getRecurlyPrivateApiKey();
        Recurly_Client::$subdomain = $this->_recurlyHelper->getRecurlySubdomain();

        $activeSubscriptions = array();

        try {
            $subscriptions = Recurly_SubscriptionList::getForAccount($account_code, ['state' => 'live']);
            foreach ($subscriptions as $subscription) {
                // If subscription quiz_id is the same as the current quiz_id
                if( isset( $subscription->custom_fields['quiz_id'] ) ) {
                    if( $quiz_id == $subscription->custom_fields['quiz_id']->value  ) {
                        $activeSubscriptions[$subscription->plan->plan_code]['subscription_id'] = $subscription->uuid;
                        $activeSubscriptions[$subscription->plan->plan_code]['starts_at'] = $subscription->current_term_started_at;
                    }
                }
            }

            return $activeSubscriptions;
        } catch (Recurly_NotFoundError $e) {
            print "Account Not Found: $e";
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
