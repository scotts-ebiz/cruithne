<?php

namespace SMG\SubscriptionCheckout\Controller\Account;

class LoginPost
{

	protected $_request;
	protected $_recommendationHelper;
	protected $_customerSession;

	public function __construct(
		\Magento\Framework\App\RequestInterface $request,
		\SMG\RecommendationApi\Helper\RecommendationHelper $recommendationHelper,
		\Magento\Customer\Model\Session $customerSession
	)
	{
		$this->_request = $request;
		$this->_recommendationHelper = $recommendationHelper;
		$this->_customerSession = $customerSession;
	}

	public function afterExecute(
		\Magento\Customer\Controller\Account\LoginPost $subject,
		$result
	)
	{

		if( ! empty( $this->_request->getParam('quiz_id' ) ) ) {
			$this->mapToUser($this->_customerSession->getCustomer()->getId(), $this->_request->getParam('quiz_id'));
		}

		return $result;
	}

	private function mapToUser($user_id, $quiz_id)
    {
        // Make sure we have a path
        if ( ! $this->_recommendationHelper->getMapToUserPath() ) {
            return;
        }

        // Make sure that customer id and quiz id exist
        if (empty($user_id) || empty($quiz_id)) {
            return;
        }

        // Get customer Gigya ID
        $gigyaId = $this->_customerSession->getCustomer()->getGigyaUid();

        try {
            $url = filter_var($this->_recommendationHelper->getMapToUserPath(), FILTER_SANITIZE_URL);

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'x-userid: ' . $gigyaId,
            ]);
            curl_setopt($ch, CURLOPT_POSTFIELDS, [$quiz_id]);

            $response = curl_exec($ch);
            curl_close($ch);

            return $response;
        } catch (Exception $e) {
            echo $e->getMessage() . ' (' . $e->getCode() . ')';
        }
    }

}