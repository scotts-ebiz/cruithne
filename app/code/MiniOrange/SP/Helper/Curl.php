<?php

namespace MiniOrange\SP\Helper;

use  MiniOrange\SP\Helper\SPConstants;

/**
 * This class denotes all the cURL related functions.
 */
class Curl
{
	
	public static function create_customer($email, $company, $password, $phone = '', $first_name = '', $last_name = '')
	{
		$url 		 = SPConstants::HOSTNAME . '/moas/rest/customer/add';
		$customerKey = SPConstants::DEFAULT_CUSTOMER_KEY;
		$apiKey 	 = SPConstants::DEFAULT_API_KEY;
		$fields = array (
				'companyName' 	 => $company,
				'areaOfInterest' => SPConstants::AREA_OF_INTEREST,
				'firstname' 	 => $first_name,
				'lastname' 		 => $last_name,
				'email' 		 => $email,
				'phone' 		 => $phone,
				'password' 		 => $password
		);
		$authHeader = self::createAuthHeader($customerKey,$apiKey);
		$response = self::callAPI($url, $fields, $authHeader);
		return $response;
	}
	
	public static function get_customer_key($email, $password) 
	{
		$url 		 = SPConstants::HOSTNAME. "/moas/rest/customer/key";
		$customerKey = SPConstants::DEFAULT_CUSTOMER_KEY;
		$apiKey 	 = SPConstants::DEFAULT_API_KEY;
		$fields = array (
					'email' 	=> $email,
					'password'  => $password
				);
		$authHeader = self::createAuthHeader($customerKey,$apiKey);
		$response = self::callAPI($url, $fields, $authHeader);
		return $response;
	}
	
	public static function check_customer($email)
	{
		$url 		 = SPConstants::HOSTNAME . "/moas/rest/customer/check-if-exists";
		$customerKey = SPConstants::DEFAULT_CUSTOMER_KEY;
		$apiKey 	 = SPConstants::DEFAULT_API_KEY;
		$fields = array(
					'email' 	=> $email,
				);
		$authHeader  = self::createAuthHeader($customerKey,$apiKey);
		$response = self::callAPI($url, $fields, $authHeader);
		return $response;
	}
	
	public static function mo_send_otp_token($auth_type,$email='',$phone='')
	{
		$url 		 = SPConstants::HOSTNAME . '/moas/api/auth/challenge';
		$customerKey = SPConstants::DEFAULT_CUSTOMER_KEY;
		$apiKey 	 = SPConstants::DEFAULT_API_KEY;
		$fields  	 = array(
							'customerKey' 	  => $customerKey,
							'email' 	  	  => $email,
							'phone' 	  	  => $phone,
							'authType' 	  	  => $auth_type,
							'transactionName' => SPConstants::AREA_OF_INTEREST
						);
		$authHeader  = self::createAuthHeader($customerKey,$apiKey);
		$response 	 = self::callAPI($url, $fields, $authHeader);
		return $response;
	}
	
	public static function validate_otp_token($transactionId,$otpToken)
	{
		$url 		 = SPConstants::HOSTNAME . '/moas/api/auth/validate';
		$customerKey = SPConstants::DEFAULT_CUSTOMER_KEY;
		$apiKey 	 = SPConstants::DEFAULT_API_KEY;
		$fields 	 = array(
						'txId'  => $transactionId,
						'token' => $otpToken,
					 );
		$authHeader  = self::createAuthHeader($customerKey,$apiKey);
		$response    = self::callAPI($url, $fields, $authHeader);
		return $response;
	}
	
	public static function submit_contact_us(  $q_email, $q_phone, $query ,$first_name, 
												$last_name, $companyName )
	{
		$url    	  	= SPConstants::HOSTNAME . "/moas/rest/customer/contact-us";
		$query  		= '['.SPConstants::AREA_OF_INTEREST.']: ' . $query;
		$customerKey 	= SPConstants::DEFAULT_CUSTOMER_KEY;
		$apiKey 	 	= SPConstants::DEFAULT_API_KEY;
		$fields = array(
					'firstName'	=> $first_name,
					'lastName'	=> $last_name,
					'company' 	=> $companyName,
					'email' 	=> $q_email,
					'phone'		=> $q_phone,
					'query'		=> $query ,
                    'ccEmail'   => 'saml2support@xecurify.com'
				);
		$authHeader  = self::createAuthHeader($customerKey,$apiKey);
		$response 	 = self::callAPI($url, $fields, $authHeader);
		return TRUE;
	}
	
	public static function forgot_password($email,$customerKey,$apiKey)
	{
		$url 		 = SPConstants::HOSTNAME . '/moas/rest/customer/password-reset';

		$fields 	 = array(
				'email' => $email
		);
		
		$authHeader  = self::createAuthHeader($customerKey,$apiKey);
		$response    = self::callAPI($url, $fields, $authHeader);
		return $response;
	}
	
	
	public static function check_customer_ln($customerKey,$apiKey)
	{
		$url = SPConstants::HOSTNAME . '/moas/rest/customer/license';
		$fields = array(
				'customerId' => $customerKey,
				'applicationName' => SPConstants::APPLICATION_NAME,
				'licenseType' => !MoUtility::micr() ? 'DEMO' : 'PREMIUM',
		);

		$authHeader  = self::createAuthHeader($customerKey,$apiKey);
		$response    = self::callAPI($url, $fields, $authHeader);
		return $response;
	}

	private static function createAuthHeader($customerKey, $apiKey)
	{
		$currentTimestampInMillis = round(microtime(true) * 1000);
		$currentTimestampInMillis = number_format($currentTimestampInMillis, 0, '', '');
	
		$stringToHash = $customerKey . $currentTimestampInMillis . $apiKey;
		$authHeader = hash("sha512", $stringToHash);
	
		$header = array (
				"Content-Type: application/json",
				"Customer-Key: $customerKey",
				"Timestamp: $currentTimestampInMillis",
				"Authorization: $authHeader"
		);
		return $header;
	}
		
	private static function callAPI($url, $jsonData=array(), $headers = array("Content-Type: application/json"))
	{
		// Custom functionality written to be in tune with Magento2 coding standards.
		$curl = new MoCurl();
		$options = array(
			'CURLOPT_FOLLOWLOCATION'=> true,
			'CURLOPT_ENCODING'=> "",
			'CURLOPT_RETURNTRANSFER'=> true,
			'CURLOPT_AUTOREFERER'=> true,
			'CURLOPT_TIMEOUT'=> 0,		
			'CURLOPT_MAXREDIRS'=> 10
		);
		$method = !empty($jsonData) ? 'POST' : 'GET';
		$curl->setConfig($options);
		$curl->write($method,$url,'1.1',$headers,!empty($jsonData) ? json_encode($jsonData) : "");
		$content = $curl->read();
		$curl->close();
		return $content;
	}


	/*===========================================================================================
						THESE ARE PREMIUM PLUGIN SPECIFIC FUNCTIONS
	=============================================================================================*/

	public static function ccl($customerKey,$apiKey)
	{
		$url = SPConstants::HOSTNAME . '/moas/rest/customer/license';
		$fields = array(
				'customerId' 	  => $customerKey,
                'applicationName' => 'magento_saml_premium_plan',
		);
		$authHeader  = self::createAuthHeader($customerKey,$apiKey);
		$response 	 = self::callAPI($url, $fields, $authHeader);
		
		return $response;
	}

	public static function vml($customerKey,$apiKey,$code,$field1)
	{
		$url = SPConstants::HOSTNAME . '/moas/api/backupcode/verify';
		$fields = array (
				'code' => $code ,
				'customerKey' => $customerKey,
				'additionalFields' => array(
						'field1' => $field1
				)
		);
		$authHeader  = self::createAuthHeader($customerKey,$apiKey);
		$response 	 = self::callAPI($url, $fields, $authHeader);
		return $response;
	}

	public static function mius($customerKey,$apiKey,$code) 
	{
		$url 	= SPConstants::HOSTNAME . '/moas/api/backupcode/updatestatus';
		$fields = array ( 
			'code' => $code, 
			'customerKey' => $customerKey
		);
		$authHeader  = self::createAuthHeader($customerKey,$apiKey);
		$response 	 = self::callAPI($url, $fields, $authHeader);
		return $response;
	}
}