<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Gateway\Recurring;

use Vantiv\Payment\Gateway\Common\Client\HttpClient;
use Vantiv\Payment\Gateway\Recurring\Builder\CreatePlanBuilder as Builder;
use Vantiv\Payment\Gateway\Common\Parser\ResponseParserInterface;
use Magento\Payment\Gateway\Command\CommandException;
use Vantiv\Payment\Gateway\Common\AbstractCustomCommand;
use Vantiv\Payment\Gateway\Recurring\Config\VantivSubscriptionConfig;

class CreatePlanCommand extends AbstractCustomCommand
{
    /**
     * Create Plan request builder.
     *
     * @var Builder
     */
    private $builder;

    /**
     * @var Parser\CreatePlanResponseParserFactory
     */
    private $responseParserFactory;

    /**
     * @var SubjectReader
     */
    private $reader;

    /**
     * Constructor.
     *
     * @param HttpClient $client
     * @param VantivSubscriptionConfig $config
     * @param Builder $builder
     * @param Parser\CreatePlanResponseParserFactory $createPlanResponseParserFactory
     * @param SubjectReader $reader
     */
    public function __construct(
        HttpClient $client,
        VantivSubscriptionConfig $config,
        Builder $builder,
        Parser\CreatePlanResponseParserFactory $createPlanResponseParserFactory,
        SubjectReader $reader
    ) {
        parent::__construct($client, $config);

        $this->builder = $builder;
        $this->responseParserFactory = $createPlanResponseParserFactory;
        $this->reader = $reader;
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

        /** @var Parser\CreatePlanResponseParserFactory $parser */
        $parser = $this->responseParserFactory->create(['xml' => $response]);

        if ($parser->getResponse() !== ResponseParserInterface::PAYMENT_APPROVED) {
            throw new CommandException(__($parser->getMessage()));
        }

        $this->reader->readPlan($subject)->setLitleTxnId($parser->getLitleTxnId());
    }
}
