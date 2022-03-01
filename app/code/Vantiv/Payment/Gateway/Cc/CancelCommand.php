<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Gateway\Cc;

use Magento\Payment\Gateway\CommandInterface;

/**
 * Cancel command implementation.
 */
class CancelCommand implements CommandInterface
{
    /**
     * @var VoidCommand
     */
    private $voidCommand;

    /**
     * @param VoidCommand $voidCommand
     */
    public function __construct(VoidCommand $voidCommand)
    {
        $this->voidCommand = $voidCommand;
    }

    /**
     * @inheritDoc
     */
    public function execute(array $commandSubject)
    {
        $this->voidCommand->execute($commandSubject);
    }
}
