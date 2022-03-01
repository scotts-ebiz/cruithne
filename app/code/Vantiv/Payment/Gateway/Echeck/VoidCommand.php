<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Vantiv\Payment\Gateway\Echeck;

use Magento\Payment\Gateway\CommandInterface;

/**
 * Void command implementation.
 */
class VoidCommand implements CommandInterface
{
    /**
     * Execute command.
     *
     * @param array $subject
     * @return void
     */
    public function execute(array $subject)
    {
        /*
         * Current command does nothing.
         * eCheck payment does not have real authorization transaction,
         * so we have nothing to void.
         *
         * The only purpose of current command is to provide compatibility
         * with Magento fulfillment flow.
         *
         * Do not confuse with "echeckVoid" request which can be done against
         * capture/sale transaction.
         */
    }
}
