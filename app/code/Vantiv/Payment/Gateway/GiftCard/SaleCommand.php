<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Gateway\GiftCard;

use Magento\Payment\Gateway\Command\CommandException;
use Vantiv\Payment\Gateway\Common\Client\HttpClient;
use Vantiv\Payment\Gateway\Common\SubjectReader as Reader;
use Vantiv\Payment\Gateway\GiftCard\Builder\GiftCardSaleBuilder as Builder;
use Vantiv\Payment\Gateway\Cc\Parser\SaleResponseParserFactory;
use Vantiv\Payment\Gateway\Common\Parser\ResponseParserInterface;
use Vantiv\Payment\Gateway\Common\AbstractCustomCommand;
use Vantiv\Payment\Gateway\GiftCard\Config\VantivGiftcardConfig;

/**
 * Sale command implementation.
 */
class SaleCommand extends AbstractCustomCommand
{
    /**
     * GiftCard sale request builder.
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
     * @var SaleResponseParserFactory
     */
    private $parserFactory = null;

    /**
     * Constructor.
     *
     * @param HttpClient $client
     * @param VantivGiftcardConfig $config
     * @param Builder $builder
     * @param Reader $reader
     * @param SaleResponseParserFactory $parserFactory
     */
    public function __construct(
        HttpClient $client,
        VantivGiftcardConfig $config,
        Builder $builder,
        Reader $reader,
        SaleResponseParserFactory $parserFactory
    ) {
        parent::__construct($client, $config);

        $this->builder = $builder;
        $this->reader = $reader;
        $this->parserFactory = $parserFactory;
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
     * @return SaleResponseParserFactory
     */
    private function getParserFactory()
    {
        return $this->parserFactory;
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
            $payment = $this->getReader()->readPayment($subject);

            /** @var \Magento\Sales\Model\Order $order */
            $order = $payment->getOrder();
            $transactionInfo = $parser->toTransactionRawDetails();
            $orderComment = __('Gift Card Sale Transaction (Vantiv): %1', $transactionInfo['litleTxnId']);
            $order->addStatusHistoryComment($orderComment);
        } else {
            throw new CommandException(__($parser->getMessage()));
        }
    }
}
