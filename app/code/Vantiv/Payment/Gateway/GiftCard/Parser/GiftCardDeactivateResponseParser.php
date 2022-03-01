<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Vantiv\Payment\Gateway\GiftCard\Parser;

use Vantiv\Payment\Gateway\Common\Parser\AbstractResponseParser;

/**
 * Deactivate response wrapper implementation.
 */
class GiftCardDeactivateResponseParser extends AbstractResponseParser
{
    /**
     * Const for <deactivateResponse> XML node.
     *
     * @var string
     */
    const GIFT_CARD_ACCOUNT_DEACTIVATE_RESPONSE_NODE = 'deactivateResponse';

    /**
     * Get deactivate xpath prefix.
     *
     * @return string
     */
    public function getPathPrefix()
    {
        return self::GIFT_CARD_ACCOUNT_DEACTIVATE_RESPONSE_NODE;
    }
}
