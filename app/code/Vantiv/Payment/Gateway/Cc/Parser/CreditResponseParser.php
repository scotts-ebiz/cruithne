<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Gateway\Cc\Parser;

use Vantiv\Payment\Gateway\Common\Parser\AbstractResponseParser;

/**
 * Response wrapper implementation.
 */
class CreditResponseParser extends AbstractResponseParser
{
    /**
     * Const for <creditResponse> XML node.
     *
     * @var string
     */
    const CREDIT_RESPONSE_NODE = 'creditResponse';

    /**
     * Get credit node path prefix.
     *
     * @return string
     */
    public function getPathPrefix()
    {
        return self::CREDIT_RESPONSE_NODE;
    }
}
