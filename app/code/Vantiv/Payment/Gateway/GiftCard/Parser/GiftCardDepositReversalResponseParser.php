<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Vantiv\Payment\Gateway\GiftCard\Parser;

use Vantiv\Payment\Gateway\Common\Parser\AbstractResponseParser;

/**
 * DepositReversal response wrapper implementation.
 */
class GiftCardDepositReversalResponseParser extends AbstractResponseParser
{
    /**
     * Const for <depositReversalResponse> XML node.
     *
     * @var string
     */
    const GIFT_CARD_ACCOUNT_DEPOSIT_REVERSAL_RESPONSE_NODE = 'depositReversalResponse';

    /**
     * Get activate xpath prefix.
     *
     * @return string
     */
    public function getPathPrefix()
    {
        return self::GIFT_CARD_ACCOUNT_DEPOSIT_REVERSAL_RESPONSE_NODE;
    }
}
