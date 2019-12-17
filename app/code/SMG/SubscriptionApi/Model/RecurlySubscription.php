<?php

namespace SMG\SubscriptionApi\Model;

use Magento\Directory\Model\ResourceModel\Region\CollectionFactory;
use Magento\Framework\Exception\LocalizedException;
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
 * @package SMG\SubscriptionApi\Model
 */
class RecurlySubscription
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
		\Magento\Customer\Model\Url $customerUrl
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
		$this->_couponCode = 'annual_subscription_discount';
		$this->_currency = 'USD';
	}

    /**
     * Create new Recurly subscription for the customer. Use it's existing Recurly account if there is one,
     * otherwise create new Recurly account for the customer
     *
     * @param string $token
     * @param \SMG\SubscriptionApi\Model\Subscription $subscription
     * @return array|void
     * @throws LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
	public function createSubscription( $token, $subscription )
	{
	    $quizId = $subscription->getQuizId();
	    $subscriptionType = $subscription->getSubscriptionType();

        // If there is Recurly token, plan code and quiz data
        if (true || ! empty($token) && ! empty($subscriptionType) && ! empty($quizId)) {

            // Configure Recurly Client
            Recurly_Client::$apiKey = $this->_recurlyHelper->getRecurlyPrivateApiKey();
            Recurly_Client::$subdomain = $this->_recurlyHelper->getRecurlySubdomain();

            $checkoutData = $this->_checkoutSession->getQuote()->getShippingAddress()->getData();

            // Update Subscription with Gigya ID
            try {
                $customer = $this->_customer->load($checkoutData['customer_id']);
                $subscription->setCustomerId( $checkoutData['customer_id'] );
                $subscription->setGigyaId( $customer->getGigyaUid() );
                $subscription->save();
            } catch ( \Exception $e ) {
                throw new LocalizedException( __('Failed to assign subscription to user account.') );
            }

            // Get Customer's Recurly account
            try {
                $account = ( $this->getRecurlyAccount()) ? $this->getRecurlyAccount() : $this->createRecurlyAccount( $checkoutData );
            } catch ( \Exception $e ) {
                throw new LocalizedException( __('There was a problem with the subscription account.') );
            }

            // Create Recurly Purchase
            try {
                $purchase = new Recurly_Purchase();
                $purchase->currency = $this->_currency;
                $purchase->collection = 'automatic';
                $purchase->account = $account;

                // Create billing information with the token from Recurly.js
                try {
                    $purchase->account->billing_info = $this->createBillingInfo( $account->account_code, $token );
                } catch ( \Exception $e ) {
                    throw new LocalizedException( __('There is a problem with the billing information.') );
                }

                // Set shipping information
                try {
                    $purchase->account->shipping_address = [ $this->getRecurlyAccountShippingAddress( $account->account_code ) ];
                } catch ( \Exception $e ) {
                    throw new LocalizedException( __('There is a problem with the shipping information.') );
                }

                // Create Master Subscription
                try {
                    $subscriptions[] = $this->createMasterSubscription( $subscription );
                } catch ( \Exception $e ) {
                    throw new LocalizedException( __('There is a problem creating the subscription') );
                }

                // Create Seasonal Subscriptions
                try {
                    $subscriptions = array_merge( $subscriptions, $this->createSeasonalSubscriptions( $subscription ) );
                } catch ( \Exception $e ) {
                    throw new LocalizedException( __('There is a problem creating the subscription') );
                }

                // Create Add-on Charges
                try {
                    $addonCharges = $this->createAddonCharges( $subscription, $account );
                    if ( $subscription->getSubscriptionType() == 'annual' ) {
                        $purchase->adjustments = $addonCharges;
                    }
                } catch ( \Exception $e ) {
                    throw new LocalizedException( __('There is a problem creating the add-ons') );
                }

                // Add Coupon Code if annual
                try {
                    if ( $subscription->getSubscriptionType() == 'annual' ) {
                        $purchase->coupon_codes = [ $this->getCouponCode() ];
                    }
                } catch ( \Exception $e ) {
                    throw new LocalizedException( __('There was a problem applying the discount code.') );
                }

                $purchase->subscriptions = $subscriptions;

                try {
                    Recurly_Purchase::invoice($purchase);
                } catch ( \Exception $e ) {
                    throw new LocalizedException( __('There was an issue invoicing the subscription.') );
                }

            } catch ( \Exception $e ) {
                throw new LocalizedException( __( $e->getMessage() ) );
            }

            try {
                $this->getSubscriptionIds( $checkoutData, $account, $subscription );
                $subscription->setSubscriptionStatus( 'pending_order' )->save();
            } catch ( \Exception $e ) {
                throw new LocalizedException( __('There was an issue getting the subscription ids.') );
            }
        }
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
     * @param bool $cancelActive
     * @param bool $cancelFuture
     * @return array
     * @throws Recurly_Error
     * @throws LocalizedException
     * @api
     */
    public function cancelRecurlySubscriptions( bool $cancelActive = true, bool $cancelFuture = true )
	{
	    $cancelledSubscriptionIds = [];

        // Configure Recurly Client using the API Key and Subdomain entered in the settings page and get account
        try {
            Recurly_Client::$apiKey = $this->_recurlyHelper->getRecurlyPrivateApiKey();
            Recurly_Client::$subdomain = $this->_recurlyHelper->getRecurlySubdomain();

            $account_code = $this->_customerSession->getCustomer()->getGigyaUid();
        } catch ( \Exception $e ) {
            throw new LocalizedException( __('Failed to retrieve subscription account.') );
        }

        // Handle Cancelling Active Subscriptions
        if ( $cancelActive ) {
            try {
                $cancelledSubscriptionIds = array_merge( $cancelledSubscriptionIds, $this->cancelRecurlySubscriptionsByAccountCodeAndStatus( $account_code, 'active' ) );
            } catch ( LocalizedException $e ) {
                throw $e;
            }
        }

        // Handle Cancelling Future Subscriptions
        if ( $cancelFuture ) {
            try {
                $cancelledSubscriptionIds = array_merge( $cancelledSubscriptionIds, $this->cancelRecurlySubscriptionsByAccountCodeAndStatus( $account_code, 'future' ) );
            } catch ( LocalizedException $e ) {
                throw $e;
            }
        }

        return $cancelledSubscriptionIds;
	}

    /**
     * Cancel Recurly subscriptions By Account Code and Status
     * @param string $account_code
     * @param string $status
     * @return array
     * @throws LocalizedException
     * @throws Recurly_Error
     */
	private function cancelRecurlySubscriptionsByAccountCodeAndStatus( string $account_code, string $status ) {
        $cancelledSubscriptionIds = [];
        try {
            $subscriptions = Recurly_SubscriptionList::getForAccount($account_code, [ 'state' => $status ]);

            foreach ( $subscriptions as $subscription ) {
                $_subscription = Recurly_Subscription::get( $subscription->uuid );
//                $_subscription->cancel();
                $cancelledSubscriptionIds[$subscription->getValues()['plan']->getValues()['plan_code']] = $subscription->uuid;
            }
        } catch (Recurly_NotFoundError $e) {
            throw new LocalizedException( __('Failed to cancel active subscriptions.') );
        }
        return $cancelledSubscriptionIds;
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
	 * Return Recurly Plan Code base on the name of the core product
	 *
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
				return '';
		}
	}

	/**
	 * Convert order grand total from dollars to cents
	 *
	 * @return int
	 */
	private function convertAmountToCents($amount)
	{
        return (float) $amount*100;
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
     * @param string $gigyaId
	 * @return object|bool
	 */
	private function getRecurlyAccount( string $gigyaId = null)
	{
	    if ( is_null($gigyaId) ) {
	        $gigyaId = $this->_customerSession->getCustomer()->getGigyaUid();
        }
        if ( ! empty( $gigyaId )) {
            try {
                $account = Recurly_Account::get( $gigyaId );
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
     * @param $data
     * @return object $account
     */
	private function createRecurlyAccount($data)
	{
        try {
            // Generate account code from customer's email
            $customer = $this->_customer->load($data['customer_id']);
            $recurly_account_code = $customer->getGigyaUid();
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

    /**
     * Create the master subscription
     * @param Subscription $subscription
     * @return Recurly_Subscription
     * @throws \Exception
     */
    private function createMasterSubscription( Subscription $subscription )
    {
        try {
            $masterSubscription = new Recurly_Subscription();

            if ( $subscription->getSubscriptionType() == 'annual' ) {

                // Create Annual Subscription (Master Subscription)
                $masterSubscription->plan_code = $subscription->getSubscriptionType();
                $masterSubscription->auto_renew = true;
                $masterSubscription->total_billing_cycles = 1;
                $masterSubscription->unit_amount_in_cents = $this->convertAmountToCents( $subscription->getPrice() );
                $masterSubscription->
                $masterSubscription->custom_fields[] = new Recurly_CustomField('quiz_id', $subscription->getQuizId() );

            } else {

                // Create Seasonal Subscription (Master Subscription)
                $masterSubscription->plan_code = $subscription->getSubscriptionType();
                $masterSubscription->auto_renew = true;
                $masterSubscription->unit_amount_in_cents = 0;
                $masterSubscription->custom_fields[] = new Recurly_CustomField('quiz_id', $subscription->getQuizId() );
            }

            return $masterSubscription;
        } catch ( \Exception $e ) {
            throw $e;
        }

    }

    /**
     * Create Seasonal Subscriptions
     * @param Subscription $subscription
     * @return array
     * @throws \Exception
     */
    private function createSeasonalSubscriptions( Subscription $subscription )
    {
        try {
            $subscriptionOrders = $subscription->getSubscriptionOrders();
            $subOrders = [];

            /** @var \SMG\SubscriptionApi\Model\SubscriptionOrder $subscriptionOrder */
            foreach ( $subscriptionOrders as $subscriptionOrder ) {

                $subOrder = new Recurly_Subscription();
                $subOrder->plan_code = $this->_recurlyHelper->getSeasonSlugByName( $subscriptionOrder->getSeasonName() );
                $subOrder->auto_renew = true;
                $subOrder->total_billing_cycles = 1;
                if ( $subscription->getSubscriptionType() != 'annual' ) {
                    $subOrder->unit_amount_in_cents = $this->convertAmountToCents( $subscriptionOrder->getPrice() );
                }
                $subOrder->custom_fields[] = new Recurly_CustomField('quiz_id', $subscription->getQuizId() );
                $subOrder->starts_at = $subscriptionOrder->getShipStartDate();
                $subOrders[] = $subOrder;
            }

            return $subOrders;
        } catch ( \Exception $e ) {
            throw $e;
        }
    }

    /**
     * Create Addon Charges
     * @param Subscription $subscription
     * @param $account
     * @return array
     * @throws \Exception
     */
    private function createAddonCharges( Subscription $subscription, $account )
    {
        try {
            $charges = [];
            $subscriptionAddonOrders = $subscription->getSubscriptionAddonOrders();

            /** @var SubscriptionAddonOrder $subscriptionAddonOrder */
            foreach( $subscriptionAddonOrders as $subscriptionAddonOrder) {

                $subscriptionAddonOrderItems = $subscriptionAddonOrder->getSubscriptionAddonOrderItems( true );

                /** @var SubscriptionAddonOrderItem $subscriptionAddonOrderItem */
                foreach ( $subscriptionAddonOrderItems as $subscriptionAddonOrderItem ) {

                    $product = $subscriptionAddonOrderItem->getProduct();

                    $charge = new Recurly_Adjustment();
                    $charge->account_code = $account->account_code;
                    $charge->currency = $this->_currency;
                    $charge->description = $product->getName() . ' (SKU: ' . $product->getSku() . ')';
                    $charge->unit_amount_in_cents = $this->convertAmountToCents( $product->getPrice() );
                    $charge->quantity = $subscriptionAddonOrderItem->getQty();
                    $charge->product_code = $product->getSku();
                    if ( $subscription->getSubscriptionType() != 'annual' ) {
                        $charge->start_date = $subscription->getFirstSubscriptionOrder()->getShipStartDate();
                        $charge->revenue_schedule_date = 'at_range_start';
                    }
                    $charge->create();
                    $charges[] = $charge;
                }
            }
        } catch ( \Exception $e ) {
            throw $e;
        }

        return $charges;
    }

    /**
     * Get Subscription Ids and save them
     * @param $checkoutData
     * @param $account
     * @param $subscription
     * @throws \Exception
     */
    private function getSubscriptionIds( $checkoutData, $account, $subscription )
    {
        try {
            Recurly_Client::$apiKey = $this->_recurlyHelper->getRecurlyPrivateApiKey();
            Recurly_Client::$subdomain = $this->_recurlyHelper->getRecurlySubdomain();

            $activeSubs = Recurly_SubscriptionList::getForAccount($account->account_code, [ 'state' => 'active' ]);
            $futureSubs = Recurly_SubscriptionList::getForAccount($account->account_code, [ 'state' => 'future' ]);

            $subCodes = [];
            foreach ( $activeSubs as $sub ) {
                $subCodes[] = [
                    'subscription_id' => $sub->getValues()['uuid'],
                    'plan_code' => $sub->getValues()['plan']->getValues()['plan_code']
                ];
            }
            foreach ( $futureSubs as $sub ) {
                $subCodes[] = [
                    'subscription_id' => $sub->getValues()['uuid'],
                    'plan_code' => $sub->getValues()['plan']->getValues()['plan_code']
                ];
            }

            foreach ( $subCodes as $subCode ) {

                if ( in_array( $subCode['plan_code'], ['annual', 'seasonal'] ) ) {
                    $subscription->setSubscriptionId( $subCode['subscription_id'] )->save();
                } else {
                    $subscription->getSubscriptionOrderBySeasonSlug( $subCode['plan_code'] )->setSubscriptionId( $subCode['subscription_id'] )->save();
                }
            }
        } catch ( \Exception $e ) {
            throw $e;
        }
    }

    /**
     * Create Credit for Recurly
     * @param $totalRefund
     * @param $gigyaId
     * @throws LocalizedException
     */
    public function createCredit( $totalRefund, $gigyaId ) {

        try {
            Recurly_Client::$apiKey = $this->_recurlyHelper->getRecurlyPrivateApiKey();
            Recurly_Client::$subdomain = $this->_recurlyHelper->getRecurlySubdomain();

            $purchase = new Recurly_Purchase();
            $purchase->currency = $this->_currency;
            $purchase->collection = 'automatic';
            $purchase->account = $this->getRecurlyAccount( $gigyaId );

            $credit = new Recurly_Adjustment();
            $credit->account_code = $gigyaId;
            $credit->currency = $this->_currency;
            $credit->description = 'Partial refund for subscription cancellation';
            $credit->unit_amount_in_cents = $this->convertAmountToCents( $totalRefund );
            $credit->create();

            $purchase->adjustments = [ $credit ];
            Recurly_Purchase::invoice($purchase);

        } catch ( \Exception $e ) {
            throw new LocalizedException( __('Credit failed to apply.' . $e->getMessage()) );
        }
    }

}
