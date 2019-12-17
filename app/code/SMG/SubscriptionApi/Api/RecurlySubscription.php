<?php

namespace SMG\SubscriptionApi\Api;

use Magento\Framework\Exception\LocalizedException;
use \SMG\SubscriptionApi\Api\Interfaces\RecurlyInterface;
use \Magento\Framework\Session\SessionManagerInterface as CoreSession;
use \SMG\SubscriptionApi\Model\RecurlySubscription as RecurlySubscriptionModel;
use \SMG\SubscriptionApi\Model\ResourceModel\Subscription as SubscriptionModel;

/**
 * Class RecurlySubscription
 * @package SMG\SubscriptionApi\Api
 */
class RecurlySubscription implements RecurlyInterface
{
	/** @var RecurlySubscriptionModel  */
	protected $_recurlySubscriptionModel;

    /** @var SubscriptionModel */
    private $_subscriptionModel;

    /** @var CoreSession */
    private $_coreSession;

    /**
     * RecurlySubscription constructor.
     * @param RecurlySubscriptionModel $recurlySubscriptionModel
     * @param SubscriptionModel $subscriptionModel
     * @param CoreSession $coreSession
     */
	public function __construct(
        RecurlySubscriptionModel $recurlySubscriptionModel,
        SubscriptionModel $subscriptionModel,
        CoreSession $coreSession
	)
	{
		$this->_recurlySubscriptionModel = $recurlySubscriptionModel;
		$this->_subscriptionModel = $subscriptionModel;
		$this->_coreSession = $coreSession;
	}

    /**
     * Create new Recurly subscription for the customer. Use it's existing Recurly account if there is one,
     * otherwise create new Recurly account for the customer
     *
     * @param string $token
     * @return array
     *
     * @api
     */
	public function createRecurlySubscription( $token )
	{
	    try {

            /** @var \SMG\SubscriptionApi\Model\Subscription $subscription */
	        $subscription = $this->_subscriptionModel->getSubscriptionByQuizId( $this->_coreSession->getQuizId() );
	        $subscription->createSubscriptionService( $token, $this->_recurlySubscriptionModel );

            return json_encode(array(
                'success' => true,
                'message' => 'Subscription successfully created.'
            ));
        } catch ( \Exception $e ) {
	        return json_encode(array(
	            'success' => false,
                'message' => $e->getMessage()
            ));
        }
	}

	/**
	 * Check if the customer already has a Recurly subscription
	 *
	 * @api
	 */
	public function checkRecurlySubscription()
	{
	    return $this->_recurlySubscriptionModel->checkRecurlySubscription();
	}

    /**
     * Cancel customer Recurly Subscription
     *
     * @api
     */
    public function cancelRecurlySubscription()
	{
	    // Cancel Recurly Subscriptions
        try {

            // Cancel recurly subscriptions
            $cancelledSubscriptionIds = $this->_recurlySubscriptionModel->cancelRecurlySubscriptions( true, true );

            // Find the master subscription id
            $masterSubscriptionId = null;
            foreach ( $cancelledSubscriptionIds as $planCode => $cancelledSubscriptionId ) {
                if ( in_array( $planCode, ['annual', 'seasonal']) ) {
                    $masterSubscriptionId = $cancelledSubscriptionId;
                }
            }
            if ( is_null( $masterSubscriptionId ) ) {
                throw new LocalizedException( __("Couldn't find the master subscription id.") );
            }

            // Find the subscription
            /** @var \SMG\SubscriptionApi\Model\Subscription $subscription */
            $subscription = $this->_subscriptionModel->getSubscriptionByMasterSubscriptionId( $masterSubscriptionId );

            // Cancel subscription orders
            $subscription->cancelSubscriptions( $this->_recurlySubscriptionModel );

        } catch ( LocalizedException $e ) {
            return json_encode(array(
                'success' => false,
                'message' => $e->getMessage()
            ));
        }

        return json_encode(array(
            'success' => true,
            'message' => 'Subscriptions successfully cancelled.'
        ));
	}

}
