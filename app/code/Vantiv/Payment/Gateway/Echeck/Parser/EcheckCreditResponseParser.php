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
class EcheckCreditResponseParser extends AbstractResponseParser
{
    /**
     * Const for <echeckCreditResponse> XML node.
     *
     * @var string
     */
    const ECHECK_CREDIT_RESPONSE_NODE = 'echeckCreditResponse';

    /**
     * Get echeck credit (refund) path prefix.
     *
     * @return string
     */
    public function getPathPrefix()
    {
        return self::ECHECK_CREDIT_RESPONSE_NODE;
    }
}
