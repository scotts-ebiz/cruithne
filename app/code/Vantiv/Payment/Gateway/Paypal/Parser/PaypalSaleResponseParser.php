<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Gateway\Paypal\Parser;

use Vantiv\Payment\Gateway\Common\Parser\AbstractResponseParser;

/**
 * Sale response wrapper implementation.
 */
class PaypalSaleResponseParser extends AbstractResponseParser
{
    /**
     * Const for <saleResponse> XML node.
     *
     * @var string
     */
    const PAYPAL_SALE_RESPONSE_NODE = 'saleResponse';

    /**
     * Get paypal authorize verification xpath prefix.
     *
     * @return string
     */
    public function getPathPrefix()
    {
        return self::PAYPAL_SALE_RESPONSE_NODE;
    }
}
