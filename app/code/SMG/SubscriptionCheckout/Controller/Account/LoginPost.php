<?php

namespace SMG\SubscriptionCheckout\Controller\Account;

/**
 * Class LoginPost
 * @package SMG\SubscriptionCheckout\Controller\Account
 * @todo Wes this needs jailed
 */
class LoginPost
{

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $_request;

    /**
     * @var \SMG\SubscriptionApi\Helper\SubscriptionHelper
     */
    protected $_subscriptionHelper;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * LoginPost constructor.
     * @param \Magento\Framework\App\RequestInterface $request
     */
    public function __construct(
        \Magento\Framework\App\RequestInterface $request,
        \SMG\SubscriptionApi\Helper\SubscriptionHelper $subscriptionHelper,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->_request = $request;
        $this->_subscriptionHelper = $subscriptionHelper;
        $this->_storeManager = $storeManager;
    }

    /**
     * @param \Magento\Customer\Controller\Account\LoginPost $subject
     * @param $result
     * @return mixed
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function afterExecute(
        \Magento\Customer\Controller\Account\LoginPost $subject,
        $result
    ) {

        if ( $this->_subscriptionHelper->isActive( $this->_storeManager->getStore()->getId() ) ) {
            return $result;
        }
    }

    /**
     * @param $user_id
     * @param $quiz_id
     * @return bool|string|void
     */
    private function mapCompletedQuiz($user_id, $quiz_id)
    {
        if (empty($user_id) || empty($quiz_id)) {
            return;
        }

        try {
            $url = 'https://lspaasdraft.azurewebsites.net/api/completedQuizzes/mapToUser';

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'x-userid: ' . $user_id,
            ]);
            curl_setopt($ch, CURLOPT_POSTFIELDS, [ $quiz_id ]);

            $response = curl_exec($ch);
            curl_close($ch);

            return $response;
        } catch (Exception $e) {
            echo $e->getMessage() . ' (' . $e->getCode() . ')';
        }
    }
}
