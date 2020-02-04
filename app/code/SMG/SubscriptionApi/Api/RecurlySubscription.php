<?php

namespace SMG\SubscriptionApi\Api;

use Exception;
use Psr\Log\LoggerInterface;
use SMG\SubscriptionApi\Api\Interfaces\RecurlyInterface;
use SMG\SubscriptionApi\Helper\CancelHelper;
use SMG\SubscriptionApi\Helper\SeasonalHelper;
use SMG\SubscriptionApi\Model\RecurlySubscription as RecurlySubscriptionModel;

/**
 * Class RecurlySubscription
 * @package SMG\SubscriptionApi\Api
 */
class RecurlySubscription implements RecurlyInterface
{
    /** @var RecurlySubscriptionModel  */
    protected $_recurlySubscriptionModel;

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
     * @param LoggerInterface $logger
     * @param SeasonalHelper $seasonalHelper
     * @param CancelHelper $cancelHelper
     */
    public function __construct(
        RecurlySubscriptionModel $recurlySubscriptionModel,
        LoggerInterface $logger,
        SeasonalHelper $seasonalHelper,
        CancelHelper $cancelHelper
    ) {
        $this->_recurlySubscriptionModel = $recurlySubscriptionModel;
        $this->_logger = $logger;
        $this->_seasonalHelper = $seasonalHelper;
        $this->_cancelHelper = $cancelHelper;
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
     * @throws Exception
     * @api
     */
    public function processSeasonalInvoice()
    {
        $this->_seasonalHelper->processSeasonalOrders();
    }
}
