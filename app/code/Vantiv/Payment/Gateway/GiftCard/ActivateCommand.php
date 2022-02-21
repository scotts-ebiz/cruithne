<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Gateway\GiftCard;

use Magento\Payment\Gateway\Command\CommandException;
use Vantiv\Payment\Gateway\Common\Client\HttpClient;
use Vantiv\Payment\Gateway\Common\SubjectReader as Reader;
use Vantiv\Payment\Gateway\GiftCard\Builder\GiftCardActivateBuilder as Builder;
use Vantiv\Payment\Gateway\GiftCard\Parser\GiftCardActivateResponseParserFactory;
use Vantiv\Payment\Gateway\Common\Parser\ResponseParserInterface;
use Vantiv\Payment\Gateway\Common\AbstractCustomCommand;
use Vantiv\Payment\Gateway\GiftCard\Config\VantivGiftcardConfig;
use Vantiv\Payment\Gateway\GiftCard\Handler\VirtualGiftCardResponseHandler;

/**
 * Activate command implementation.
 */
class ActivateCommand extends AbstractCustomCommand
{
    /**
     * GiftCard activate request builder.
     *
     * @var Builder
     */
    private $builder = null;

    /**
     * Subject reader.
     *
     * @var Reader
     */
    private $reader = null;

    /**
     * Response parser factory.
     *
     * @var GiftCardActivateResponseParserFactory
     */
    private $parserFactory = null;

    /**
     * Virtual Gift Card Handler
     *
     * @var VirtualGiftCardResponseHandler
     */
    private $virtualGiftCardResponseHandler = null;

    /**
     * Constructor.
     *
     * @param HttpClient $client
     * @param VantivGiftcardConfig $config
     * @param Builder $builder
     * @param Reader $reader
     * @param GiftCardActivateResponseParserFactory $parserFactory
     * @param VirtualGiftCardResponseHandler $virtualGiftCardResponseHandler
     */
    public function __construct(
        HttpClient $client,
        VantivGiftcardConfig $config,
        Builder $builder,
        Reader $reader,
        GiftCardActivateResponseParserFactory $parserFactory,
        VirtualGiftCardResponseHandler $virtualGiftCardResponseHandler
    ) {
        parent::__construct($client, $config);

        $this->builder = $builder;
        $this->reader = $reader;
        $this->parserFactory = $parserFactory;
        $this->virtualGiftCardResponseHandler = $virtualGiftCardResponseHandler;
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
     * Get command subject reader.
     *
     * @return Reader
     */
    private function getReader()
    {
        return $this->reader;
    }

    /**
     * Get parser factory.
     *
     * @return GiftCardActivateResponseParserFactory
     */
    private function getParserFactory()
    {
        return $this->parserFactory;
    }

    /**
     * Get Virtual Gift Card Handler
     *
     * @return VirtualGiftCardResponseHandler
     */
    private function getVirtualGiftCardResponseHandler()
    {
        return $this->virtualGiftCardResponseHandler;
    }

    /**
     * Execute command.
     *
     * @param array $subject
     * @return void
     * @throws \Exception
     */
    public function execute(array $subject)
    {
        $request = $this->getBuilder()->build($subject);
        $response = $this->call($request);
        $parser = $this->getParserFactory()->create(['xml' => $response]);

        if ($parser->getResponse() === ResponseParserInterface::PAYMENT_APPROVED) {
            $payment = $this->getReader()->readPayment($subject);

            /** @var \Magento\Sales\Model\Order $order */
            $order = $payment->getOrder();
            $transactionInfo = $parser->toTransactionRawDetails();
            $orderComment = __('Gift Card Activation Transaction (Vantiv): %1', $transactionInfo['litleTxnId']);
            $order->addStatusHistoryComment($orderComment);
        } else {
            throw new \Exception($parser->getMessage());
        }

        $this->getVirtualGiftCardResponseHandler()->handle($subject, $parser);
    }
}
