<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Gateway\Common\Renderer;

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
     * @param string $merchantId
     * @param string $user
     * @param string $password
     * @param string $version
     * @return string
     */
    public function wrap($xml, $merchantId, $user, $password, $version = self::DEFAULT_VERSION)
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
        $writer->writeAttribute('merchantId', $merchantId);
        {
            $writer->startElement('authentication');
            {
                $writer->writeElement('user', $user);
                $writer->writeElement('password', $password);
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
