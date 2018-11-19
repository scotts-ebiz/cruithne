<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Gateway\Common\Builder;

use Magento\Framework\Exception\LocalizedException;
use XMLWriter;

/**
 * Vantiv XML request builder.
 *
 * @api
 */
abstract class AbstractLitleOnlineRequestBuilder implements RequestBuilderInterface
{
    /**
     * Litle Online Request default version.
     *
     * @var string
     */
    const DEFAULT_VERSION = '9.8';

    /**
     * Build <litleOnlineRequest> XML document.
     *
     * @param array $subject
     * @return string
     */
    public function build(array $subject)
    {
        /*
         * Prepare document variables.
         */
        $merchant = $this->readMerchant($subject);
        $username = $this->readUsername($subject);
        $password = $this->readPassword($subject);

        /*
         * Prepare child document.
         */
        $xml = $this->buildBody($subject);

        /*
         * Generate resulting document.
         */
        $xml = $this->wrapBody($xml, $merchant, $username, $password);

        return $xml;
    }

    /**
     * Build request body XML.
     *
     * @param array $subject
     * @return string
     * @throws \LogicException
     * @deprecated
     */
    public function buildBody(array $subject)
    {
        throw new \LogicException('"buildBody" method is deprecated!');
    }

    /**
     * Read API merchant ID.
     *
     * @param array $subject
     * @return string
     */
    abstract protected function readMerchant(array $subject);

    /**
     * Read API user.
     *
     * @param array $subject
     * @return string
     */
    abstract protected function readUsername(array $subject);

    /**
     * Read API password.
     *
     * @param array $subject
     * @return string
     */
    abstract protected function readPassword(array $subject);

    /**
     * Get all necessary Authentication Data as array.
     *
     * @param array $subject
     * @return array
     */
    protected function getAuthenticationData(array $subject = [])
    {
        return [
            'merchantId'   => $this->readMerchant($subject),
            'user'         => $this->readUsername($subject),
            'password'     => $this->readPassword($subject),
        ];
    }

    /**
     * Get a unique identifier assigned by the presenter and mirrored back in the response.
     *
     * @return string
     */
    protected function getId()
    {
        return uniqid();
    }

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
    protected function wrapBody($xml, $merchant, $username, $password, $version = self::DEFAULT_VERSION)
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
