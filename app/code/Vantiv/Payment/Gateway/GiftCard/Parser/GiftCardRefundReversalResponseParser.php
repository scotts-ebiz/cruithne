<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Vantiv\Payment\Gateway\GiftCard\Parser;

use Vantiv\Payment\Gateway\Common\Parser\AbstractResponseParser;

/**
 * RefundReversal response wrapper implementation.
 */
class GiftCardRefundReversalResponseParser extends AbstractResponseParser
{
    /**
     * Const for <refundReversalResponse> XML node.
     *
     * @var string
     */
    const GIFT_CARD_ACCOUNT_REFUND_REVERSAL_RESPONSE_NODE = 'refundReversalResponse';

    /**
     * Get activate xpath prefix.
     *
     * @return string
     */
    public function getPathPrefix()
    {
        return self::GIFT_CARD_ACCOUNT_REFUND_REVERSAL_RESPONSE_NODE;
    }
}
