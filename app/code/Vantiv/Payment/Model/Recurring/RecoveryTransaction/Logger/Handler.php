<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Vantiv\Payment\Model\Recurring\RecoveryTransaction\Logger;

use Magento\Framework\Logger\Handler\Base;

/**
 * Class Handler
 *
 * Custom log handler class
 */
class Handler extends Base
{
    /**
     * @var string
     */
    protected $fileName = '/var/log/vantiv_recovery_transaction.log';
}
