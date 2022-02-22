<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Vantiv\Payment\Gateway\GiftCard\Parser;

use Vantiv\Payment\Gateway\Common\Parser\AbstractResponseParser;

/**
 * UnloadReversal response wrapper implementation.
 */
class GiftCardUnloadReversalResponseParser extends AbstractResponseParser
{
    /**
     * Const for <unloadReversalResponse> XML node.
     *
     * @var string
     */
    const GIFT_CARD_ACCOUNT_UNLOAD_REVERSAL_RESPONSE_NODE = 'unloadReversalResponse';

    /**
     * Get load xpath prefix.
     *
     * @return string
     */
    public function getPathPrefix()
    {
        return self::GIFT_CARD_ACCOUNT_UNLOAD_REVERSAL_RESPONSE_NODE;
    }
}
