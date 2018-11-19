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
class VoidResponseParser extends AbstractResponseParser
{
    /**
     * Const for <authReversalResponse> XML node.
     *
     * @var string
     */
    const VOID_RESPONSE_NODE = 'authReversalResponse';

    /**
     * Get Void xpath prefix.
     *
     * @return string
     */
    public function getPathPrefix()
    {
        return self::VOID_RESPONSE_NODE;
    }
}
