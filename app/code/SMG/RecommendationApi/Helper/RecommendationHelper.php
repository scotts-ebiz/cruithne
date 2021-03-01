<?php

namespace SMG\RecommendationApi\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;

class RecommendationHelper extends AbstractHelper
{
    const CONFIG_RECOMMENDATIONS_QUIZ_API_KEY = 'recommendations/api/key';
    const CONFIG_RECOMMENDATIONS_ACTIVE_PATH = 'recommendations/module/active';
    const CONFIG_RECOMMENDATIONS_USE_CSRF = 'recommendations/module/usecsrf';
    const CONFIG_RECOMMENDATIONS_NEW_QUIZ_PATH = 'recommendations/api/new';
    const CONFIG_RECOMMENDATIONS_SAVE_QUIZ_PATH = 'recommendations/api/save';
    const CONFIG_RECOMMENDATIONS_QUIZ_RESULT_PATH = 'recommendations/api/result';
    const CONFIG_RECOMMENDATIONS_COMPLETED_QUIZ_PATH = 'recommendations/api/previous';
    const CONFIG_RECOMMENDATIONS_MAP_TO_USER_PATH = 'recommendations/api/map';
    const CONFIG_RECOMMENDATIONS_PRODUCTS_PATH = 'recommendations/api/products';
    const SUBSCRIPTION_CONFIG_EXPIRED_DAYS = 'smg/subscription/expired_days';

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
     * Get the LSPaaS API key.
     *
     * @param null $storeID
     * @return mixed
     */
    public function getApiKey($storeID = null)
    {
        return $this->scopeConfig->getValue(
            self::CONFIG_RECOMMENDATIONS_QUIZ_API_KEY,
            ScopeInterface::SCOPE_STORE,
            $storeID
        );
    }

    /**
     * Check whether we should use CSRF token checking
     *
     * @param null $store_id
     * @return mixed
     */
    public function useCsrf($store_id = null)
    {
        $useCsrf = $this->scopeConfig->getValue(
            self::CONFIG_RECOMMENDATIONS_USE_CSRF,
            ScopeInterface::SCOPE_STORE,
            $store_id
        );

        return $useCsrf === '1';
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
        ) . "?key={$this->getApiKey()}";
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
        ) . "?key={$this->getApiKey()}";
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
        ) . "?key={$this->getApiKey()}";
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
        ) . "?key={$this->getApiKey()}";
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
        ) . "?key={$this->getApiKey()}";
    }

    /**
     * Return the path to the products flat file.
     *
     * @param null $store_id
     * @return mixed
     */
    public function getProductsPath($store_id = null)
    {
        return $this->scopeConfig->getValue(
            self::CONFIG_RECOMMENDATIONS_PRODUCTS_PATH,
            ScopeInterface::SCOPE_STORE,
            $store_id
        ) . "?key={$this->getApiKey()}";
    }

    /**
     * cURL wrapper
     *
     * @param string $url
     * @param $data
     * @param string $method
     * @return array
     * @throws \Exception
     */
    public function request($url, $data, $method = '')
    {
        if (! empty($url)) {
            try {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
                curl_setopt($ch, CURLOPT_TIMEOUT, 45);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                if ($method == 'POST') {
                    curl_setopt($ch, CURLOPT_POST, true);
                    if (! empty($data)) {
                        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
                    }
                } elseif ($method == 'PUT') {
                    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PUT");
                    if (! empty($data)) {
                        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
                    }
                } else {
                    curl_setopt($ch, CURLOPT_POST, false);
                }
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Content-Type: application/json; charset=utf-8',
                    'Accept: application/json',
                ]);
                $response = curl_exec($ch);

                if (curl_errno($ch)) {
                    throw new \Exception(curl_error($ch));
                }

                curl_close($ch);

                return json_decode($response, true);
            } catch (\Exception $e) {
                throw new \Exception($e);
            }
        }

        return;
    }

    /**
    * Return number of days of quiz expired
    *
    * @param null $store_id
    * @return int
    */
   public function getExpiredDays($store_id = null)
   {
       return $this->scopeConfig->getValue(
           self::SUBSCRIPTION_CONFIG_EXPIRED_DAYS,
           ScopeInterface::SCOPE_STORE,
           $store_id
       );
   }
}
