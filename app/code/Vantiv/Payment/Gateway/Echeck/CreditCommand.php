<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Gateway\Echeck;

use Magento\Payment\Gateway\Command\CommandException;
use Vantiv\Payment\Gateway\Common\Client\HttpClient;
use Vantiv\Payment\Gateway\Common\SubjectReader as Reader;
use Vantiv\Payment\Gateway\Echeck\Builder\EcheckCreditBuilder as Builder;
use Vantiv\Payment\Gateway\Echeck\Parser\EcheckCreditResponseParserFactory as ParserFactory;
use Magento\Sales\Model\Order\Payment\Transaction;
use Vantiv\Payment\Gateway\Common\AbstractPaymentCommand;
use Vantiv\Payment\Gateway\Common\Parser\ResponseParserInterface;

/**
 * Credit (Refund) command implementation.
 */
class CreditCommand extends AbstractPaymentCommand
{
    /**
     * Constructor.
     *
     * @param HttpClient $client
     * @param Builder $builder
     * @param Reader $reader
     * @param ParserFactory $parserFactory
     */
    public function __construct(
        HttpClient $client,
        Builder $builder,
        Reader $reader,
        ParserFactory $parserFactory
    ) {
        parent::__construct($reader, $builder, $client, $parserFactory);
    }

    /**
     * Execute command.
     *
     * @param array $subject
     * @return void
     */
    protected function handle(array $subject, ResponseParserInterface $parser)
    {
        if ($parser->getResponse() === '000') {
            $this->getReader()
                ->readPayment($subject)
                ->setTransactionId($parser->getLitleTxnId());

            $this->getReader()
                ->readPayment($subject)
                ->setTransactionAdditionalInfo(Transaction::RAW_DETAILS, $parser->toTransactionRawDetails());
        } else {
            throw new CommandException(__($parser->getMessage()));
        }
    }
}
