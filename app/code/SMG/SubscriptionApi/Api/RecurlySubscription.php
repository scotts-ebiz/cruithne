<?php

namespace SMG\SubscriptionApi\Api;

use Exception;
use Magento\Framework\Session\SessionManagerInterface as CoreSession;
use Psr\Log\LoggerInterface;
use SMG\SubscriptionApi\Api\Interfaces\RecurlyInterface;
use SMG\SubscriptionApi\Helper\CancelHelper;
use SMG\SubscriptionApi\Helper\SeasonalHelper;
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
     * @var LoggerInterface
     */
    protected $_logger;

    /**
     * @var CancelHelper
     */
    protected $_cancelHelper;

    /**
     * @var SeasonalHelper
     */
    protected $_seasonalHelper;

    /**
     * RecurlySubscription constructor.
     * @param RecurlySubscriptionModel $recurlySubscriptionModel
     * @param SubscriptionModel $subscriptionModel
     * @param CoreSession $coreSession
     * @param LoggerInterface $logger
     * @param SeasonalHelper $seasonalHelper
     * @param CancelHelper $cancelHelper
     */
    public function __construct(
        RecurlySubscriptionModel $recurlySubscriptionModel,
        SubscriptionModel $subscriptionModel,
        CoreSession $coreSession,
        LoggerInterface $logger,
        SeasonalHelper $seasonalHelper,
        CancelHelper $cancelHelper
    ) {
        $this->_recurlySubscriptionModel = $recurlySubscriptionModel;
        $this->_subscriptionModel = $subscriptionModel;
        $this->_coreSession = $coreSession;
        $this->_logger = $logger;
        $this->_seasonalHelper = $seasonalHelper;
        $this->_cancelHelper = $cancelHelper;
    }

    /**
     * Create new Recurly subscription for the customer. Use it's existing Recurly account if there is one,
     * otherwise create new Recurly account for the customer
     *
     * @param string $token
     * @return string|array
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
            $this->_logger->error($e->getMessage());

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
        // Cancel Recurly Subscriptions
        try {
            $this->_cancelHelper->cancelSubscriptions(true, true);
        } catch (Exception $e) {
            $this->_logger->error($e->getMessage());

            return json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }

        return json_encode([
            'success' => true,
            'message' => 'Subscriptions successfully cancelled.'
        ]);
    }

    /**
     * Process seasonal invoices sent from Recurly
     *
     * @return array
     *
     * @throws Exception
     * @api
     */
    public function processSeasonalInvoice()
    {
        $this->_seasonalHelper->processSeasonalOrders();
    }
}
