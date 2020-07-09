<?php
namespace SMG\SubscriptionApi\Api\Interfaces;

/**
 * @api
 */
interface RecurlyInterface
{
    /**
     * Check existing Recurly subscriptions
     *
     * @return mixed
     *
     * @api
     */
    public function checkRecurlySubscription();

    /**
     * Cancel Recurly subscriptions
     *
     * @param string $cancelReason
     * @return mixed
     *
     * @api
     */
    public function cancelRecurlySubscription($cancelReason);

    /**
     * Process seasonal invoices sent from Recurly
     *
     * @return mixed
     *
     * @api
     */
    public function processSeasonalInvoice();
}
