<?php

namespace SMG\Subscriptions\Model;

use SMG\Subscriptions\Api\RecurlyInterface;
use SMG\Subscriptions\Config\RecurlyConfig;

use Recurly_Client;
use Recurly_Account;
use Recurly_Subscription;
use Recurly_BillingInfo;

class RecurlySubscription implements RecurlyInterface
{

	protected $_customerSession;

	protected $_customer;

	protected $_customerFactory;

	/**
	 * @var \SMG\Subscriptions\Config\RecurlyConfig
	 */
	protected $_recurlyConfig;

	public function __construct(
		\Magento\Customer\Model\Session $customerSession,
		 \Magento\Customer\Model\Customer $customer,
    	\Magento\Customer\Model\ResourceModel\CustomerFactory $customerFactory,
		\SMG\Subscriptions\Config\RecurlyConfig $recurlyConfig
	)
	{
		$this->_customerSession = $customerSession;
		$this->_customer = $customer;
		$this->_customerFactory = $customerFactory;
		$this->_recurlyConfig = $recurlyConfig;
	}

	private function checkRecurlyAccount($recurly_account_code)
	{
		Recurly_Client::$apiKey = $this->_recurlyConfig->getValue('apikey');
		Recurly_Client::$subdomain = $this->_recurlyConfig->getValue('subdomain');

		return false;
	}

	/**
	 * Create new recurlt subscription
	 * 
	 * @api
	 */
	public function newRecurly($token, $order)
	{

		// If Recurly.js token exists and there is an order
		if( ! empty( $token) && ! empty( $order ) ) {
			// Configure Recurly Client to use the API key and subdomain stored in settings
			Recurly_Client::$apiKey = $this->_recurlyConfig->getValue('apikey');
			Recurly_Client::$subdomain = $this->_recurlyConfig->getValue('subdomain');

			// Create new subscription
			try {
				$subscription = new Recurly_Subscription();
				$subscription->plan_code = 'annual'; // This should be changed later when customer selects it's subscription type
				$subscription->currency = 'USD';

				// If Recurly account exists, add the subscription to it
				if( ! empty( $this->_customerSession->getCustomer()->getData()['recurly_account_code'] ) ) {
					$account = Recurly_Account::get( $this->_customerSession->getCustomer()->getData()['recurly_account_code'] );
					$subscription->account = $account;
				} else { // Otherwise create new account
					$subscription->account = new Recurly_Account();
					$new_recurly_account_code = md5($order['customerData']['email']);
					$subscription->account->account_code = $new_recurly_account_code;
					$new_recurly_account_code = md5($order['customerData']['email']);

					// Save Recurly account code to the eav table
					$this->_customerSession->getCustomer()->setData('recurly_account_code', $new_recurly_account_code);
					$customer = $this->_customer->load($order['customerData']['id']);
					$customerData = $customer->getDataModel();
					$customerData->setCustomAttribute('recurly_account_code',$new_recurly_account_code);
					$customer->updateData($customerData);
					$customerResource = $this->_customerFactory->create();
					$customerResource->saveAttribute($customer, 'recurly_account_code');

					$subscription->account->first_name = $order['customerData']['firstname'];
					$subscription->account->last_name = $order['customerData']['lastname'];
					$subscription->account->email = $order['customerData']['email'];
				}

				$subscription->account->billing_info = new Recurly_BillingInfo();
				$subscription->account->billing_info->token_id = $token;

				$subscription->create();

				return array( array( 'success' => true, 'message' => 'Subscription created.' ) );
			} catch(Recurly_ValidationError $e) {
				print_r("Invalid account");
				print_r( $e );
				return array( array( 'success' => false, 'message' => $e ) );
			}
		}

		return;
	}
}