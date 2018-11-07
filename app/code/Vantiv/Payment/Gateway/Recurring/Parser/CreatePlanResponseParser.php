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
class CreatePlanResponseParser extends AbstractResponseParser
{
    /**
     * Const for <createPlanResponse> XML node.
     *
     * @var string
     */
    const CREATE_PLAN_RESPONSE_NODE = 'createPlanResponse';

    /**
     * Get credit node path prefix.
     *
     * @return string
     */
    public function getPathPrefix()
    {
        return self::CREATE_PLAN_RESPONSE_NODE;
    }
}
