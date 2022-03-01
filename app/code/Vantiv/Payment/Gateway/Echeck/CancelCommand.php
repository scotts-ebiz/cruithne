<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Gateway\Echeck;

use Magento\Payment\Gateway\CommandInterface;

/**
 * Cancel command implementation.
 */
class CancelCommand implements CommandInterface
{
    /**
     * Void command instance.
     *
     * @var VoidCommand
     */
    private $voidCommand = null;

    /**
     * Constructor.
     *
     * @param VoidCommand $voidCommand
     */
    public function __construct(VoidCommand $voidCommand)
    {
        $this->voidCommand = $voidCommand;
    }

    /**
     * Void command getter.
     *
     * @return VoidCommand
     */
    private function getVoidCommand()
    {
        return $this->voidCommand;
    }

    /**
     * Execute command.
     *
     * @param array $subject
     * @return void
     */
    public function execute(array $subject)
    {
        $this->getVoidCommand()->execute($subject);
    }
}
