<?php

namespace SMG\Api\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;

class QuizHelper extends AbstractHelper
{

	const CONFIG_RECOMMENDATIONS_ACTIVE_PATH = 'recommendations/settings/active';
	const CONFIG_RECOMMENDATIONS_APIKEY_PATH = 'recommendations/settings/apikey';
	const CONFIG_RECOMMENDATIONS_NEW_QUIZ_PATH = 'recommendations/api/new';
	const CONFIG_RECOMMENDATIONS_SAVE_QUIZ_PATH = 'recommendations/api/save';
	const CONFIG_RECOMMENDATIONS_QUIZ_RESULT_PATH = 'recommendations/api/result';
	const CONFIG_RECOMMENDATIONS_COMPLETED_QUIZ_PATH = 'recommendations/api/previous';

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

		$apikey = $this->scopeConfig->getValue(
			self::CONFIG_RECOMMENDATIONS_APIKEY_PATH,
			ScopeInterface::SCOPE_STORE,
			$store_id
		);

		return $active && $apikey;
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

}