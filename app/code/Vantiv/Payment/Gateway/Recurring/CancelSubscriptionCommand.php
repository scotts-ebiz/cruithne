<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Gateway\Recurring;

use Vantiv\Payment\Gateway\Common\AbstractCustomCommand;
use Vantiv\Payment\Gateway\Common\Client\HttpClient;
use Vantiv\Payment\Gateway\Recurring\Builder\CancelSubscriptionBuilder as Builder;
use Vantiv\Payment\Gateway\Common\Parser\ResponseParserInterface;
use Magento\Payment\Gateway\Command\CommandException;
use Vantiv\Payment\Gateway\Recurring\Config\VantivSubscriptionConfig;

class CancelSubscriptionCommand extends AbstractCustomCommand
{
    /**
     * Cancel Subscription request builder.
     *
     * @var Builder
     */
    private $builder;

    /**
     * @var Parser\CancelSubscriptionResponseParserFactory
     */
    private $responseParserFactory;

    /**
     * Constructor.
     *
     * @param HttpClient $client
     * @param VantivSubscriptionConfig $config
     * @param Builder $builder
     * @param Parser\CancelSubscriptionResponseParserFactory $cancelSubscriptionResponseParserFactory
     */
    public function __construct(
        HttpClient $client,
        VantivSubscriptionConfig $config,
        Builder $builder,
        Parser\CancelSubscriptionResponseParserFactory $cancelSubscriptionResponseParserFactory
    ) {
        parent::__construct($client, $config);
        $this->builder = $builder;
        $this->responseParserFactory = $cancelSubscriptionResponseParserFactory;
    }

    /**
     * Execute a command.
     *
     * @param array $subject
     * @return integer
     * @throws CommandException
     */
    public function execute(array $subject)
    {
        $request = $this->builder->build($subject);
        $response = $this->call($request);

        /** @var Parser\CancelSubscriptionResponseParserFactory $parser */
        $parser = $this->responseParserFactory->create(['xml' => $response]);

        if ($parser->getResponse() !== ResponseParserInterface::PAYMENT_APPROVED) {
            throw new CommandException(__($parser->getMessage()));
        }
    }
}
