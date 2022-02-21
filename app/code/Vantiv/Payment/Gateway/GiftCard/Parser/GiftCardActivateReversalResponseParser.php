<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Vantiv\Payment\Gateway\GiftCard\Parser;

use Vantiv\Payment\Gateway\Common\Parser\AbstractResponseParser;

/**
 * Activate Reversal response wrapper implementation.
 */
class GiftCardActivateReversalResponseParser extends AbstractResponseParser
{
    /**
     * Const for <activateReversalResponse> XML node.
     *
     * @var string
     */
    const GIFT_CARD_ACCOUNT_ACTIVATE_REVERSAL_RESPONSE_NODE = 'activateReversalResponse';

    /**
     * Get activate xpath prefix.
     *
     * @return string
     */
    public function getPathPrefix()
    {
        return self::GIFT_CARD_ACCOUNT_ACTIVATE_REVERSAL_RESPONSE_NODE;
    }
}
