<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Vantiv\Payment\Gateway\GiftCard\Parser;

use Vantiv\Payment\Gateway\Common\Parser\AbstractResponseParser;

/**
 * Activate response wrapper implementation.
 */
class GiftCardActivateResponseParser extends AbstractResponseParser
{
    /**
     * Const for <activateResponse> XML node.
     *
     * @var string
     */
    const GIFT_CARD_ACCOUNT_ACTIVATE_RESPONSE_NODE = 'activateResponse';

    /**
     * Get activate xpath prefix.
     *
     * @return string
     */
    public function getPathPrefix()
    {
        return self::GIFT_CARD_ACCOUNT_ACTIVATE_RESPONSE_NODE;
    }
}
