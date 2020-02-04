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
     * @return array
     *
     * @api
     */
    public function checkRecurlySubscription();

    /**
     * Cancel Recurly subscriptions
     *
     * @return array
     *
     * @api
     */
    public function cancelRecurlySubscription();

    /**
     * Process seasonal invoices sent from Recurly
     *
     * @return array
     *
     * @api
     */
    public function processSeasonalInvoice();
}
