<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Gateway\GiftCard;

use Magento\Payment\Gateway\Command\CommandException;
use Vantiv\Payment\Gateway\Common\Client\HttpClient;
use Vantiv\Payment\Gateway\Common\SubjectReader as Reader;
use Vantiv\Payment\Gateway\GiftCard\Builder\GiftCardDeactivateBuilder as Builder;
use Vantiv\Payment\Gateway\GiftCard\Parser\GiftCardDeactivateResponseParserFactory;
use Vantiv\Payment\Gateway\Common\Parser\ResponseParserInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Vantiv\Payment\Gateway\Common\AbstractCustomCommand;
use Vantiv\Payment\Gateway\GiftCard\Config\VantivGiftcardConfig;

/**
 * Deactivate command implementation.
 */
class DeactivateCommand extends AbstractCustomCommand
{
    /**
     * GiftCard deactivate request builder.
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
     * @var GiftCardDeactivateResponseParserFactory
     */
    private $parserFactory = null;

    /**
     * Order repository instance.
     *
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * Constructor.
     *
     * @param HttpClient $client
     * @param VantivGiftcardConfig $config
     * @param Builder $builder
     * @param Reader $reader
     * @param GiftCardDeactivateResponseParserFactory $parserFactory
     * @param OrderRepositoryInterface $orderRepository
     */
    public function __construct(
        HttpClient $client,
        VantivGiftcardConfig $config,
        Builder $builder,
        Reader $reader,
        GiftCardDeactivateResponseParserFactory $parserFactory,
        OrderRepositoryInterface $orderRepository
    ) {
        parent::__construct($client, $config);

        $this->builder = $builder;
        $this->reader = $reader;
        $this->parserFactory = $parserFactory;
        $this->orderRepository = $orderRepository;
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
     * @return GiftCardDeactivateResponseParserFactory
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
            /** @var \Magento\Sales\Model\Order $order */
            $order = $this->getReader()->readOrderAdapter($subject);
            $transactionInfo = $parser->toTransactionRawDetails();
            $orderComment = __('Gift Card Deactivation Transaction (Vantiv): %1', $transactionInfo['litleTxnId']);
            $order->addStatusHistoryComment($orderComment);
            $this->orderRepository->save($order);
        } else {
            throw new CommandException(__($parser->getMessage()));
        }
    }
}
