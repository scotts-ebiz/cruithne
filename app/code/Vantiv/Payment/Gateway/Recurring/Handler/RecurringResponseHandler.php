<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Vantiv\Payment\Gateway\Recurring\Handler;

use Magento\Payment\Gateway\Command\CommandException;
use Vantiv\Payment\Gateway\Recurring\SubjectReader;
use Vantiv\Payment\Gateway\Common\Parser\AbstractResponseParser as Parser;

class RecurringResponseHandler
{
    /**
     * Subject reader.
     *
     * @var SubjectReader
     */
    private $reader = null;

    /**
     * @param SubjectReader $reader
     */
    public function __construct(SubjectReader $reader)
    {
        $this->reader = $reader;
    }

    /**
     * Set vantiv subscription id into subscription object.
     *
     * @param array $subject
     * @param Parser $parser
     * @throws CommandException
     */
    public function handle(array $subject, Parser $parser)
    {
        $subscription = $this->reader->readPaymentDataObject($subject)->getPayment()->getSubscription();

        if (!$subscription) {
            return;
        }

        $subscriptionId = $parser->getRecurringResponseSubscriptionId();
        if (!$subscriptionId) {
            throw new CommandException(__('Transaction has been declined. Please try again later.'));
        }

        $subscription->setVantivSubscriptionId($subscriptionId);
    }
}
