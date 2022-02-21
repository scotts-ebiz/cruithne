<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Vantiv\Payment\Gateway\Recurring;

use Vantiv\Payment\Gateway\Common\AbstractCustomCommand;
use Vantiv\Payment\Gateway\Common\Client\HttpClient;
use Vantiv\Payment\Gateway\Recurring\Builder\VoidRecoveryTransactionBuilder as Builder;
use Vantiv\Payment\Gateway\Common\Parser\ResponseParserInterface;
use Magento\Payment\Gateway\Command\CommandException;
use Vantiv\Payment\Gateway\Recurring\Config\VantivSubscriptionConfig;

class VoidRecoveryTransactionCommand extends AbstractCustomCommand
{
    /**
     * Void request builder.
     *
     * @var Builder
     */
    private $builder;

    /**
     * @var \Vantiv\Payment\Gateway\Cc\Parser\VoidSaleResponseParserFactory
     */
    private $responseParserFactory;

    /**
     * Constructor.
     *
     * @param HttpClient $client
     * @param VantivSubscriptionConfig $config
     * @param Builder $builder
     * @param \Vantiv\Payment\Gateway\Cc\Parser\VoidSaleResponseParserFactory $voidSaleResponseParserFactory
     */
    public function __construct(
        HttpClient $client,
        VantivSubscriptionConfig $config,
        Builder $builder,
        \Vantiv\Payment\Gateway\Cc\Parser\VoidSaleResponseParserFactory $voidSaleResponseParserFactory
    ) {
        parent::__construct($client, $config);
        $this->builder = $builder;
        $this->responseParserFactory = $voidSaleResponseParserFactory;
    }

    /**
     * Execute a command.
     *
     * @param array $subject
     * @throws CommandException
     */
    public function execute(array $subject)
    {
        $request = $this->builder->build($subject);
        $response = $this->call($request);

        /** @var \Vantiv\Payment\Gateway\Cc\Parser\VoidSaleResponseParser $parser */
        $parser = $this->responseParserFactory->create(['xml' => $response]);

        if ($parser->getResponse() !== ResponseParserInterface::PAYMENT_APPROVED) {
            throw new CommandException(__($parser->getMessage()));
        }
    }
}
