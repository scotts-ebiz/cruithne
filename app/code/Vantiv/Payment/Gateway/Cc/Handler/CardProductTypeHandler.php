<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Gateway\Cc\Handler;

use Vantiv\Payment\Gateway\Common\SubjectReader;
use Vantiv\Payment\Gateway\Common\Parser\AbstractResponseParser as Parser;

/**
 * Handle <cardProductType> response data.
 */
class CardProductTypeHandler
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
    public function __construct(SubjectReader $reader)
    {
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
     * Set credit card data into payment.
     *
     * @param array $subject
     * @param Parser $parser
     * @return boolean
     */
    public function handle(array $subject, Parser $parser)
    {
        $payment = $this->getReader()->readPayment($subject);

        $cardProductType = $parser->getCardProductType();
        if (!empty($cardProductType)) {
            $payment->setAdditionalInformation('card_product_type', $cardProductType);
        }

        return true;
    }
}
