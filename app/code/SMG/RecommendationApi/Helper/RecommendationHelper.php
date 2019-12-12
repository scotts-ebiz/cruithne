<?php

namespace SMG\RecommendationApi\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;

class RecommendationHelper extends AbstractHelper
{
    const CONFIG_RECOMMENDATIONS_ACTIVE_PATH = 'recommendations/module/active';
    const CONFIG_RECOMMENDATIONS_USE_CSRF = 'recommendations/module/usecsrf';
    const CONFIG_RECOMMENDATIONS_NEW_QUIZ_PATH = 'recommendations/api/new';
    const CONFIG_RECOMMENDATIONS_SAVE_QUIZ_PATH = 'recommendations/api/save';
    const CONFIG_RECOMMENDATIONS_QUIZ_RESULT_PATH = 'recommendations/api/result';
    const CONFIG_RECOMMENDATIONS_COMPLETED_QUIZ_PATH = 'recommendations/api/previous';
    const CONFIG_RECOMMENDATIONS_MAP_TO_USER_PATH = 'recommendations/api/map';
    const CONFIG_RECOMMENDATIONS_PRODUCTS_PATH = 'recommendations/api/products';

    /**
     * @var \Magento\Framework\Session\SessionManagerInterface
     */
    protected $_coreSession;

    /**
     * Recommendation API Helper Constructor
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Framework\Session\SessionManagerInterface $coreSession
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\Session\SessionManagerInterface $coreSession
    )
    {
        $this->_coreSession = $coreSession;
        parent::__construct($context);
    }

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
        );
    }

    /**
     * Return array of core and addon products from the quiz result
     * 
     * @param string $quiz_id
     * @return array
     */
    public function getQuizResultProducts($quiz_id)
    {
        $completedQuizUrl = filter_var(
            trim(
                str_replace('{completedQuizId}', $quiz_id, $this->getQuizResultApiPath()),
                '/'
            ),
            FILTER_SANITIZE_URL
        );

        $completedQuizResults = $this->request( $completedQuizUrl, '', 'GET' );
        $allProducts = array();

        foreach( $completedQuizResults['plan']['coreProducts'] as $index => $product ) {
            $allProducts['core'][$index]['season'] = $product['season'];
            $allProducts['core'][$index]['applicationStartDate'] = $product['applicationStartDate'];
            $allProducts['core'][$index]['applicationEndDate'] = $product['applicationEndDate'];
            $allProducts['core'][$index]['sku'] = $product['sku'];
            $allProducts['core'][$index]['quantity'] = $product['quantity'];
        }

        foreach( $completedQuizResults['plan']['addOnProducts'] as $index => $product ) {
            $allProducts['addon'][$index]['season'] = $product['season'];
            $allProducts['addon'][$index]['applicationStartDate'] = $product['applicationStartDate'];
            $allProducts['addon'][$index]['applicationEndDate'] = $product['applicationEndDate'];
            $allProducts['addon'][$index]['sku'] = $product['sku'];
            $allProducts['addon'][$index]['quantity'] = $product['quantity'];
        }

        // Save products to session
        $this->_coreSession->setOrderProducts( $allProducts );

        return $allProducts;
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
}
