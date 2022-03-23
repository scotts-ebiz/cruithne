<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Gateway\Recurring\Parser;

use Vantiv\Payment\Gateway\Common\Parser\AbstractResponseParser;

/**
 * Response wrapper implementation.
 */
class CancelSubscriptionResponseParser extends AbstractResponseParser
{
    /**
     * Const for <cancelSubscriptionResponse> XML node.
     *
     * @var string
     */
    const CANCEL_SUBSCRIPTION_RESPONSE_NODE = 'cancelSubscriptionResponse';

    /**
     * Get credit node path prefix.
     *
     * @return string
     */
    public function getPathPrefix()
    {
        return self::CANCEL_SUBSCRIPTION_RESPONSE_NODE;
    }
}
