<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Vantiv\Payment\Gateway\GiftCard\Parser;

use Vantiv\Payment\Gateway\Common\Parser\AbstractResponseParser;

/**
 * BalanceInquiry response wrapper implementation.
 */
class GiftCardBalanceInquiryResponseParser extends AbstractResponseParser
{
    /**
     * Const for <balanceInquiryResponse> XML node.
     *
     * @var string
     */
    const GIFT_CARD_ACCOUNT_BALANCE_INQUIRY_RESPONSE_NODE = 'balanceInquiryResponse';

    /**
     * Get activate xpath prefix.
     *
     * @return string
     */
    public function getPathPrefix()
    {
        return self::GIFT_CARD_ACCOUNT_BALANCE_INQUIRY_RESPONSE_NODE;
    }
}
