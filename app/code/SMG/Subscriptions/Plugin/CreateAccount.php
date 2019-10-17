<?php

namespace SMG\Subscriptions\Plugin;

use SMG\Subscriptions\Config\RecurlyConfig;
use Recurly_Account;
use Recurly_Client;

class CreateAccount
{

	/**
     * Payment configuration instance.
     *
     * @var RecurlyConfig
     */
    private $config = null;

	public function __construct(
		RecurlyConfig $config
	)
	{
		$this->config = $config;
	}

	/**
     * Get payment configuration instance.
     *
     * @return RecurlyConfig
     */
    private function getConfig()
    {
        return $this->config;
    }

    /**
     * Register a Recurly account after a
     * successfull customer registration
     * 
     */
	public function afterExecute(\Magento\Customer\Controller\Account\CreatePost $subject, $proceed)
	{

		Recurly_Client::$apiKey = $this->getConfig()->getValue('apikey');
		Recurly_Client::$subdomain = $this->getConfig()->getValue('subdomain');

		try {
			$account = new Recurly_Account($_POST['email']);
			$account->email = $_POST['email'];
			$account->first_name = $_POST['firstname'];
			$account->last_name = $_POST['lastname'];

			$account->create();
		} catch(Recurly_ValidationError $e) {
			echo "Invalid account: $e";
		}

		return $proceed;
	}
}