<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Gateway\Cc;

use Magento\Payment\Gateway\CommandInterface;

/**
 * Accept command implementation.
 */
class AcceptCommand implements CommandInterface
{
    /**
     * Accept payment.
     *
     * @param array $subject
     * @return void
     */
    public function execute(array $subject)
    {
    }
}
