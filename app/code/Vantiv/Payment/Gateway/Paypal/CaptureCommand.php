<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Gateway\Paypal;

use Magento\Payment\Gateway\Command\CommandException;
use Vantiv\Payment\Gateway\Common\Client\HttpClient;
use Vantiv\Payment\Gateway\Common\SubjectReader as Reader;
use Vantiv\Payment\Gateway\Paypal\Builder\PaypalCaptureBuilder as Builder;
use Vantiv\Payment\Gateway\Paypal\Parser\PaypalCaptureResponseParserFactory;
use Magento\Sales\Model\Order\Payment\Transaction;
use Vantiv\Payment\Gateway\Common\AbstractPaymentCommand;
use Vantiv\Payment\Gateway\Common\Parser\ResponseParserInterface;

/**
 * Capture command implementation.
 */
class CaptureCommand extends AbstractPaymentCommand
{
    /**
     * Constructor.
     *
     * @param HttpClient $client
     * @param Builder $builder
     * @param Reader $reader
     * @param PaypalCaptureResponseParserFactory $parserFactory
     */
    public function __construct(
        HttpClient $client,
        Builder $builder,
        Reader $reader,
        PaypalCaptureResponseParserFactory $parserFactory
    ) {
        parent::__construct($reader, $builder, $client, $parserFactory);
    }

    /**
     * Execute command.
     *
     * @param array $subject
     * @param ResponseParserInterface $parser
     * @return void
     * @throws CommandException
     */
    protected function handle(array $subject, ResponseParserInterface $parser)
    {
        if ($parser->getResponse() === ResponseParserInterface::PAYMENT_APPROVED) {
            $payment = $this->getReader()->readPayment($subject);

            $payment->setIsTransactionClosed(true);
            $payment->setTransactionId($parser->getLitleTxnId());
            $payment->setTransactionAdditionalInfo(
                Transaction::RAW_DETAILS,
                $parser->toTransactionRawDetails()
            );

            $payment->getOrder()->addStatusHistoryComment($parser->getMessage());
        } else {
            throw new CommandException(__($parser->getMessage()));
        }
    }
}
