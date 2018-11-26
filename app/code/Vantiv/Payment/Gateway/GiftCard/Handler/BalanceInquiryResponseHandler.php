<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Gateway\GiftCard\Handler;

use Vantiv\Payment\Gateway\GiftCard\SubjectReader;
use Vantiv\Payment\Gateway\Common\Parser\AbstractResponseParser as Parser;

/**
 * Handle <balanceInquiryResponse> response data.
 */
class BalanceInquiryResponseHandler
{
    /**
     * Subject reader.
     *
     * @var SubjectReader
     */
    private $reader = null;

    /**
     * Constructor.
     *
     * @param SubjectReader $reader
     */
    public function __construct(
        SubjectReader $reader
    ) {
        $this->reader = $reader;
    }

    /**
     * Get subject reader.
     *
     * @return SubjectReader
     */
    private function getReader()
    {
        return $this->reader;
    }

    /**
     * Update Gift Card Account Balance
     *
     * @param array $subject
     * @param Parser $parser
     * @return boolean
     */
    public function handle(array $subject, Parser $parser)
    {
        $result = true;

        $giftCardAccount = $this->getReader()->readGiftCardAccount($subject);
        $giftCardBalance = $parser->getGiftCardBalance();

        if ($giftCardBalance != $giftCardAccount->getBalance()) {
            $giftCardAccount->setBalance($giftCardBalance / 100)->save();
        }

        return $result;
    }
}
