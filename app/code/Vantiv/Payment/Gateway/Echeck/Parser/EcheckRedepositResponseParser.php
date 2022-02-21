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
class EcheckRedepositResponseParser extends AbstractResponseParser
{
    /**
     * Const for <echeckRedepositResponse> XML node.
     *
     * @var string
     */
    const ECHECK_REDEPOSIT_RESPONSE_NODE = 'echeckRedepositResponse';

    /**
     * Get echeck re-deposit path prefix.
     *
     * @return string
     */
    public function getPathPrefix()
    {
        return self::ECHECK_REDEPOSIT_RESPONSE_NODE;
    }
}
