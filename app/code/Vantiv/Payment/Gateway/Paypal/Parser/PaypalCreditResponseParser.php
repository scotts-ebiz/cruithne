<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Gateway\Paypal\Parser;

use Vantiv\Payment\Gateway\Common\Parser\AbstractResponseParser;

/**
 * Credit response wrapper implementation.
 */
class PaypalCreditResponseParser extends AbstractResponseParser
{
    /**
     * Const for <creditResponse> XML node.
     *
     * @var string
     */
    const PAYPAL_CREDIT_RESPONSE_NODE = 'creditResponse';

    /**
     * Get paypal authorize verification xpath prefix.
     *
     * @return string
     */
    public function getPathPrefix()
    {
        return self::PAYPAL_CREDIT_RESPONSE_NODE;
    }
}
