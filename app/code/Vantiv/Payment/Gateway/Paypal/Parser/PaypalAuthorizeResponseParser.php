<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Gateway\Paypal\Parser;

use Vantiv\Payment\Gateway\Common\Parser\AbstractResponseParser;

/**
 * Authorize response wrapper implementation.
 */
class PaypalAuthorizeResponseParser extends AbstractResponseParser
{
    /**
     * Const for <authorizationResponse> XML node.
     *
     * @var string
     */
    const PAYPAL_AUTHORIZE_RESPONSE_NODE = 'authorizationResponse';

    /**
     * Get paypal authorize verification xpath prefix.
     *
     * @return string
     */
    public function getPathPrefix()
    {
        return self::PAYPAL_AUTHORIZE_RESPONSE_NODE;
    }
}
