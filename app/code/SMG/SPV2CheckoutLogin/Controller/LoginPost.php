<?php

namespace SMG\SPV2CheckoutLogin\Controller;

class LoginPost
{

	protected $_request;

	public function __construct(
		\Magento\Framework\App\RequestInterface $request
	)
	{
		$this->_request = $request;
	}

	public function afterExecute(
		\Magento\Customer\Controller\Account\LoginPost $subject,
		$result
	)
	{
		//print_r( $this->_request->getParam('quiz_id') );

		return $result;
	}

}