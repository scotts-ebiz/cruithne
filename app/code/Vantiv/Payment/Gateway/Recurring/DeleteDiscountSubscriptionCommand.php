<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Gateway\Recurring;

use Vantiv\Payment\Gateway\Common\AbstractCustomCommand;
use Magento\Payment\Gateway\Command\CommandException;
use Vantiv\Payment\Gateway\Common\Client\HttpClient;
use Vantiv\Payment\Gateway\Recurring\Config\VantivSubscriptionConfig;
use Vantiv\Payment\Gateway\Common\Parser\ResponseParserInterface;
use Vantiv\Payment\Gateway\Recurring\Builder\DeleteDiscountSubscriptionBuilder;
use Vantiv\Payment\Gateway\Recurring\Parser\UpdateSubscriptionResponseParserFactory;

class DeleteDiscountSubscriptionCommand extends AbstractCustomCommand
{
    /**
     * @var DeleteDiscountSubscriptionBuilder
     */
    private $builder;
    /**
     * @var UpdateSubscriptionResponseParserFactory
     */
    private $responseParser;

    /**
     * DeleteDiscountSubscriptionCommand constructor.
     *
     * @param HttpClient $client
     * @param VantivSubscriptionConfig $config
     * @param DeleteDiscountSubscriptionBuilder $builder
     * @param UpdateSubscriptionResponseParserFactory $responseParser
     */
    public function __construct(
        HttpClient $client,
        VantivSubscriptionConfig $config,
        DeleteDiscountSubscriptionBuilder $builder,
        UpdateSubscriptionResponseParserFactory $responseParser
    ) {
        parent::__construct($client, $config);
        $this->builder = $builder;
        $this->responseParser = $responseParser;
    }

    /**
     * Executes command to delete Subscription Discount
     *
     * @param array $subject
     * @throws CommandException
     */
    public function execute(array $subject)
    {
        $request = $this->builder->build($subject);
        $response = $this->call($request);

        $parser = $this->responseParser->create(['xml' => $response]);

        if ($parser->getResponse() !== ResponseParserInterface::PAYMENT_APPROVED) {
            throw new CommandException(__($parser->getMessage()));
        }
    }
}
