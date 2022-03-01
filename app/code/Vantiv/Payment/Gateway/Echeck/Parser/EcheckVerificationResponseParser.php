<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Gateway\Echeck\Parser;

use Vantiv\Payment\Gateway\Common\Parser\AbstractResponseParser;

/**
 * Response wrapper implementation.
 */
class EcheckVerificationResponseParser extends AbstractResponseParser
{
    /**
     * Const for <echeckVerificationResponse> XML node.
     *
     * @var string
     */
    const ECHECK_VERIFICATION_RESPONSE_NODE = 'echeckVerificationResponse';

    /**
     * Get echeck verification xpath prefix.
     *
     * @return string
     */
    public function getPathPrefix()
    {
        return self::ECHECK_VERIFICATION_RESPONSE_NODE;
    }
}
