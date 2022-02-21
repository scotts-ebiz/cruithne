<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Gateway\Paypal;

use Magento\Payment\Gateway\Command\CommandException;
use Vantiv\Payment\Gateway\Common\Client\HttpClient;
use Vantiv\Payment\Gateway\Common\SubjectReader as Reader;
use Vantiv\Payment\Gateway\Paypal\Builder\PaypalVoidBuilder as Builder;
use Vantiv\Payment\Gateway\Paypal\Parser\PaypalVoidResponseParserFactory;
use Magento\Sales\Model\Order\Payment\Transaction;
use Vantiv\Payment\Gateway\Common\AbstractPaymentCommand;
use Vantiv\Payment\Gateway\Common\Parser\ResponseParserInterface;

/**
 * Void command implementation.
 */
class VoidCommand extends AbstractPaymentCommand
{
    /**
     * Constructor.
     *
     * @param HttpClient $client
     * @param Builder $builder
     * @param Reader $reader
     * @param PaypalVoidResponseParserFactory $parserFactory
     */
    public function __construct(
        HttpClient $client,
        Builder $builder,
        Reader $reader,
        PaypalVoidResponseParserFactory $parserFactory
    ) {
        parent::__construct($reader, $builder, $client, $parserFactory);
    }

    /**
     * Execute Void Command.
     *
     * @param array $commandSubject
     * @param ResponseParserInterface $parser
     * @throws CommandException
     * @return void
     */
    protected function handle(array $commandSubject, ResponseParserInterface $parser)
    {
        if ($parser->getResponse() !== ResponseParserInterface::PAYMENT_APPROVED) {
            throw new CommandException(__('Transaction has been declined. Please try again later.'));
        }

        /** @var \Magento\Sales\Model\Order\Payment $payment */
        $payment = $this->getReader()->readPayment($commandSubject);
        $payment->setTransactionId($parser->getLitleTxnId());
        $payment->setIsTransactionClosed(true);
        $payment->setShouldCloseParentTransaction(true);

        $payment->setTransactionAdditionalInfo(
            Transaction::RAW_DETAILS,
            $parser->toTransactionRawDetails()
        );
    }
}
