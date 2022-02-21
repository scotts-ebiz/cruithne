<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Vantiv\Payment\Gateway\GiftCard\Parser;

use Vantiv\Payment\Gateway\Common\Parser\AbstractResponseParser;

/**
 * Load response wrapper implementation.
 */
class GiftCardLoadResponseParser extends AbstractResponseParser
{
    /**
     * Const for <loadResponse> XML node.
     *
     * @var string
     */
    const GIFT_CARD_ACCOUNT_LOAD_RESPONSE_NODE = 'loadResponse';

    /**
     * Get load xpath prefix.
     *
     * @return string
     */
    public function getPathPrefix()
    {
        return self::GIFT_CARD_ACCOUNT_LOAD_RESPONSE_NODE;
    }
}
