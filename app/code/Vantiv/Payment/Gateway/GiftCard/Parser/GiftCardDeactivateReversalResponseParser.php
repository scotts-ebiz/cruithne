<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Vantiv\Payment\Gateway\GiftCard\Parser;

use Vantiv\Payment\Gateway\Common\Parser\AbstractResponseParser;

/**
 * Deactivate Reversal response wrapper implementation.
 */
class GiftCardDeactivateReversalResponseParser extends AbstractResponseParser
{
    /**
     * Const for <deactivateReversalResponse> XML node.
     *
     * @var string
     */
    const GIFT_CARD_ACCOUNT_DEACTIVATE_REVERSAL_RESPONSE_NODE = 'deactivateReversalResponse';

    /**
     * Get activate xpath prefix.
     *
     * @return string
     */
    public function getPathPrefix()
    {
        return self::GIFT_CARD_ACCOUNT_DEACTIVATE_REVERSAL_RESPONSE_NODE;
    }
}
