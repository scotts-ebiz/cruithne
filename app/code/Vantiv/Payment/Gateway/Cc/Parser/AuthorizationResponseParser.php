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
class AuthorizationResponseParser extends AbstractResponseParser
{
    /**
     * Const for <authorizationResponse> XML node.
     *
     * @var string
     */
    const AUTHORIZATION_RESPONSE_NODE = 'authorizationResponse';

    /**
     * Get authorization response path prefix.
     *
     * @return string
     */
    public function getPathPrefix()
    {
        return self::AUTHORIZATION_RESPONSE_NODE;
    }
}
