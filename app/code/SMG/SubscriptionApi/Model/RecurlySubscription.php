<?php
namespace SMG\SubscriptionApi\Model;

use Magento\Directory\Model\ResourceModel\Region\Collection;
use Magento\Directory\Model\ResourceModel\Region\CollectionFactory;
use SMG\SubscriptionApi\Api\RecurlyInterface;
use Recurly_Client;
use Recurly_Account;
use Recurly_Subscription;
use Recurly_BillingInfo;
use Recurly_Coupon;
use Recurly_NotFoundError;
use Recurly_Purchase;
use Recurly_Adjustment;
use Recurly_ShippingAddress;
use Recurly_ShippingAddressList;
use Recurly_CustomField;
use Recurly_ValidationError;
use Recurly_SubscriptionList;

class RecurlySubscription implements RecurlyInterface
{

	protected $_helper;
	protected $_customerSession;
	protected $_customer;
	protected $_customerFactory;
	protected $_couponCode;
	protected $_currency;
	protected $_productRepository;
	protected $_checkoutSession;
	protected $_collectionFactory;
	protected $_customerUrl;

	public function __construct(
		\SMG\SubscriptionApi\Helper\RecurlyHelper $helper,
		\Magento\Customer\Model\Session $customerSession,
		\Magento\Customer\Model\Customer $customer,
    	\Magento\Customer\Model\ResourceModel\CustomerFactory $customerFactory,
    	\Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
		\Magento\Checkout\Model\Session $checkoutSession,
		CollectionFactory $collectionFactory,
		\Magento\Customer\Model\Url $customerUrl
	)
	{
		$this->_helper = $helper;
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
	 * @api
	 */
	public function createRecurlySubscription($token, $quiz, $plan, $cancel_existing = false)
	{
		// If there is Recurly token, plan code and quiz data
		if( ! empty( $token ) && ! empty( $plan ) && ! empty( $quiz ) ) {
			// Configure Recurly Client
			Recurly_Client::$apiKey = $this->_helper->getRecurlyPrivateApiKey();
			Recurly_Client::$subdomain = $this->_helper->getRecurlySubdomain();

			$checkoutData = $this->_checkoutSession->getQuote()->getShippingAddress()->getData();

			// Get Customer's Recurly account
			$account = ( $this->getRecurlyAccount() ) ? $this->getRecurlyAccount() : $this->createRecurlyAccount( $checkoutData );

			// Cancel existing subscriptions if the customer has agreed to that
			if( $cancel_existing === true ) {
				$this->cancelAccountSubscriptions( $account->account_code );
			}

			// Create Recurly Purchase
			$purchase = new Recurly_Purchase();
			$purchase->currency = $this->_currency;
			$purchase->collection = 'automatic';
			$purchase->account = $account;

			// Create billing information with the token from Recurly.js
			if( $this->createBillingInfo( $account->account_code, $token ) ) {
				$purchase->account->billing_info = $this->createBillingInfo( $account->account_code, $token );
			} else {
				return array(
					'success'	=> false,
					'message'	=> 'There is a problem with the billing information.'
				);
			}

			// Set shipping information
			if( $this->getRecurlyAccountShippingAddress( $account->account_code ) ) {
				$purchase->account->shipping_addresses = array( $this->getRecurlyAccountShippingAddress( $account->account_code ) );
			} else {
				return array(
					'success'	=> false,
					'message'	=> 'There is a problem with the shipping information.'
				);
			}

			$quoteItems = $this->_checkoutSession->getQuote()->getItemsCollection();
			$totalAnnualAmount = 0;
			$seasonalProductsSkus = array( 'annual', 'early-spring', 'late-spring', 'early-summer', 'early-fall');

			foreach( $quoteItems as $item ) {
				if( $item->getSku() == 'annual' ) {
					$totalAnnualAmount = $item->getPrice(); // Tota price of the annual subscription
				} elseif( ! in_array( $item->getSku(), $seasonalProductsSkus ) ) { // If it's addon
					// Charge for the addons
					$charge = new Recurly_Adjustment();
					$charge->account_code = $account->account_code;
					$charge->currency = $this->_currency;
					$charge->description = $item->getName() . ' (SKU: ' . $item->getSku() . ')';
					$charge->unit_amount_in_cents = $this->convertAmountToCents( $item->getPrice() );
					$charge->quantity = 1;
					$charge->product_code = $item->getSku();
					$charge->create();

					$purchase->adjusments = array( $charge );
				}
			}

			$all_subscriptions = array();
			$seasonalProducts = $this->getPlanData($quiz['id']);

			if( $plan == 'annual' ) {
				// Create Annual Subscription (Master Subscription)
				$annual_subscription = new Recurly_Subscription();
				$annual_subscription->plan_code = $plan;
				$annual_subscription->auto_renew = true;
				$annual_subscription->total_billing_cycles = 1;
				$annual_subscription->unit_amount_in_cents = $this->convertAmountToCents( $totalAnnualAmount );
				$annual_subscription->custom_fields[] = new Recurly_CustomField( 'quiz_id', $quiz['id'] );
				array_push( $all_subscriptions, $annual_subscription );

				// Apply cooupon code to annual subscription
				if( $this->getCouponCode() ) {
					$purchase->coupon_codes = array( $this->getCouponCode() );
				}

				// Create Seasonal Subscriptions (Child Subscriptions) for the Annual Subscription
				if( ! empty( $seasonalProducts['plan']['coreProducts'] ) ) {
					foreach( $seasonalProducts['plan']['coreProducts'] as $season ) {
						$subscription = new Recurly_Subscription();
						$subscription->plan_code = $this->getPlanCodeByName( $season['season'] );
						$subscription->auto_renew = true;
						$subscription->total_billing_cycles = 1;
						$subscription->unit_amount_in_cents = 0;
						$subscription->custom_fields[] = new Recurly_CustomField( 'quiz_id', $quiz['id'] );
						$subscription->starts_at = $this->getSubscriptionStartDate($season['applicationStartDate']);
						array_push( $all_subscriptions, $subscription );
					}
				}
			} else {
				// Create Seasonal Subscription (Master Subscription)
				$seasonal_subscription = new Recurly_Subscription();
				$seasonal_subscription->plan_code = $plan;
				$seasonal_subscription->auto_renew = true;
				$seasonal_subscription->unit_amount_in_cents = 0;
				$seasonal_subscription->custom_fields[] = new Recurly_CustomField( 'quiz_id', $quiz['id'] );
				array_push( $all_subscriptions, $seasonal_subscription );

				// Create Seasonal Subscriptions (Child Subscriptions) for the Seasonal Subscription
				if( ! empty( $seasonalProducts['plan']['coreProducts'] ) ) {
					foreach( $seasonalProducts['plan']['coreProducts'] as $season ) {
						// Get Product from Magento based on SKU
						$product = $this->_productRepository->get( $season['sku'] );
						$subscription = new Recurly_Subscription();
						$subscription->plan_code = $this->getPlanCodeByName( $season['season'] );
						$subscription->auto_renew = true;
						$subscription->total_billing_cycles = 1;
						$subscription->unit_amount_in_cents = $this->convertAmountToCents( $product->getPrice() );
						$subscription->custom_fields[] = new Recurly_CustomField( 'quiz_id', $quiz['id'] );
						$subscription->starts_at = $this->getSubscriptionStartDate($season['applicationStartDate']);
						array_push( $all_subscriptions, $subscription );
					}
				}
			}

			$purchase->subscriptions = $all_subscriptions;

			try {
				$collection = Recurly_Purchase::invoice( $purchase );
				return array(
					'success' 	=> true,
					'message'	=> 'Subscriptions created.'
				);
			} catch( Recurly_ValidationError $e ) {
				return array(
					'success'	=> false,
					'message'	=> $e->getMessage(),
				);
			}

		}

		return;
	}

	/**
	 * Check if the customer already has a Recurly subscription
	 * 
	 * @api
	 */
	public function checkRecurlySubscription($token)
	{
		// Configure Recurly Client using the API Key and Subdomain entered in the settings page
		Recurly_Client::$apiKey = $this->_helper->getRecurlyPrivateApiKey();
		Recurly_Client::$subdomain = $this->_helper->getRecurlySubdomain();

		// Get checkout data
		$checkout_data = $this->_checkoutSession->getQuote()->getShippingAddress()->getData();

		// Get Customer's Recurly Account or create new one using current customer's data
		$account = ( $this->getRecurlyAccount() ) ? $this->getRecurlyAccount() : $this->createRecurlyAccount($checkout_data);

		/**
		 * Check if the customer already has subscriotions. If yes, ask them if they want to cancel and create a new one.
		 * If they don't want to create a new one, redirect them to the my account page
		 */
		return array( array( 'success' => true, 'message' => 'You do not have a subscription', 'has_subscription' => false ) );

		if( $this->hasRecurlySubscription($account->account_code) ) {
			return array( array( 'success' => false, 'message' => 'You already have subscriptions. Would you like to cancel them and create new one?', 'has_subscription' => true, 'redirect_url' => $this->_customerUrl->getAccountUrl() ) );
		} else {
		}

	}

		/**
	 * Cancel subscriptions of specific Recurly account
	 * 
	 * @param string $account_code
	 * 
	 * @return bool
	 */
	private function cancelAccountSubscriptions($account_code)
	{
		try {
			$subscriptions = Recurly_SubscriptionList::getForAccount($account_code);

			foreach ( $subscriptions as $subscription ) {
				$_subscription = Recurly_Subscription::get($subscription->uuid);
				$_subscription->cancel();
		  	}

		  	return true;
		} catch (Recurly_NotFoundError $e) {
			return false;
		}
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
		} catch(Recurly_NotFoundError $e) {
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
			$subscriptions = Recurly_SubscriptionList::getForAccount($account_code);

			if( count( $subscriptions ) > 0 ) {
				return true;
			}

			return false;
		} catch(Recurly_NotFoundError $e) {
			return false;
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
		return (int) $amount*100;
	}

	/**
	 * Check if the current customer has a Recurly account.
	 * If it does, return it's account, otherwise return false.
	 *
	 * @return object|bool
	 */
	private function getRecurlyAccount()
	{
		if( ! empty( $this->_customerSession->getCustomer()->getData()['recurly_account_code'] ) ) {
			try {
				$account = Recurly_Account::get( $this->_customerSession->getCustomer()->getData()['recurly_account_code'] );
				return $account;
			} catch(Recurly_NotFoundError $e) {
				return false;
			}
		}

		return false;
	}

	/**
	 * Create shipping address and connect it with the account
	 *
	 * @return bool
	 */
	private function createRecurlyAccountShippingAddress($account_code, $data)
	{
		if( $this->getRecurlyAccount() ) {
			$account = $this->getRecurlyAccount();
			
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
			$recurly_account_code = md5( $data['email'] );
			$account = new Recurly_Account($recurly_account_code);

			// Save Recurly account code to the eav table
			$this->saveRecurlyAccountCode( $data['email'], $data['customer_id'] );

			// Populate Recurly Account Data
			$account->email = $data['email'];
			$account->first_name = $data['firstname'];
			$account->last_name = $data['lastname'];

			if( ! $this->createRecurlyAccountShippingAddress($account->account_code, $data) ) {
				return array( array( 'success' => false, 'message' => 'There was a problem creating a shipping address for the account.' ) );
			}

			$this->createRecurlyAccountShippingAddress($account->account_code, $data);

			$account->create();
		} catch(Recurly_ValidationError $e) {
			/**
			 * If a Recurly account with the account_code exists, save the code as a custom attribute,
			 * and get Recurly account using that account code
			 */
			if( $e->getCode() === 0 ) {
				$this->saveRecurlyAccountCode( $data['email'], $dat['customer_id'] );

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
		if( ! empty( $customer_email ) && ! empty( $customer_id ) && filter_var( $customer_email, FILTER_VALIDATE_EMAIL ) ) {
			$recurly_account_code = md5( $customer_email );

			$this->_customerSession->getCustomer()->setData('recurly_account_code', $recurly_account_code);
			$customer = $this->_customer->load($customer_id);
			$customerData = $customer->getDataModel();
			$customerData->setCustomAttribute('recurly_account_code',$recurly_account_code);
			$customer->updateData($customerData);
			$customerResource = $this->_customerFactory->create();
			$customerResource->saveAttribute($customer, 'recurly_account_code');
		}
	}

	/**
	 * 
	 * 
	 */
	private function getPlanData($quiz_id)
	{
		 if( ! empty( $quiz_id ) ) {
		 	$url = 'https://lspaasdraft.azurewebsites.net/api/completedQuizzes/' . $quiz_id;

            try {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, TRUE);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
                curl_setopt($ch, CURLOPT_TIMEOUT, 45);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER,TRUE);
                curl_setopt($ch, CURLOPT_POST, FALSE);
                curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                    'Content-Type: application/json; charset=utf-8',
                    'Accept: application/json',
                ));
                $response = curl_exec($ch);

                if(curl_errno($ch)) {
                    throw new Exception(curl_error($ch));
                }

                curl_close($ch);

                return json_decode($response, true);
            } catch(Exception $e) {
                throw new Exception($e);
            }
        }
	}

	/**
	 * Check if coupon code exists on Recurly and return it,
	 * or return false if it doesn't
	 * 
	 * @return mixed
	 */
	private function checkIfCouponExists()
	{
		try {
			$coupon = Recurly_Coupon::get($this->_couponCode);
			return $coupon;
		} catch(Recurly_NotFoundError $e) {
			return false;
		}
	}

	/**
	 * If the coupon code exists, return the code, otherwise create
	 * a new Recurly coupon code that can be used for the annual subscriptions
	 * 
	 * @return mixed
	 */
	private function getCouponCode()
	{
		if( $this->checkIfCouponExists()) {
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
			$coupon->plan_codes = array( 'annual' );
			$coupon->create();

			return $coupon->coupon_code;
		} catch(Recurly_ValidationError $e) {
			return false;
		}
	}

	/**
	 * Calculate shipping start date 
	 * 
	 * @return DateTime 
	 */
	private function getSubscriptionStartDate($start_date)
	{
		// Get shipping days start from the settings, if the value is missing set to 14
		$shippingOpenWindow = ( ! empty( $this->_helper->getShipDaysStart() ) ) ? $this->_helper->getShipDaysStart() : 14;

		$applicationStartDate = new \DateTime($start_date);
		$applicationStartDate->sub(new \DateInterval('P' . $shippingOpenWindow . 'D'));
		$todayDate = new \DateTime(date('Y-m-d 00:00:00'));

		return ( $todayDate <= $applicationStartDate ) ? $applicationStartDate : $todayDate;
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