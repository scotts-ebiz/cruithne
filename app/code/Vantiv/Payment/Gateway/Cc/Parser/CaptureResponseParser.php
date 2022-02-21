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
class CaptureResponseParser extends AbstractResponseParser
{
    /**
     * Const for <captureResponse> XML node.
     *
     * @var string
     */
    const CAPTURE_RESPONSE_NODE = 'captureResponse';

    /**
     * Get capture node path prefix.
     *
     * @return string
     */
    public function getPathPrefix()
    {
        return self::CAPTURE_RESPONSE_NODE;
    }
}
