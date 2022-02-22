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
class UpdatePlanResponseParser extends AbstractResponseParser
{
    /**
     * Const for <updatePlanResponse> XML node.
     *
     * @var string
     */
    const UPDATE_PLAN_RESPONSE_NODE = 'updatePlanResponse';

    /**
     * Get credit node path prefix.
     *
     * @return string
     */
    public function getPathPrefix()
    {
        return self::UPDATE_PLAN_RESPONSE_NODE;
    }
}
