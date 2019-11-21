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

	private function mapCompletedQuiz($user_id, $quiz_id)
	{
		if( empty( $user_id ) || empty( $quiz_id ) ) {
			return;
		}

		try {
			
			$url = 'https://lspaasdraft.azurewebsites.net/api/completedQuizzes/mapToUser';

			$ch = curl_init();
			curl_setopt( $ch, CURLOPT_URL, $url );
			curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, 'PUT' );
			curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
			curl_setopt( $ch, CURLOPT_HTTPHEADER, array(
				'Content-Type: application/json',
				'x-userid: ' . $user_id,
			) );
			curl_setopt( $ch, CURLOPT_POSTFIELDS, array( $quiz_id ) );

			$response = curl_exec( $ch );
			curl_close( $ch );

			return $response;
		} catch(Exception $e) {
			echo $e->getMessage() . ' (' . $e->getCode() . ')';
		}


	}

}