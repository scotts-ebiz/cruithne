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
class UpdateSubscriptionResponseParser extends AbstractResponseParser
{
    /**
     * Const for <updateSubscriptionResponse> XML node.
     *
     * @var string
     */
    const UPDATE_SUBSCRIPTION_RESPONSE_NODE = 'updateSubscriptionResponse';

    /**
     * Get credit node path prefix.
     *
     * @return string
     */
    public function getPathPrefix()
    {
        return self::UPDATE_SUBSCRIPTION_RESPONSE_NODE;
    }
}
