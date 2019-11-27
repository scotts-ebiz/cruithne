<?php

namespace SMG\RecommendationQuiz\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;

class RecommendationQuizHelper extends AbstractHelper
{

	const CONFIG_RECOMMENDATION_QUIZ_GOOGLE_MAPS_API = 'recommendation/quiz/googlemapsapi';

	/**
	 * Return the Google Maps API Key
	 * 
	 * @param null $store_id
	 * @return string
	 */
	public function getGoogleMapsApiKey($store_id = null)
	{
		return $this->scopeConfig->getValue(
			self::CONFIG_RECOMMENDATION_QUIZ_GOOGLE_MAPS_API,
			ScopeInterface::SCOPE_STORE,
			$store_id
		);
	}

}