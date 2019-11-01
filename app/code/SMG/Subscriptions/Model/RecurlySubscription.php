<?php

namespace SMG\Subscriptions\Model;

use PHPUnit\Runner\Exception;
use SMG\Subscriptions\Api\RecurlyInterface;
use SMG\Subscriptions\Config\RecurlyConfig;

use Recurly_Client;
use Recurly_Account;
use Recurly_Subscription;
use Recurly_BillingInfo;
use Recurly_ShippingAddress;

class RecurlySubscription implements RecurlyInterface
{

	protected $_customerSession;

	protected $_customer;

	protected $_customerFactory;

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

            $account = null;
            // If Recurly account exists for the customer record, let's make sure that it actually pulls back an account
            if( ! empty( $this->_customerSession->getCustomer()->getData()['recurly_account_code'] ) ) {

                try {
                    // Attempt to pull an account
                    $account = Recurly_Account::get( $this->_customerSession->getCustomer()->getData()['recurly_account_code'] );

                } catch (Recurly_NotFoundError $e) {

                    // No account existed, let's update the customer record
                    $this->_customerSession->getCustomer()->setData('recurly_account_code', null);
                }
            }

            // If there is no account, let's create one
            if ( is_null($account) ) {

                // Let's generate an account code
                // @todo this should be the gigya id
                $recurlyAccount = md5($order['customerData']['email']);
                $this->_customerSession->getCustomer()->setData('recurly_account_code', $recurlyAccount);

                try {
                    $account = new Recurly_Account($recurlyAccount);
                    $account->email = $order['customerData']['email'];
                    $account->last_name = $order['customerData']['lastname'];
                    $account->first_name = $order['customerData']['firstname'];

                    // work shipping address
                    $shad1 = new Recurly_ShippingAddress();
                    $shad1->first_name = "Verena";
                    $shad1->last_name = "Example";
                    $shad1->company = "Recurly Inc.";
                    $shad1->phone = "555-555-5555";
                    $shad1->email = "verena@example.com";
                    $shad1->address1 = "123 Main St.";
                    $shad1->city = "San Francisco";
                    $shad1->state = "CA";
                    $shad1->zip = "94110";
                    $shad1->country = "US";

                    // Create the account
                    $account->shipping_addresses = array($shad1, $shad1);
                    $account->create();

                } catch (Exception $e) {
                    return array(array('failure' => true, 'message' => 'Unexpected error: ' . $e->getMessage()));
                }
            }

                try {
                    $subscription = new Recurly_Subscription();
                    $subscription->plan_code = 'Annual';
                    $subscription->currency = 'USD';

                    // @todo feeling this is the wrong address. Need to validate.
                    $address = $order['customerData']['addresses'][0];

                    $billing_info = new Recurly_BillingInfo();
                    $billing_info->token_id = $token;

                    $account->billing_info = $billing_info;
                    $subscription->account = $account;

                    $subscription->create();

                } catch (\Recurly_ValidationError $e) {
                    return array( array( 'failure' => true, 'message' => 'Unexpected error: ' . $e->getMessage() ) );
                }

            // @todo do we need this?

//                  // Save to customer record
//					$customer = $this->_customer->load($order['customerData']['id']);
//					$customerData = $customer->getDataModel();
//					$customerData->setCustomAttribute('recurly_account_code',$new_recurly_account_code);
//					$customer->updateData($customerData);
//					$customerResource = $this->_customerFactory->create();
//					$customerResource->saveAttribute($customer, 'recurly_account_code');
            }

		return array( array( 'success' => true, 'message' => 'Subscription was created for account #: ' . $this->_customerSession->getCustomer()->getData()['recurly_account_code'] ) );
;
	}
}