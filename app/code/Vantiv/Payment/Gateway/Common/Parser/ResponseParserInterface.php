<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Gateway\Common\Parser;

/**
 * Vantiv XML response parser.
 *
 * @api
 */
interface ResponseParserInterface
{
    /**
     * Common payment success response code.
     *
     * @var string
     */
    const PAYMENT_APPROVED = '000';

    /**
     * Unsufficient funds response.
     *
     * @var string
     */
    const INSUFFICIENT_FUNDS = '110';

    /**
     * Token response success code.
     *
     * @var string
     */
    const TOKEN_SUCCESSFULLY_REGISTERED = '801';

    /**
     * Token duplicate response code.
     *
     * @var string
     */
    const TOKEN_PREVIOUSLY_REGISTERED = '802';

    /**
     * No transaction found response.
     *
     * @var string
     */
    const NO_TRANSACTION_FOUND = '360';

    /**
     * Invalid account number response.
     *
     * @var string
     */
    const INVALID_ACCOUNT_NUMBER = '301';

    /**
     * Get response code.
     *
     * @return string
     */
    public function getResponse();

    /**
     * Get response message.
     *
     * @return string
     */
    public function getMessage();

    /**
     * Get Litle transacrtion ID.
     *
     * @return string
     */
    public function getLitleTxnId();

    /**
     * Get <responseTime> value.
     *
     * @return string
     */
    public function getResponseTime();

    /**
     * Get array of transaction data.
     *
     * @return string[]
     */
    public function toTransactionRawDetails();
}
