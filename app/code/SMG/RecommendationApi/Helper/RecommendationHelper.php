<?php

namespace SMG\RecommendationApi\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;

class RecommendationHelper extends AbstractHelper
{

	const CONFIG_RECOMMENDATIONS_ACTIVE_PATH = 'recommendations/settings/active';
	const CONFIG_RECOMMENDATIONS_NEW_QUIZ_PATH = 'recommendations/api/new';
	const CONFIG_RECOMMENDATIONS_SAVE_QUIZ_PATH = 'recommendations/api/save';
	const CONFIG_RECOMMENDATIONS_QUIZ_RESULT_PATH = 'recommendations/api/result';
	const CONFIG_RECOMMENDATIONS_COMPLETED_QUIZ_PATH = 'recommendations/api/previous';
	const CONFIG_RECOMMENDATIONS_MAP_TO_USER_PATH = 'recommendations/api/map';

	/**
	 * Check whether Recommendations is active and ready to use
	 * 
	 * @param null $store_id
	 * @return bool
	 */
	public function isActive($store_id = null)
	{
		$active = $this->scopeConfig->getValue(
			self::CONFIG_RECOMMENDATIONS_ACTIVE_PATH,
			ScopeInterface::SCOPE_STORE,
			$store_id
		);

		return $active;
	}

	/**
	 * Return new quiz api path
	 * 
	 * @param null $store_id
	 * @return string
	 */
	public function getNewQuizApiPath($store_id = null)
	{
		return $this->scopeConfig->getValue(
			self::CONFIG_RECOMMENDATIONS_NEW_QUIZ_PATH,
			ScopeInterface::SCOPE_STORE,
			$store_id
		);
	}

	/**
	 * Returns save quiz api path
	 * 
	 * @param null $store_id
	 * @return string
	 */
	public function getSaveQuizApiPath($store_id = null)
	{
		return $this->scopeConfig->getValue(
			self::CONFIG_RECOMMENDATIONS_SAVE_QUIZ_PATH,
			ScopeInterface::SCOPE_STORE,
			$store_id
		);
	}

	/**
	 * Returns quiz result api path
	 * 
	 * @param null $store_id
	 * @return string
	 */
	public function getQuizResultApiPath($store_id = null)
	{
		return $this->scopeConfig->getValue(
			self::CONFIG_RECOMMENDATIONS_QUIZ_RESULT_PATH,
			ScopeInterface::SCOPE_STORE,
			$store_id
		);
	}

	/**
	 * Return completed quizzes api path
	 * 
	 * @param null $store_id
	 * @return string
	 */
	public function getCompletedQuizApiPath($store_id = null)
	{
		return $this->scopeConfig->getValue(
			self::CONFIG_RECOMMENDATIONS_COMPLETED_QUIZ_PATH,
			ScopeInterface::SCOPE_STORE,
			$store_id
		);
	}

    /**
     * Return map to user path
     *
     * @param null $store_id
     * @return string
     */
    public function getMapToUserPath($store_id = null)
    {
        return $this->scopeConfig->getValue(
            self::CONFIG_RECOMMENDATIONS_MAP_TO_USER_PATH,
            ScopeInterface::SCOPE_STORE,
            $store_id
        );
    }

	/**
     * cURL wrapper
     * 
     * @param string $url
     * @param string $method
     * @return array
     */
    public function request( $url, $data, $method = '' )
    {
        if( ! empty( $url ) ) {
            try {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, TRUE);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
                curl_setopt($ch, CURLOPT_TIMEOUT, 45);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER,TRUE);
                if( $method == 'POST' ) {
                    curl_setopt($ch, CURLOPT_POST, TRUE);
                    if( ! empty( $data ) ) {
                        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
                    }
                } elseif( $method == 'PUT' ) {
                    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PUT");
                    if( ! empty( $data ) ) {
                        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
                    }
                } else {
                    curl_setopt($ch, CURLOPT_POST, FALSE);
                }
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

        return;
    }

}