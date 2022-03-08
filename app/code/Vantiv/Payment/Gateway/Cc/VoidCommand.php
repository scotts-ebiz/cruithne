<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Gateway\Cc;

use Magento\Payment\Gateway\Command\CommandException;
use Magento\Sales\Model\Order\Payment\Transaction;

use Vantiv\Payment\Gateway\Common\Client\HttpClient as Client;
use Vantiv\Payment\Gateway\Common\SubjectReader as Reader;
use Vantiv\Payment\Gateway\Cc\Builder\VoidBuilder as Builder;
use Vantiv\Payment\Gateway\Cc\Parser\VoidResponseParserFactory as ParserFactory;

use Vantiv\Payment\Gateway\Common\Parser\ResponseParserInterface;
use Vantiv\Payment\Gateway\Common\AbstractPaymentCommand;

/**
 * Void Command implementation.
 */
class VoidCommand extends AbstractPaymentCommand
{
    /**
     * Constructor.
     *
     * @param Client $client
     * @param ParserFactory $parserFactory
     * @param Builder $builder
     * @param Reader $reader
     */
    public function __construct(
        Client $client,
        ParserFactory $parserFactory,
        Builder $builder,
        Reader $reader
    ) {
        parent::__construct($reader, $builder, $client, $parserFactory);
    }

    /**
     * Handle authorization response.
     *
     * @param array $subject
     * @param ResponseParserInterface $parser
     * @throws CommandException
     * @return void
     */
    protected function handle(array $subject, ResponseParserInterface $parser)
    {
        if ($parser->getResponse() !== ResponseParserInterface::PAYMENT_APPROVED) {
            throw new CommandException(__('Transaction has been declined. Please try again later.'));
        }

        $payment = $this->getReader()->readPayment($subject);

        $payment->setTransactionId($parser->getLitleTxnId());
        $payment->setIsTransactionClosed(true);
        $payment->setShouldCloseParentTransaction(true);
        $payment->setTransactionAdditionalInfo(
            Transaction::RAW_DETAILS,
            $parser->toTransactionRawDetails()
        );
    }
}
