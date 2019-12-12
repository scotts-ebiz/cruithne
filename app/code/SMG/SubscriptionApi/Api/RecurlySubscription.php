<?php

namespace SMG\SubscriptionApi\Api;

use Magento\Directory\Model\ResourceModel\Region\CollectionFactory;
use SMG\SubscriptionApi\Api\Interfaces\RecurlyInterface;
use Recurly_Client;
use Recurly_Account;
use Recurly_Adjustment;
use Recurly_BillingInfo;
use Recurly_Coupon;
use Recurly_CustomField;
use Recurly_Error;
use Recurly_NotFoundError;
use Recurly_Purchase;
use Recurly_ShippingAddress;
use Recurly_ShippingAddressList;
use Recurly_Subscription;
use Recurly_SubscriptionList;
use Recurly_ValidationError;

/**
 * Class RecurlySubscription
 * @package SMG\SubscriptionApi\Api
 */
class RecurlySubscription implements RecurlyInterface
{

    /**
     * @var \SMG\SubscriptionApi\Helper\RecurlyHelper
     */
	protected $_recurlyHelper;

    /**
     * @var \SMG\SubscriptionApi\Helper\SubscriptionHelper
     */
	protected $_subscriptionHelper;

    /**
     * @var \SMG\RecommendationApi\Helper\RecommendationHelper
     */
	protected $_recommendationHelper;

    /**
     * @var \Magento\Customer\Model\Session
     */
	protected $_customerSession;

    /**
     * @var \Magento\Customer\Model\Customer
     */
	protected $_customer;

    /**
     * @var \Magento\Customer\Model\ResourceModel\CustomerFactory
     */
	protected $_customerFactory;

    /**
     * @var string
     */
	protected $_couponCode;

    /**
     * @var string
     */
	protected $_currency;

    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
	protected $_productRepository;

    /**
     * @var \Magento\Checkout\Model\Session
     */
	protected $_checkoutSession;

    /**
     * @var CollectionFactory
     */
	protected $_collectionFactory;

    /**
     * @var \Magento\Customer\Model\Url
     */
	protected $_customerUrl;

    /**
     * @var \Magento\Catalog\Model\Product
     */
    protected $_product;

    /**
     * @var \Magento\Catalog\Model\ProductFactory
     */
    protected $_productFactory;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product
     */
    protected $_productResource;

    /**
     * @var \Magento\Framework\Session\SessionManagerInterface
     */
    protected $_coreSession;

    /**
     * RecurlySubscription constructor.
     * @param \SMG\SubscriptionApi\Helper\RecurlyHelper $recurlyHelper
     * @param \SMG\SubscriptionApi\Helper\SubscriptionHelper $subscriptionHelper
     * @param \SMG\RecommendationApi\Helper\RecommendationHelper $recommendationHelper
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Customer\Model\Customer $customer
     * @param \Magento\Customer\Model\ResourceModel\CustomerFactory $customerFactory
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param CollectionFactory $collectionFactory
     * @param \Magento\Customer\Model\Url $customerUrl
     * @param \Magento\Catalog\Model\Product $product
     * @param \Magento\Catalog\Model\ProductFactory $productFactory
     * @param \Magento\Catalog\Model\ResourceModel\Product $productResource
     * @param \Magento\Framework\Session\SessionManagerInterface $coreSession
     */
	public function __construct(
		\SMG\SubscriptionApi\Helper\RecurlyHelper $recurlyHelper,
        \SMG\SubscriptionApi\Helper\SubscriptionHelper $subscriptionHelper,
		\SMG\RecommendationApi\Helper\RecommendationHelper $recommendationHelper,
		\Magento\Customer\Model\Session $customerSession,
		\Magento\Customer\Model\Customer $customer,
    	\Magento\Customer\Model\ResourceModel\CustomerFactory $customerFactory,
    	\Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
		\Magento\Checkout\Model\Session $checkoutSession,
		CollectionFactory $collectionFactory,
		\Magento\Customer\Model\Url $customerUrl,
        \Magento\Catalog\Model\Product $product,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\Catalog\Model\ResourceModel\Product $productResource,
        \Magento\Framework\Session\SessionManagerInterface $coreSession
	)
	{
        $this->_recurlyHelper = $recurlyHelper;
        $this->_subscriptionHelper = $subscriptionHelper;
        $this->_recommendationHelper = $recommendationHelper;
		$this->_recommendationHelper = $recommendationHelper;
		$this->_customerSession = $customerSession;
		$this->_customer = $customer;
		$this->_customerFactory = $customerFactory;
		$this->_productRepository = $productRepository;
		$this->_checkoutSession = $checkoutSession;
		$this->_collectionFactory = $collectionFactory;
		$this->_customerUrl = $customerUrl;
        $this->_product = $product;
        $this->_productFactory = $productFactory;
        $this->_productResource = $productResource;
        $this->_coreSession = $coreSession;
		$this->_couponCode = 'annual_subscription_discount';
		$this->_currency = 'USD';
	}

    /**
     * Create new Recurly subscription for the customer. Use it's existing Recurly account if there is one,
     * otherwise create new Recurly account for the customer
     *
     * @param string $token
     * @param mixed $quiz_id
     * @param string $plan
     * @param bool $remove_not_allowed
     * @return array|void
     * @throws Recurly_Error
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     *
     * @api
     */
	public function createRecurlySubscription($token, $quiz_id, $plan, $remove_not_allowed)
	{
        // If there is Recurly token, plan code and quiz data
        if (! empty($token) && ! empty($plan) && ! empty($quiz_id)) {
            // Configure Recurly Client
            Recurly_Client::$apiKey = $this->_recurlyHelper->getRecurlyPrivateApiKey();
            Recurly_Client::$subdomain = $this->_recurlyHelper->getRecurlySubdomain();

            $checkoutData = $this->_checkoutSession->getQuote()->getShippingAddress()->getData();

            // Get Customer's Recurly account
            $account = ($this->getRecurlyAccount()) ? $this->getRecurlyAccount() : $this->createRecurlyAccount($checkoutData);

            // Get not allowed products
            $notAllowedProducts = $this->getNotAllowedProducts( $quiz_id );
                
            // If there are not allowed products
            if( ! empty( $notAllowedProducts ) ) {
                // If not allowed products should be removed
                if( $remove_not_allowed === true ) {
                    $this->removeNotAllowedProducts( $quiz_id, $notAllowedProducts );
                    
                } else {
                    $response = array(
                        'success'                   => false,
                        'has_not_allowed_products'  => true,
                        'not_allowed_products'      => $notAllowedProducts,
                    );

                    return json_encode( $response );
                }
            }

            // Get order products
            $orderProducts = $this->_coreSession->getOrderProducts();

            // Create Recurly Purchase
            try {
                $purchase = new Recurly_Purchase();
                $purchase->currency = $this->_currency;
                $purchase->collection = 'automatic';
                $purchase->account = $account;

                // Create billing information with the token from Recurly.js
                if ($this->createBillingInfo($account->account_code, $token)) {
                    $purchase->account->billing_info = $this->createBillingInfo($account->account_code, $token);
                } else {
                    return json_encode( array(
                        'success'   => false,
                        'message'   => 'There is a problem with your billing information.'
                    ) );
                }

                // Set shipping information
                if ($this->getRecurlyAccountShippingAddress($account->account_code)) {
                    $purchase->account->shipping_addresses = [ $this->getRecurlyAccountShippingAddress($account->account_code) ];
                } else {
                    return json_encode( array(
                        'success'   => false,
                        'message'   => 'There is a problem with your shipping information.'
                    ) );
                }

                $quoteItems = $this->_checkoutSession->getQuote()->getItemsCollection();
                $totalAnnualAmount = 0;
                $totalAddonsAmount = 0;
                $all_subscriptions = [];

                // Set total annual amount from the calculated price of the Annual product
                foreach( $quoteItems as $item ) {
                    if( $item->getSku() == 'annual' ) {
                        $totalAnnualAmount = $item->getPrice();
                    }
                }

                // Create charges for the addons
                if( ! empty( $orderProducts['addon'] ) ) {
                    foreach( $orderProducts['addon'] as $addon ) {

                        $product = $this->_productRepository->get( $addon['sku'] );
                        $product = $this->_productFactory->create()->load( $product->getId() );

                        if( $product ) {
                            $charge = new Recurly_Adjustment();
                            $charge->account_code = $account->account_code;
                            $charge->currency = $this->_currency;
                            $charge->description = $product->getName() . ' (SKU: ' . $product->getSku() . ' )';
                            $charge->unit_amount_in_cents = $this->convertAmountToCents( $product->getPrice() );
                            $totalAddonsAmount += $product->getPrice();
                            $charge->quantity = $addon['quantity'];
                            $charge->product_code = $product->getSku();
                            $charge->create();

                            $purchase->adjusments = [ $charge ];
                        }
                    }
                }

                if ( $plan == 'annual' ) {
                    // Create Annual Subscription (Master Subscription)
                    $annual_subscription = new Recurly_Subscription();
                    $annual_subscription->plan_code = $plan;
                    $annual_subscription->auto_renew = true;
                    $annual_subscription->total_billing_cycles = 1;
                    $annual_subscription->unit_amount_in_cents = $this->convertAmountToCents( $totalAnnualAmount );
                    $annual_subscription->custom_fields[] = new Recurly_CustomField( 'quiz_id', $quiz_id );
                    array_push( $all_subscriptions, $annual_subscription );

                    // Apply cooupon code to annual subscription
                    if ($this->getCouponCode()) {
                        $purchase->coupon_codes = [ $this->getCouponCode() ];
                    }

                    // Create Seasonal Subscriptions (Child Subscriptions) for the Annual Subscription
                    if ( ! empty( $orderProducts['core'] ) ) {
                        foreach ( $orderProducts['core'] as $core_product ) {
                            $subscription = new Recurly_Subscription();
                            $subscription->plan_code = $this->_recurlyHelper->getSeasonSlugByName( $core_product['season'] );
                            $subscription->auto_renew = true;
                            $subscription->total_billing_cycles = 1;
                            $subscription->unit_amount_in_cents = 0;
                            $subscription->custom_fields[] = new Recurly_CustomField( 'quiz_id', $quiz_id );
                            $subscription->starts_at = $this->getSubscriptionStartDate( $core_product['applicationStartDate'] );
                            array_push( $all_subscriptions, $subscription );
                        }
                    }
                } else {
                    // Create Seasonal Subscription (Master Subscription)
                    $seasonal_subscription = new Recurly_Subscription();
                    $seasonal_subscription->plan_code = $plan;
                    $seasonal_subscription->auto_renew = true;
                    $seasonal_subscription->unit_amount_in_cents = 0;
                    $seasonal_subscription->custom_fields[] = new Recurly_CustomField( 'quiz_id', $quiz_id );
                    array_push( $all_subscriptions, $seasonal_subscription );

                    // Create Seasonal Subscriptions (Child Subscriptions) for the Seasonal Subscription
                    if ( ! empty( $orderProducts['core'] ) ) {
                        foreach ( $orderProducts['core'] as $core_product ) {
                            // Get Product from Magento based on SKU
                            $product = $this->_productRepository->get( $core_product['sku'] );
                            $subscription = new Recurly_Subscription();
                            $subscription->plan_code = $this->_recurlyHelper->getSeasonSlugByName( $core_product['season'] );
                            $subscription->auto_renew = true;
                            $subscription->total_billing_cycles = 1;
                            $subscription->unit_amount_in_cents = $this->convertAmountToCents( $product->getPrice() );
                            $subscription->custom_fields[] = new Recurly_CustomField( 'quiz_id', $quiz_id );
                            $subscription->starts_at = $this->getSubscriptionStartDate( $core_product['applicationStartDate'] );
                            array_push( $all_subscriptions, $subscription );
                        }
                    }
                }

                // Create Addon Subscription
                $addon_subscription = new Recurly_Subscription();
                $addon_subscription->plan_code = 'add-ons';
                $addon_subscription->auto_renew = false;
                $addon_subscription->total_billing_cycles = 1;
                $addon_subscription->unit_amount_in_cents = 0;
                $addon_subscription->custom_fields[] = new Recurly_CustomField( 'quiz_id', $quiz_id );
                array_push( $all_subscriptions, $addon_subscription );

                $purchase->subscriptions = $all_subscriptions;

                $collection = Recurly_Purchase::invoice($purchase);

                return json_encode( array(
                    'success'	=> true
                ) );
            } catch (Recurly_Error $e) {
                return json_encode( array(
                    'success'   => false,
                    'message'   => $e->getMessage()
                ) );
            }
        }

        return;
	}

	/**
	 * Check if the customer already has a Recurly subscription
	 * 
	 * @api
	 */
	public function checkRecurlySubscription()
	{
        // Configure Recurly Client using the API Key and Subdomain entered in the settings page
        Recurly_Client::$apiKey = $this->_recurlyHelper->getRecurlyPrivateApiKey();
        Recurly_Client::$subdomain = $this->_recurlyHelper->getRecurlySubdomain();

        // Get checkout data
        $checkoutData = $this->_checkoutSession->getQuote()->getShippingAddress()->getData();

        // Get Customer's Recurly Account or create new one using current customer's data
        $account = ($this->getRecurlyAccount()) ? $this->getRecurlyAccount() : $this->createRecurlyAccount($checkoutData);

        // Check if the customer has an active subscription
        $activeSubscriptions = $this->hasRecurlySubscription( $account->account_code );
        if( $activeSubscriptions['has_subscriptions'] === true ) {
            $response = array(
                'success'           => true,
                'has_subscription'  => true,
                'refund_amount'     => $activeSubscriptions['refund_amount'],
                'redirect_url'      => $this->_customerUrl->getAccountUrl()
            );
        } else {
            $response = array(
                'success'           => true,
                'has_subscription'  => false,
            );
        }

        return json_encode( $response );
	}

    /**
     * Cancel customer Recurly Subscription
     * 
     * @api
     */
    public function cancelRecurlySubscription()
    {
        // Configure Recurly Client using the API Key and Subdomain entered in the settings page
        Recurly_Client::$apiKey = $this->_recurlyHelper->getRecurlyPrivateApiKey();
        Recurly_Client::$subdomain = $this->_recurlyHelper->getRecurlySubdomain();

        // Get customer's Recurly account code
        $account_code = $this->_customerSession->getCustomer()->getRecurlyAccountCode();

        try {
            $active_subscriptions = Recurly_SubscriptionList::getForAccount($account_code, [ 'state' => 'active' ]);
            $future_subscriptions = Recurly_SubscriptionList::getForAccount($account_code, [ 'state' => 'future' ]);

            foreach ($active_subscriptions as $subscription) {
                $_subscription = Recurly_Subscription::get($subscription->uuid);
                $_subscription->cancel();
            }

            foreach ($future_subscriptions as $subscription) {
                $_subscription = Recurly_Subscription::get($subscription->uuid);
                $_subscription->cancel();
            }

            $response = array(
                'success'   => true,
                'message'   => 'Recurly subscriptions canceled'
            );

            return json_encode( $response );
        } catch (Recurly_NotFoundError $e) {
            $response = array(
                'success'   => false,
                'message'   => 'Recurly subscriptions can not be cancelled (' . $e->getMessage() . ')'
            );

            return json_encode( $response );
        }
    }

    /**
     * Return array of products that are not allowed to be shipped to customer's
     * selected region
     * 
     * @param string $quiz_id
     * @return array
     * 
     */
    private function getNotAllowedProducts( $quiz_id ) {
        $products = $this->_recommendationHelper->getQuizResultProducts( $quiz_id );
        $regionId = $this->_checkoutSession->getQuote()->getShippingAddress()->getRegionId();
        $notAllowedProducts = array();
        $counter = 0;

        foreach( $products['core'] as $core_product ) {
            $product = $this->_productRepository->get( $core_product['sku'] );
            $productId = $product->getId();
            $product = $this->_productFactory->create();
            $this->_productResource->load($product, $productId);

            if( $regionId == $product->getStatesNotAllowed() ) {
                $notAllowedProducts[$counter]['id'] = $product->getId();
                $notAllowedProducts[$counter]['name'] = $product->getName();
                $notAllowedProducts[$counter]['price'] = $product->getPrice();
                $notAllowedProducts[$counter]['sku'] = $core_product['sku'];
                $counter++;
            }
        }

        foreach( $products['addon'] as $addon ) {
            $product = $this->_productRepository->get( $addon['sku'] );
            $productId = $product->getId();
            $product = $this->_productFactory->create();
            $this->_productResource->load($product, $productId);

            if( $regionId == $product->getStatesNotAllowed() ) {
                $notAllowedProducts[$counter]['id'] = $product->getId();
                $notAllowedProducts[$counter]['name'] = $product->getName();
                $notAllowedProducts[$counter]['price'] = $product->getPrice();
                $notAllowedProducts[$counter]['sku'] = $addon['sku'];
                $counter++;
            }
        }

       return array_unique( $notAllowedProducts, SORT_REGULAR );
    }

    /**
     * Remove products that are not allowed to ship to customer's state
     * 
     * @param string $quiz_id
     * @param array $not_allowed_products
     */
    private function removeNotAllowedProducts( $quiz_id, $not_allowed_products )
    {
        $products = $this->_recommendationHelper->getQuizResultProducts( $quiz_id );
        $not_allowed_skus = array();

        foreach( $not_allowed_products as $product ) {
            array_push( $not_allowed_skus, $product['sku'] );
        }

        foreach( $products['core'] as $index => $product ) {
            if( in_array( $product['sku'], $not_allowed_skus) ) {
                unset( $products['core'][$index] );
            }
        }

        foreach( $products['addon'] as $index => $product ) {
            if( in_array( $product['sku'], $not_allowed_skus) ) {
                unset( $products['addon'][$index] );
            }
        }

        // Update order products in session
        $this->_coreSession->setOrderProducts( $products );
    }

	/**
	 * Create billing information with the token provided from Recurly.js
	 * 
	 * @param string $account_code
	 * @param string $token
	 * 
	 * @return object|bool
	 */
	private function createBillingInfo($account_code, $token)
	{
        try {
            $billing_info = new Recurly_BillingInfo();
            $billing_info->account_code = $account_code;
            $billing_info->token_id = $token;
            $billing_info->create();

            return $billing_info;
        } catch (Recurly_NotFoundError $e) {
            return false;
        }
	}


	/**
	 * Check if customer already has a subscription
	 * 
	 * @return bool
	 */
	private function hasRecurlySubscription($account_code)
	{
        try {
            $subscriptions = Recurly_SubscriptionList::getForAccount($account_code, [ 'state' => 'active' ]);
            $subscriptions_amount = 0;

            foreach( $subscriptions as $subscription ) {
                $subscriptions_amount += $subscription->unit_amount_in_cents;
            }

            if ( count($subscriptions) > 0 ) {
                return array(
                    'has_subscriptions' => true,
                    'refund_amount'     => $this->convertAmountToDollars( $subscriptions_amount )
                );
            }

            return array(
                'has_subscriptions' => false,
                'refund_amount'     => 0
            );
        } catch (Recurly_NotFoundError $e) {
            return array(
                'has_subscriptions' => false,
                'refund_amount'     => 0
            );
        }
	}

	/**
	 * Convert order grand total from dollars to cents
	 * 
	 * @return int
	 */
	private function convertAmountToCents($amount)
	{
        return (int) $amount*100;
	}

    /**
     * Convert cents to dollars
     *
     */
    private function convertAmountToDollars($amount)
    {
        return number_format(($amount/100), 2, '.', ' ');
    }

	/**
	 * Check if the current customer has a Recurly account.
	 * If it does, return it's account, otherwise return false.
	 *
	 * @return object|bool
	 */
	private function getRecurlyAccount()
	{
        if (! empty($this->_customerSession->getCustomer()->getData()['recurly_account_code'])) {
            try {
                $account = Recurly_Account::get($this->_customerSession->getCustomer()->getData()['recurly_account_code']);
                return $account;
            } catch (Recurly_NotFoundError $e) {
                if ($e->getCode() == 0) {
                    return false;
                }
                return false;
            }
        }

        return false;
	}

	/**
	 * Get customer's current shipping addresses and use it for the purchase
	 * 
	 * @return object|gool
	 */
	private function getRecurlyAccountShippingAddress($account_code)
	{

		$shipping_addresses = Recurly_ShippingAddressList::get($account_code);

		if( ! empty( $shipping_addresses ) ) {
			foreach( $shipping_addresses as $address ) {
				return $address;
			}
		}

		return false;
	}

	/**
	 * Create Recurly account using the data from the checkout page
	 * 
	 * @return object $account
	 */
	private function createRecurlyAccount($data)
	{
        try {
            // Generate account code from customer's email
            $recurly_account_code = md5($data['email']);
            $account = new Recurly_Account($recurly_account_code);

            // Save Recurly account code to the eav table
            $this->saveRecurlyAccountCode($data['email'], $data['customer_id']);

            // Populate Recurly Account Data
            $account->email = $data['email'];
            $account->first_name = $data['firstname'];
            $account->last_name = $data['lastname'];

            $account->create();

            $shipping_address = new Recurly_ShippingAddress();
            $shipping_address->first_name = $data['firstname'];
            $shipping_address->last_name = $data['lastname'];
            $shipping_address->email = $data['email'];
            $shipping_address->address1 = $data['street'];
            $shipping_address->city = $data['city'];
            $shipping_address->state = $this->getRegionCode($data['region']);
            $shipping_address->zip = $data['postcode'];
            $shipping_address->country = $data['country_id'];

            $account->createShippingAddress($shipping_address);

            return $account;
        } catch (Recurly_ValidationError $e) {

            // If a Recurly account with the account_code exists, save the code as a custom attribute,
            // and get Recurly account using that account code
            if ($e->getCode() === 0) {
                $this->saveRecurlyAccountCode($data['email'], $data['customer_id']);

                return $this->getRecurlyAccount();
            }
        }
	}

	/**
	 * Get current customer and save it's Recurly account code as a custom attribute,
	 * generated from customer's email
	 * 
	 */
	private function saveRecurlyAccountCode($customer_email, $customer_id)
	{
        if (! empty($customer_email) && ! empty($customer_id) && filter_var($customer_email, FILTER_VALIDATE_EMAIL)) {
            $recurly_account_code = md5($customer_email);

            $this->_customerSession->getCustomer()->setData('recurly_account_code', $recurly_account_code);
            $customer = $this->_customer->load($customer_id);
            $customerData = $customer->getDataModel();
            $customerData->setCustomAttribute('recurly_account_code', $recurly_account_code);
            $customer->updateData($customerData);
            $customerResource = $this->_customerFactory->create();
            $customerResource->saveAttribute($customer, 'recurly_account_code');
        }
	}

    /**
     * Check if coupon code exists on Recurly and return it,
     * or return false if it doesn't
     *
     * @return mixed
     * @throws Recurly_Error
     */
    private function checkIfCouponExists()
    {
        try {
            $coupon = Recurly_Coupon::get($this->_couponCode);
            return $coupon;
        } catch (Recurly_NotFoundError $e) {
            return false;
        }
    }

    /**
     * If the coupon code exists, return the code, otherwise create
     * a new Recurly coupon code that can be used for the annual subscriptions
     *
     * @return mixed
     * @throws Recurly_Error
     */
    private function getCouponCode()
    {
        if ($this->checkIfCouponExists()) {
            return $this->_couponCode;
        }

        // Create coupon if it doesn't exist
        try {
            $coupon = new Recurly_Coupon();
            $coupon->coupon_code = $this->_couponCode;
            $coupon->name = '10% Discount Coupon Code';
            $coupon->discount_type = 'percent';
            $coupon->discount_percent = 10;
            $coupon->invoice_description = '10% Discount';
            $coupon->duration = 'forever';
            $coupon->redemption_resource = 'subscription';
            $coupon->applies_to_all_plans = false;
            $coupon->plan_codes = [ 'annual' ];
            $coupon->create();

            return $coupon->coupon_code;
        } catch (Recurly_ValidationError $e) {
            return false;
        }
    }

    /**
     * Calculate shipping start date
     *
     * @param $start_date
     * @return \DateTime
     * @throws \Exception
     */

    private function getSubscriptionStartDate($start_date)
    {
        // Get shipping days start from the settings, if the value is missing set to 14
        $shippingOpenWindow = (! empty($this->_subscriptionHelper->getShipDaysStart())) ? $this->_subscriptionHelper->getShipDaysStart() : 14;

        $applicationStartDate = new \DateTime($start_date);
        $applicationStartDate->sub(new \DateInterval('P' . $shippingOpenWindow . 'D'));
        $todayDate = new \DateTime(date('Y-m-d 00:00:00'));

        return ($todayDate <= $applicationStartDate) ? $applicationStartDate : $todayDate;
    }

    /**
     * Return region code by name
     *
     * @return array
     */
    private function getRegionCode($region)
    {
        $regionCode = $this->_collectionFactory->create()->addRegionNameFilter($region)->getFirstItem()->toArray();
        return $regionCode['code'];
    }

}
