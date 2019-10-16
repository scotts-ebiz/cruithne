<?php

namespace SMG\Subscriptions\Plugin;

use Recurly_Account;
use Recurly_Client;

class CreateAccount
{

	protected $_helperData;

	public function __construct(
		\SMG\Subscriptions\Helper\Data $helperData
	)
	{
		$this->helperData = $helperData;
	}

	public function afterExecute(\Magento\Customer\Controller\Account\CreatePost $subject, $proceed)
	{

		Recurly_Client::$apiKey = $this->helperData->getApiKey();
		Recurly_Client::$subdomain = $this->helperData->getSubdomain();

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