<?php

namespace SMG\LoginRedirection\Controller;

class LoginPost
{

	/**
	 * Redirect customer to checkout page after login
	 */
	public function afterExecute(
		\Magento\Customer\Controller\Account\LoginPost $subject,
		$result
	)
	{
		$result->setPath('checkout');

		return $result;
	}

}