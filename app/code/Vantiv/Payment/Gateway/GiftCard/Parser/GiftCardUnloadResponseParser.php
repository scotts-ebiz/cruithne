<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Vantiv\Payment\Gateway\GiftCard\Parser;

use Vantiv\Payment\Gateway\Common\Parser\AbstractResponseParser;

/**
 * Unload response wrapper implementation.
 */
class GiftCardUnloadResponseParser extends AbstractResponseParser
{
    /**
     * Const for <unloadResponse> XML node.
     *
     * @var string
     */
    const GIFT_CARD_ACCOUNT_UNLOAD_RESPONSE_NODE = 'unloadResponse';

    /**
     * Get load xpath prefix.
     *
     * @return string
     */
    public function getPathPrefix()
    {
        return self::GIFT_CARD_ACCOUNT_UNLOAD_RESPONSE_NODE;
    }
}
