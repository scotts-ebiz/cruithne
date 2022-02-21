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
class SaleResponseParser extends AbstractResponseParser
{
    /**
     * Const for <saleResponse> XML node.
     *
     * @var string
     */
    const SALE_RESPONSE_NODE = 'saleResponse';

    /**
     * Get sale node path prefix.
     *
     * @return string
     */
    public function getPathPrefix()
    {
        return self::SALE_RESPONSE_NODE;
    }
}
