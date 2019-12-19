<?php

namespace SMG\SubscriptionApi\Api;

use SMG\SubscriptionApi\Exception\SubscriptionException;
use Magento\Framework\Session\SessionManagerInterface as CoreSession;
use SMG\SubscriptionApi\Api\Interfaces\RecurlyInterface;
use SMG\SubscriptionApi\Helper\SubscriptionOrderHelper;
use SMG\SubscriptionApi\Model\RecurlySubscription as RecurlySubscriptionModel;
use SMG\SubscriptionApi\Model\ResourceModel\Subscription as SubscriptionModel;

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
     * @var SubscriptionOrderHelper
     */
    protected $_subscriptionOrderHelper;

    /**
     * RecurlySubscription constructor.
     * @param RecurlySubscriptionModel $recurlySubscriptionModel
     * @param SubscriptionModel $subscriptionModel
     * @param CoreSession $coreSession
     */
    public function __construct(
        RecurlySubscriptionModel $recurlySubscriptionModel,
        SubscriptionModel $subscriptionModel,
        CoreSession $coreSession,
        SubscriptionOrderHelper $subscriptionOrderHelper
    ) {
        $this->_recurlySubscriptionModel = $recurlySubscriptionModel;
        $this->_subscriptionModel = $subscriptionModel;
        $this->_coreSession = $coreSession;
        $this->_subscriptionOrderHelper = $subscriptionOrderHelper;
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
    public function createRecurlySubscription($token)
    {
        try {

            /** @var \SMG\SubscriptionApi\Model\Subscription $subscription */
            $subscription = $this->_subscriptionModel->getSubscriptionByQuizId($this->_coreSession->getQuizId());
            $subscription->createSubscriptionService($token, $this->_recurlySubscriptionModel);

            return json_encode([
                'success' => true,
                'message' => 'Subscription successfully created.'
            ]);
        } catch (\Exception $e) {
            return json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
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
        return $this->_recurlySubscriptionModel->cancelRecurlySubscription();
    }

    /**
     * Process seasonal invoices sent from Recurly
     *
     * @param string $subscriptionId
     * @return array
     *
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @api
     */
    public function processSeasonalInvoice($subscriptionId)
    {
        try {
            $this->_subscriptionOrderHelper->processInvoiceWithSubscriptionId($subscriptionId);
        } catch (SubscriptionException $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }

        return ['success' => true, 'Subscription order has been processed'];
    }
}
