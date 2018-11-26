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
class EcheckSalesResponseParser extends AbstractResponseParser
{
    /**
     * Const for <echeckSalesResponse> XML node.
     *
     * @var string
     */
    const ECHECK_SALES_RESPONSE_NODE = 'echeckSalesResponse';

    /**
     * Get echeck sales response path prefix.
     *
     * @return string
     */
    public function getPathPrefix()
    {
        return self::ECHECK_SALES_RESPONSE_NODE;
    }
}
