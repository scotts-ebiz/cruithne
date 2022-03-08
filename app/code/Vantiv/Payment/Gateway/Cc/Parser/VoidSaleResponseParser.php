<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Gateway\Cc\Parser;

use Vantiv\Payment\Gateway\Common\Parser\AbstractResponseParser;

/**
 * Response wrapper implementation
 */
class VoidSaleResponseParser extends AbstractResponseParser
{
    /**
     * Const for <voidResponse> XML node
     *
     * @var string
     */
    const VOID_SALE_RESPONSE_NODE = 'voidResponse';

    /**
     * Get Void sale xpath prefix
     *
     * @return string
     */
    public function getPathPrefix()
    {
        return self::VOID_SALE_RESPONSE_NODE;
    }
}
