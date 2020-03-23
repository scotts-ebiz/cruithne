<?php

namespace SMG\SubscriptionCheckout\Plugin\Controller\Account;

use Gigya\GigyaIM\Helper\GigyaMageHelper;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use SMG\RecommendationApi\Helper\RecommendationHelper;
use SMG\SubscriptionApi\Helper\SubscriptionHelper;
use Gigya\GigyaIM\Helper\CmsStarterKit\sdk\GSException;
use Gigya\GigyaIM\Helper\CmsStarterKit\sdk\GSApiException;
use Zaius\Engage\Helper\Sdk as ZaiusSdk;
use ZaiusSDK\ZaiusException;

/**
 * Class LoginPost
 * @package SMG\SubscriptionCheckout\Controller\Account
 */
class LoginPost
{
    /**
     * @var RequestInterface
     */
    protected $_request;

    /**
     * @var SubscriptionHelper
     */
    protected $_subscriptionHelper;

    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var GigyaMageHelper
     */
    protected $_gigyaMageHelper;

    /**
     * @var RecommendationHelper
     */
    private $_recommendationHelper;

    /**
     * @var SessionManagerInterface
     */
    private $_coreSession;

    /**
     * @var CustomerSession
     */
    private $_customerSession;

    /**
     * @var LoggerInterface
     */
    private $_logger;
    
    /**
     * @var ZaiusSdk
     */
    protected $_sdk;
    
    /**
     * LoginPost constructor.
     * @param RequestInterface $request
     * @param SubscriptionHelper $subscriptionHelper
     * @param StoreManagerInterface $storeManager
     * @param RecommendationHelper $recommendationHelper
     * @param SessionManagerInterface $coreSession
     * @param CustomerSession $customerSession
     * @param LoggerInterface $logger
     * @param GigyaMageHelper $gigyaMageHelper
     */
    public function __construct(
        RequestInterface $request,
        SubscriptionHelper $subscriptionHelper,
        StoreManagerInterface $storeManager,
        RecommendationHelper $recommendationHelper,
        SessionManagerInterface $coreSession,
        CustomerSession $customerSession,
        LoggerInterface $logger,
        GigyaMageHelper $gigyaMageHelper,
        ZaiusSdk $sdk
    ) {
        $this->_request = $request;
        $this->_subscriptionHelper = $subscriptionHelper;
        $this->_storeManager = $storeManager;
        $this->_recommendationHelper = $recommendationHelper;
        $this->_coreSession = $coreSession;
        $this->_customerSession = $customerSession;
        $this->_logger = $logger;
        $this->_gigyaMageHelper = $gigyaMageHelper;
        $this->_sdk = $sdk;
    }

    /**
     * @param \Magento\Customer\Controller\Account\LoginPost $subject
     * @param $result
     * @return mixed
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws GSApiException
     * @throws GSException
     */
    public function afterExecute(
        \Magento\Customer\Controller\Account\LoginPost $subject,
        $result
    ) {
        // if this is a subscription site we do not want them to go to the checkout cart page
        if ($this->_subscriptionHelper->isActive($this->_storeManager->getStore()->getId())) {
            $quizId = $this->_coreSession->getQuizId();
            $zipCode = $this->_coreSession->getZipCode();
            $customer = $this->_customerSession->getCustomer();
            $gigyaId = $customer->getGigyaUid();
            $customer_email = $customer->getData('email');

            if ($quizId && $gigyaId) {
                $this->mapToUser($gigyaId, $quizId);
            }

            if ($gigyaId && $zipCode) {
                $gigyaData['profile']['address'] = $zipCode;
                $this->_gigyaMageHelper->updateGigyaAccount($gigyaId, $gigyaData);
            }
            
            if ( $gigyaId && $customer_email ) {
                try {
                   // Zaius SubscriptionCall
                   $this->zaiusSubscriptionCall($customer_email);
                } catch (Exception $ex) {
                    $this->_logger->error($ex->getMessage());
                    return;
                }
            }
        }

        return $result;
    }

    /**
     * Map the quiz to the user
     *
     * @param string $user_id
     * @param string $quiz_id
     * @return bool|string|void
     * @throws LocalizedException
     * @api
     */
    private function mapToUser($user_id, $quiz_id)
    {

        // Make sure we have a path
        if (!$this->_recommendationHelper->getMapToUserPath()) {
            return;
        }

        if (empty($user_id) || empty($quiz_id)) {
            return;
        }

        try {
            $url = filter_var($this->_recommendationHelper->getMapToUserPath(), FILTER_SANITIZE_URL);

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'x-userid: ' . $user_id,
            ]);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([$quiz_id]));

            $response = curl_exec($ch);

            $httpStatus = null;
            if (! curl_errno($ch)) {
                $httpStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            }

            if (! is_null($httpStatus)) {
                $this->_logger->info('MapToUser called for GigyaUid: ' . $user_id . ' and QuizId: ' . $quiz_id . ' returned with HTTP status: ' . $httpStatus);
            } else {
                $this->_logger->error('MapToUser called and returned error for GigyaUid: ' . $user_id . ' and QuizId: ' . $quiz_id);
            }

            curl_close($ch);

            return $response;
        } catch (\Exception $e) {
            throw new LocalizedException(__($e->getMessage() . ' (' . $e->getCode() . ')'));
        }
    }
    
    /**
     * Customer Subscription to zaius
     * @param $customer_email
     */
    private function zaiusSubscriptionCall($customer_email)
    {
        $zaiusstatus = false;

        // Check Email
        if ($customer_email) {
            // call getsdkclient function
            $zaiusClient = $this->_sdk->getSdkClient();
            
            // take event as a array and add parameters
            $subscription = array();
            $subscription['list_id'] = 'scotts';
            $subscription['email'] = $customer_email;
            $subscription['subscribed'] = true;
            $subscription['acquisition_method'] = 'scotts-program-account';
            $subscription['acquisition_source'] = 'Scotts';
            // get updateSubscription function
            try {
                $zaiusstatus = $zaiusClient->updateSubscription($subscription);
            } catch (ZaiusException $e) {
                $this->_logger->error('A post to Zaius failed during subscription, however, it should not affect the account creation.');
            }

            // check return values from the updateSubscription function
            if (isset($zaiusstatus)) {
                $this->_logger->debug("The customer Email Subscription " . $customer_email . " is subscribed successfully to zaius."); //saved in var/log/debug.log
            } else {
                $this->_logger->error("The customer Email Subscription " . $customer_email . " is failed to zaius.");
            }
        }
    }
}