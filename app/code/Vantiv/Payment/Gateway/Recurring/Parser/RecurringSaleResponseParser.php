<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Vantiv\Payment\Gateway\Recurring\Parser;

use Vantiv\Payment\Gateway\Common\Parser\ResponseParserInterface;

class RecurringSaleResponseParser extends \Vantiv\Payment\Gateway\Common\Parser\AbstractResponseParser
{
    /**
     * @param string|\SimpleXMLElement $xml
     */
    public function __construct($xml)
    {
        if ($xml instanceof \SimpleXMLElement) {
            $this->rootNode = $xml;
            $xml = $xml->asXml();
        }

        parent::__construct($xml);
    }

    /**
     * @inheritdoc
     */
    public function getPathPrefix()
    {
        return '';
    }

    /**
     * Retrieve orderId
     *
     * @return string
     */
    public function getOrderId()
    {
        return $this->getValue('orderId');
    }

    /**
     * Check if transaction was successful
     *
     * @return bool
     */
    public function isSuccessful()
    {
        return $this->getResponse() == ResponseParserInterface::PAYMENT_APPROVED;
    }

    /**
     * Retrieve reportGroup attribute
     *
     * @return string
     */
    public function getReportGroup()
    {
        return $this->getRootAttribute('reportGroup');
    }
}
