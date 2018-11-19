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
use Vantiv\Payment\Gateway\Recurring\Builder\DeleteAddonSubscriptionBuilder;
use Vantiv\Payment\Gateway\Recurring\Parser\UpdateSubscriptionResponseParserFactory;

class DeleteAddonSubscriptionCommand extends AbstractCustomCommand
{
    /**
     * @var DeleteAddonSubscriptionBuilder
     */
    private $builder;
    /**
     * @var UpdateSubscriptionResponseParserFactory
     */
    private $responseParser;

    /**
     * DeleteAddonSubscriptionCommand constructor.
     *
     * @param HttpClient $client
     * @param VantivSubscriptionConfig $config
     * @param DeleteAddonSubscriptionBuilder $builder
     * @param UpdateSubscriptionResponseParserFactory $responseParser
     */
    public function __construct(
        HttpClient $client,
        VantivSubscriptionConfig $config,
        DeleteAddonSubscriptionBuilder $builder,
        UpdateSubscriptionResponseParserFactory $responseParser
    ) {
        parent::__construct($client, $config);
        $this->builder = $builder;
        $this->responseParser = $responseParser;
    }

    /**
     * Executes command to delete Subscription Add-On
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
