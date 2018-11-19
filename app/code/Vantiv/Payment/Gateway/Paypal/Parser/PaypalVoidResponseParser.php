<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Gateway\Paypal\Parser;

use Vantiv\Payment\Gateway\Common\Parser\AbstractResponseParser;

/**
 * Void response wrapper implementation.
 */
class PaypalVoidResponseParser extends AbstractResponseParser
{
    /**
     * Const for <authReversalResponse> XML node.
     *
     * @var string
     */
    const PAYPAL_VOID_RESPONSE_NODE = 'authReversalResponse';

    /**
     * Get paypal authorize verification xpath prefix.
     *
     * @return string
     */
    public function getPathPrefix()
    {
        return self::PAYPAL_VOID_RESPONSE_NODE;
    }
}
