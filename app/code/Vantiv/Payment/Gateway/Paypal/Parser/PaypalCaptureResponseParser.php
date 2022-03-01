<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Gateway\Paypal\Parser;

use Vantiv\Payment\Gateway\Common\Parser\AbstractResponseParser;

/**
 * Capture response wrapper implementation.
 */
class PaypalCaptureResponseParser extends AbstractResponseParser
{
    /**
     * Const for <captureResponse> XML node.
     *
     * @var string
     */
    const PAYPAL_CAPTURE_RESPONSE_NODE = 'captureResponse';

    /**
     * Get paypal authorize verification xpath prefix.
     *
     * @return string
     */
    public function getPathPrefix()
    {
        return self::PAYPAL_CAPTURE_RESPONSE_NODE;
    }
}
