<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Model\Logger;

use Magento\Framework\Logger\Monolog;

class Logger extends Monolog
{
    /**
     * @param string             $name       The logging channel
     * @param HandlerInterface[] $handlers   Optional stack of handlers, the first one in the array is called first.
     * @param callable[]         $processors Optional array of processors
     */
    public function __construct($name, array $handlers = [], array $processors = [])
    {
        parent::__construct($name, $handlers, $processors);
    }

    /**
     * Adds a log record at the DEBUG level.
     *
     * This method allows for compatibility with common interfaces.
     *
     * @param  string  $message The log message
     * @param  array   $context The log context
     * @return Boolean Whether the record has been processed
     */
    public function debug($message, array $context = [])
    {
        $sanitizedMessage = $this->sanitizeLog($message);

        return parent::debug($sanitizedMessage, $context);
    }

    /**
     * Clear sensitive data from log
     *
     * @param string $message
     * @return string
     */
    private function sanitizeLog($message)
    {
        /**
         * Sanitize user name
         */
        $sanitizedMessage = preg_replace("|<user>.*?</user>|", "<user>****</user>", $message);

        /**
         * Sanitize password
         */
        $sanitizedMessage = preg_replace(
            "|<password>.*?</password>|",
            "<password>****</password>",
            $sanitizedMessage
        );

        /**
         * Sanitize card validation number
         */
        $sanitizedMessage = preg_replace(
            "|<cardValidationNum>.*?</cardValidationNum>|",
            "<cardValidationNum>****</cardValidationNum>",
            $sanitizedMessage
        );

        /**
         * Sanitize credit card number
         */
        $sanitizedMessage = preg_replace(
            "|<number>.*?</number>|",
            "<number>****</number>",
            $sanitizedMessage
        );

        /**
         * Sanitize eCheck account name
         */
        $sanitizedMessage = preg_replace(
            "|<accNum>.*?</accNum>|",
            "<accNum>****</accNum>",
            $sanitizedMessage
        );

        /**
         * Sanitize Gift Card BIN number
         */
        $sanitizedMessage = preg_replace(
            "|<giftCardBin>.*?</giftCardBin>|",
            "<giftCardBin>****</giftCardBin>",
            $sanitizedMessage
        );

        return $sanitizedMessage;
    }
}
