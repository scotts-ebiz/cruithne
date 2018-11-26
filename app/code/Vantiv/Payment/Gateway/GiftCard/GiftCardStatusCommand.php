<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Gateway\GiftCard;

use Vantiv\Payment\Gateway\Common\Client\HttpClient;
use Vantiv\Payment\Gateway\GiftCard\Builder\GiftCardBalanceInquiryBuilder as Builder;
use Vantiv\Payment\Gateway\GiftCard\Parser\GiftCardBalanceInquiryResponseParserFactory;
use Vantiv\Payment\Gateway\Common\AbstractCustomCommand;
use Vantiv\Payment\Gateway\GiftCard\Config\VantivGiftcardConfig;
use Vantiv\Payment\Gateway\GiftCard\Handler\StatusResponseHandler;

/**
 * Gift Card Status command implementation.
 */
class GiftCardStatusCommand extends AbstractCustomCommand
{
    /**
     * GiftCard balanceInquiry request builder.
     *
     * @var Builder
     */
    private $builder = null;

    /**
     * Response parser factory.
     *
     * @var GiftCardBalanceInquiryResponseParserFactory
     */
    private $parserFactory = null;

    /**
     * Gift Card Status Handler
     *
     * @var StatusResponseHandler
     */
    private $statusResponseHandler = null;

    /**
     * Constructor.
     *
     * @param HttpClient $client
     * @param VantivGiftcardConfig $config
     * @param Builder $builder
     * @param GiftCardBalanceInquiryResponseParserFactory $parserFactory
     * @param StatusResponseHandler $statusResponseHandler
     */
    public function __construct(
        HttpClient $client,
        VantivGiftcardConfig $config,
        Builder $builder,
        GiftCardBalanceInquiryResponseParserFactory $parserFactory,
        StatusResponseHandler $statusResponseHandler
    ) {
        parent::__construct($client, $config);

        $this->builder = $builder;
        $this->parserFactory = $parserFactory;
        $this->statusResponseHandler = $statusResponseHandler;
    }

    /**
     * Get request builder instance.
     *
     * @return Builder
     */
    private function getBuilder()
    {
        return $this->builder;
    }

    /**
     * Get parser factory.
     *
     * @return GiftCardBalanceInquiryResponseParserFactory
     */
    private function getParserFactory()
    {
        return $this->parserFactory;
    }

    /**
     * Get Gift Card Status Handler
     *
     * @return StatusResponseHandler
     */
    private function getStatusResponseHandler()
    {
        return $this->statusResponseHandler;
    }

    /**
     * Execute command.
     *
     * @param array $subject
     * @return void
     */
    public function execute(array $subject)
    {
        $request = $this->getBuilder()->build($subject);
        $response = $this->call($request);
        $parser = $this->getParserFactory()->create(['xml' => $response]);
        $this->getStatusResponseHandler()->handle($subject, $parser);
    }
}
