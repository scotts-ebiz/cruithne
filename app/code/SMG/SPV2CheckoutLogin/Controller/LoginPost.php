<?php

namespace SMG\SPV2CheckoutLogin\Controller;

class LoginPost
{

	public function afterExecute(
		\Magento\Customer\Controller\Account\LoginPost $subject,
		$result
	)
	{
		return $result;
	}

}