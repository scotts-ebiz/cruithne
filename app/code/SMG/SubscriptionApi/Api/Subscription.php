<?php

namespace SMG\SubscriptionApi\Api;

use Magento\Framework\Exception\SecurityViolationException;
use Magento\Setup\Exception;
use Recurly_Client;
use Recurly_SubscriptionList;
use SMG\SubscriptionApi\Api\Interfaces\SubscriptionInterface;

/**
 * Class Subscription
 * @package SMG\SubscriptionApi\Api
 */
class Subscription implements SubscriptionInterface
{

    /** @var \SMG\RecommendationApi\Helper\RecommendationHelper */
    protected $_recommendationHelper;

    /** @var \SMG\SubscriptionApi\Helper\RecurlyHelper */
    protected $_recurlyHelper;

    /** @var \SMG\SubscriptionApi\Helper\SubscriptionHelper */
    protected $_subscriptionHelper;

    /** @var \Magento\Checkout\Model\Session */
    protected $_customerSession;

    /** @var \Magento\Framework\Data\Form\FormKey */
    protected $_formKey;

    /** @var \Magento\Checkout\Model\Cart */
    protected $_cart;

    /** @var \Magento\Catalog\Model\Product */
    protected $_product;

    /** @var \Magento\Catalog\Api\ProductRepositoryInterface */
    protected $_productRepository;

    /** @var \Magento\Checkout\Model\Session */
    protected $_checkoutSession;

    /** @var \Magento\Store\Model\StoreManagerInterface */
    protected $_storeManager;

    /** @var \Magento\Quote\Api\CartRepositoryInterface */
    protected $_cartRepositoryInterface;

    /** @var \Magento\Quote\Api\CartManagementInterface */
    protected $_cartManagementInterface;

    /** @var \Magento\Customer\Model\CustomerFactory */
    protected $_customerFactory;

    /** @var \Magento\Customer\Api\CustomerRepositoryInterface */
    protected $_customerRepository;

    /** @var \Magento\Sales\Model\Order */
    protected $_order;

    /** @var \Magento\Customer\Api\AddressRepositoryInterface */
    protected $_addressRepository;

    /**  @var \Magento\Customer\Api\Data\AddressInterfaceFactory */
    protected $_dataAddressFactory;

    /**
     * @var \Magento\Customer\Model\Address
     */
    protected $_customerAddress;

    /**  @var \SMG\SubscriptionApi\Model\ResourceModel\Subscription */
    protected $_subscription;

    /** @var \Magento\Framework\Session\SessionManagerInterface */
    protected $_coreSession;

    /**
     * Subscription constructor.
     * @param \SMG\RecommendationApi\Helper\RecommendationHelper $recommendationHelper
     * @param \SMG\SubscriptionApi\Helper\RecurlyHelper $recurlyHelper
     * @param \SMG\SubscriptionApi\Helper\SubscriptionHelper $subscriptionHelper
     * @param \Magento\Checkout\Model\Session $customerSession
     * @param \Magento\Framework\Data\Form\FormKey $formKey
     * @param \Magento\Checkout\Model\Cart $cart
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Catalog\Model\Product $product
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Quote\Api\CartRepositoryInterface $cartRepositoryInterface
     * @param \Magento\Quote\Api\CartManagementInterface $cartManagementInterface
     * @param \Magento\Customer\Model\CustomerFactory $customerFactory
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     * @param \Magento\Sales\Model\Order $order
     * @param \Magento\Customer\Api\AddressRepositoryInterface $addressRepository
     * @param \Magento\Customer\Api\Data\AddressInterfaceFactory $dataAddressFactory
     * @param \Magento\Customer\Model\Address $customerAddress
     * @param \SMG\SubscriptionApi\Model\ResourceModel\Subscription $subscription
     */
    public function __construct(
        \SMG\RecommendationApi\Helper\RecommendationHelper $recommendationHelper,
        \SMG\SubscriptionApi\Helper\RecurlyHelper $recurlyHelper,
        \SMG\SubscriptionApi\Helper\SubscriptionHelper $subscriptionHelper,
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
        \Magento\Customer\Api\AddressRepositoryInterface $addressRepository,
        \Magento\Customer\Api\Data\AddressInterfaceFactory $dataAddressFactory,
        \Magento\Customer\Model\Address $customerAddress,
        \SMG\SubscriptionApi\Model\ResourceModel\Subscription $subscription,
        \Magento\Framework\Session\SessionManagerInterface $coreSession
    ) {
        $this->_recommendationHelper = $recommendationHelper;
        $this->_recurlyHelper = $recurlyHelper;
        $this->_subscriptionHelper = $subscriptionHelper;
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
        $this->_subscription = $subscription;
        $this->_coreSession = $coreSession;
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
    public function addSubscriptionToCart($key, $subscription_plan, $data, $addons = [])
    {

        // Test the form key
        if (! $this->formValidation($key)) {
            throw new SecurityViolationException(__('Unauthorized'));
        }

        // Add subscription to cart
        try {
            /** @var \SMG\SubscriptionApi\Model\Subscription $subscription */
            $subscription = $this->_subscription->getSubscriptionByQuizId( $this->_coreSession->getQuizId() );
            $subscription->setSubscriptionType( $subscription_plan )->save();
            $subscription->generateShipDates();
            $subscription->addSubscriptionToCart( $addons );
        } catch ( Exception $e ) {
            $response = array( 'success' => false, 'message' => $e->getMessage() );
            return json_encode($response);
        }

        $response = array( 'success' => true );
        return json_encode($response);
    }

    /**
     * Process cart products and create multiple orders
     *
     * @param string $key
     * @param string $quiz_id
     * @param mixed $billing_address
     * @return array|false|string
     *
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @api
     */
    public function createOrders($key, $quiz_id, $billing_address) {

        // Get store and website information
        $store = $this->_storeManager->getStore();
        $websiteId = $this->_storeManager->getStore()->getWebsiteId();

        // Get customer
        $customer = $this->_customerFactory->create();
        $customer->setWebsiteId($websiteId);
        $customer->loadByEmail($this->_checkoutSession->getQuote()->getCustomerEmail());
        $customerData = $customer->getData();
        $customerGigyaId = $customerData['gigya_uid'];

        // Get customer's current subscriptions
        $recurlySubscriptions = $this->getAccountSubscriptions($customer->getRecurlyAccountCode(), $quiz_id);

        // Get all items in the cart
        $mainQuote = $this->_checkoutSession->getQuote();
        $quoteItems = $mainQuote->getItemsCollection();

        // Remove items from the quote, because there will be duplicate orders create

        foreach( $quoteItems as $item ) {
            $this->_cart->removeItem($item->getItemId())->save();
        }

        // Get customer
        $customerId = $customer->getId();
        $customer = $this->_customerRepository->getById($customerId);

        // Get customer shipping and billing address
        $orderShippingAddress = $mainQuote->getShippingAddress()->getData();
        $orderBillingAddress = $billing_address;
        
        // Get seasonal products
        $completedQuizUrl = filter_var(
            trim(
                str_replace('{completedQuizId}', $quiz_id, $this->_recommendationHelper->getQuizResultApiPath()),
                '/'
            ),
            FILTER_SANITIZE_URL
        );

        // Get quiz results data
        $completedQuizResult = $this->_recommendationHelper->request($completedQuizUrl, '', 'GET' );

        // Get seasonal products
        $seasonalProducts = $completedQuizResult['plan']['coreProducts'];

        if( ! empty( $seasonalProducts ) ) {
            // Go through the seasonal products
            foreach( $seasonalProducts as $item ) {
                // Create empty cart for every seasonal product
                $cartId = $this->_cartManagementInterface->createEmptyCartForCustomer($customerId);
                $quote = $this->_cartRepositoryInterface->get($cartId);
                $quote->setStore($store);
                $quote->setCurrency();
                $quote->assignCustomer($customer);

                // Add product to the cart
                $product = $this->_productRepository->get( $item['sku'] );
                $product = $this->_product->load( $product->getId() );
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

                // Collect totals
                $quote->collectTotals();

                // Save quote
                $quote->save();
         
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
                $seasonCode = $this->_recurlyHelper->getSeasonSlugByName( $item['season'] );

                // Set subscription id
                $order->setSubscriptionId( $recurlySubscriptions[$seasonCode]['subscription_id'] );

                // Set ship date for the subscription/order
                $order->setShipDate( $recurlySubscriptions[$seasonCode]['starts_at'] );

                // Set is addon subscription flag
                $order->setSubscriptionAddon(false);

                // Save order
                $order->save();
            }
        }

        // Get addon products
        $addOnProducts = $completedQuizResult['plan']['addOnProducts'];

        if (! empty($addOnProducts)) {
            // Create cart for the addons
            $addonCartId = $this->_cartManagementInterface->createEmptyCartForCustomer($customerId);
            $addonQuote = $this->_cartRepositoryInterface->get($addonCartId);
            $addonQuote->setStore($store);
            $addonQuote->setCurrency();
            $addonQuote->assignCustomer($customer);

            // Go through the addon products
            foreach ($addOnProducts as $addon) {
                // Create cart for the addon
                $addonCartId = $this->_cartManagementInterface->createEmptyCartForCustomer($customerId);
                $addonQuote = $this->_cartRepositoryInterface->get($addonCartId);
                $addonQuote->setStore($store);
                $addonQuote->setCurrency();
                $addonQuote->assignCustomer($customer);

                // Add addon products to the cart
                $product = $this->_productRepository->get( $addon['sku'] );
                $product = $this->_product->load( $product->getId() );
                $addonQuote->addProduct( $product, 1 );

                // Collect totals
                $addonQuote->collectTotals();

                // Save quote
                $addonQuote->save();
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
            $addonQuote->getPayment()->importData([ 'method' => 'recurly' ]);

            // Create order
            $addonQuote = $this->_cartRepositoryInterface->get($addonQuote->getId());
            $addonOrderId = $this->_cartManagementInterface->placeOrder($addonQuote->getId());
            $addonOrder = $this->_order->load($addonOrderId);
            $addonOrder->setEmailSent(0);

            // Set customer gigya id
            $addonOrder->setGigyaId($customerGigyaId);

            // Set master subscription id based on the recurly subscription plan code
            if (isset($recurlySubscriptions['annual']['subscription_id'])) {
                $order->setMasterSubscriptionId($recurlySubscriptions['annual']['subscription_id']);
            }
            if (isset($recurlySubscriptions['seasonal']['subscription_id'])) {
                $order->setMasterSubscriptionId($recurlySubscriptions['seasonal']['subscription_id']);
            }

            // Set subscription id
            $addonOrder->setSubscriptionId($recurlySubscriptions['add-ons']['subscription_id']);

            // Set ship date
            $addonOrder->setShipDate($recurlySubscriptions['add-ons']['starts_at']);

            // Set is addon subscription flag
            $addonOrder->setSubscriptionAddon(true);

            // Save order
            $addonOrder->save();
        }

        // Delete customer addresses, because we don't want to store them in the address book,
        // so they will always need to enter their shipping/billing details on checkout
        foreach( $customer->getAddresses() as $adr ) {
            $this->_addressRepository->deleteById( $adr->getId() );
        }

        return array( 'success' => true, 'message' => 'Magento orders created' );
    }

    /**
     * Return all customer's subscriptions
     *
     * @param string $account_code
     * @return array
     */
    private function getAccountSubscriptions($account_code, $quiz_id)
    {
        Recurly_Client::$apiKey = $this->_recurlyHelper->getRecurlyPrivateApiKey();
        Recurly_Client::$subdomain = $this->_recurlyHelper->getRecurlySubdomain();

        $activeSubscriptions = [];

        try {
            $subscriptions = Recurly_SubscriptionList::getForAccount($account_code, ['state' => 'live']);
            foreach ($subscriptions as $subscription) {
                // If subscription quiz_id is the same as the current quiz_id
                if (isset($subscription->custom_fields['quiz_id'])) {
                    if ($quiz_id == $subscription->custom_fields['quiz_id']->value) {
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
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function formValidation($key)
    {
        if ($this->_subscriptionHelper->useCsrf($this->_storeManager->getStore()->getId())) {
            return $this->_formKey->getFormKey() === $key;
        }

        return true;
    }
}
