<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Gateway\GiftCard\Handler;

use Vantiv\Payment\Gateway\GiftCard\SubjectReader;
use Vantiv\Payment\Gateway\Common\Parser\AbstractResponseParser as Parser;

/**
 * Handle <virtualGiftCardResponse> response data.
 */
class VirtualGiftCardResponseHandler
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
     * Save Virtual Gift Card Account Code
     *
     * @param array $subject
     * @param Parser $parser
     * @return boolean
     */
    public function handle(array $subject, Parser $parser)
    {
        $result = true;
        $virtualGiftCardNumber = $parser->getVirtualGiftCardNumber();

        if ($virtualGiftCardNumber != '') {
            $giftCardAccount = $this->getReader()->readGiftCardAccount($subject);
            $giftCardAccount->setCode($virtualGiftCardNumber);
        }

        return $result;
    }
}
