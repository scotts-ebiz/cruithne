<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Gateway\GiftCard;

use Magento\Payment\Gateway\Command\CommandException;
use Vantiv\Payment\Gateway\Common\Client\HttpClient;
use Vantiv\Payment\Gateway\GiftCard\Builder\GiftCardBalanceInquiryBuilder as Builder;
use Vantiv\Payment\Gateway\GiftCard\Parser\GiftCardBalanceInquiryResponseParserFactory;
use Vantiv\Payment\Gateway\Common\Parser\ResponseParserInterface;
use Vantiv\Payment\Gateway\Common\AbstractCustomCommand;
use Vantiv\Payment\Gateway\GiftCard\Config\VantivGiftcardConfig;
use Vantiv\Payment\Gateway\GiftCard\Handler\BalanceInquiryResponseHandler;

/**
 * BalanceInquiry command implementation.
 */
class GiftCardBalanceInquiryCommand extends AbstractCustomCommand
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
     * Gift Card Balance Inquiry Handler
     *
     * @var BalanceInquiryResponseHandler
     */
    private $balanceInquiryResponseHandler = null;

    /**
     * Constructor.
     *
     * @param HttpClient $client
     * @param VantivGiftcardConfig $config
     * @param Builder $builder
     * @param GiftCardBalanceInquiryResponseParserFactory $parserFactory
     * @param BalanceInquiryResponseHandler $balanceInquiryResponseHandler
     */
    public function __construct(
        HttpClient $client,
        VantivGiftcardConfig $config,
        Builder $builder,
        GiftCardBalanceInquiryResponseParserFactory $parserFactory,
        BalanceInquiryResponseHandler $balanceInquiryResponseHandler
    ) {
        parent::__construct($client, $config);

        $this->builder = $builder;
        $this->parserFactory = $parserFactory;
        $this->balanceInquiryResponseHandler = $balanceInquiryResponseHandler;
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
     * Get Gift Card Balance Inquiry Handler
     *
     * @return BalanceInquiryResponseHandler
     */
    private function getBalanceInquiryResponseHandler()
    {
        return $this->balanceInquiryResponseHandler;
    }

    /**
     * Execute command.
     *
     * @param array $subject
     * @return void
     * @throws CommandException
     */
    public function execute(array $subject)
    {
        $request = $this->getBuilder()->build($subject);
        $response = $this->call($request);
        $parser = $this->getParserFactory()->create(['xml' => $response]);

        if ($parser->getResponse() === ResponseParserInterface::PAYMENT_APPROVED) {
            $this->getBalanceInquiryResponseHandler()->handle($subject, $parser);
        } else {
            throw new CommandException(__($parser->getMessage()));
        }
    }
}
