<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Gateway\Common\Builder;

use XMLWriter;

/**
 * Vantiv XML request wrapper.
 *
 * @api
 */
class LitleOnlineRequestWrapper
{
    /**
     * Litle Online Request default version.
     *
     * @var string
     */
    const DEFAULT_VERSION = '9.8';

    /**
     * Wrap <litleOnlineRequest> XML document.
     *
     * @param string $xml
     * @param string $merchant
     * @param string $username
     * @param string $password
     * @param string $version
     * @return string
     */
    public function wrap($xml, $merchant, $username, $password, $version = self::DEFAULT_VERSION)
    {
        /*
         * Generate document.
         */
        $writer = new XMLWriter();
        $writer->openMemory();
        $writer->setIndent(true);
        $writer->setIndentString(str_repeat(' ', 4));
        $writer->startDocument('1.0', 'UTF-8');
        $writer->startElement('litleOnlineRequest');
        $writer->writeAttribute('version', $version);
        $writer->writeAttribute('xmlns', 'http://www.litle.com/schema');
        $writer->writeAttribute('merchantId', $merchant);
        {
            $writer->startElement('authentication');
            {
                $writer->startElement('user');
                $writer->text($username);
                $writer->endElement();

                $writer->startElement('password');
                $writer->text($password);
                $writer->endElement();
            }
            $writer->endElement();

            $writer->writeRaw($xml);
        }
        $writer->endElement();
        $writer->endDocument();
        $xml = $writer->outputMemory();

        return $xml;
    }
}
