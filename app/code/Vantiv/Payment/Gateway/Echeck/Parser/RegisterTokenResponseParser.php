<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Gateway\Echeck\Parser;

use Vantiv\Payment\Gateway\Common\Parser\AbstractResponseParser;

/**
 * Response wrapper implementation.
 */
class RegisterTokenResponseParser extends AbstractResponseParser
{
    /**
     * Const for <registerTokenResponse> XML node.
     *
     * @var string
     */
    const REGISTER_TOKEN_RESPONSE_NODE = 'registerTokenResponse';

    /**
     * Get token response path prefix.
     *
     * @return string
     */
    public function getPathPrefix()
    {
        return self::REGISTER_TOKEN_RESPONSE_NODE;
    }

    /**
     * Get 'litleToken' value.
     *
     * @return string
     */
    public function getLitleToken()
    {
        return $this->getValue('litleToken');
    }

    /**
     * Get token response code.
     *
     * @return string
     */
    public function getTokenResponseCode()
    {
        return $this->getResponse();
    }

    /**
     * Get token response message.
     *
     * @return string
     */
    public function getTokenMessage()
    {
        return $this->getMessage();
    }
}
