<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Vantiv\Payment\Gateway\GiftCard\Parser;

use Vantiv\Payment\Gateway\Common\Parser\AbstractResponseParser;

/**
 * LoadReversal response wrapper implementation.
 */
class GiftCardLoadReversalResponseParser extends AbstractResponseParser
{
    /**
     * Const for <loadReversalResponse> XML node.
     *
     * @var string
     */
    const GIFT_CARD_ACCOUNT_LOAD_REVERSAL_RESPONSE_NODE = 'loadReversalResponse';

    /**
     * Get load xpath prefix.
     *
     * @return string
     */
    public function getPathPrefix()
    {
        return self::GIFT_CARD_ACCOUNT_LOAD_REVERSAL_RESPONSE_NODE;
    }
}
