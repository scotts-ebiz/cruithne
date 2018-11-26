<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Gateway\Cc;

use Magento\Payment\Gateway\Command\CommandException;
use Magento\Sales\Model\Order\Payment\Transaction;

use Vantiv\Payment\Gateway\Common\SubjectReader as Reader;
use Vantiv\Payment\Gateway\Cc\Builder\CaptureBuilder as Builder;
use Vantiv\Payment\Gateway\Common\Client\HttpClient as Client;
use Vantiv\Payment\Gateway\Cc\Parser\CaptureResponseParserFactory as ParserFactory;
use Vantiv\Payment\Gateway\Common\Parser\ResponseParserInterface;
use Vantiv\Payment\Gateway\Common\AbstractPaymentCommand;

/**
 * Capture command implementation.
 */
class CaptureCommand extends AbstractPaymentCommand
{
    /**
     * SaleCommand instance.
     *
     * @var SaleCommand
     */
    private $saleCommand = null;

    /**
     * Constructor.
     *
     * @param Client $client
     * @param ParserFactory $parserFactory
     * @param Builder $builder
     * @param Reader $reader
     * @param SaleCommand $saleCommand
     */
    public function __construct(
        Client $client,
        ParserFactory $parserFactory,
        Builder $builder,
        Reader $reader,
        SaleCommand $saleCommand
    ) {
        parent::__construct($reader, $builder, $client, $parserFactory);

        $this->saleCommand = $saleCommand;
    }

    /**
     * Get sale command.
     *
     * @return SaleCommand
     */
    private function getSaleCommand()
    {
        return $this->saleCommand;
    }

    /**
     * Execute command.
     *
     * @throws CommandException
     * @param array $subject
     * @return void
     */
    public function execute(array $subject)
    {
        $payment = $this->getReader()->readPayment($subject);

        if ($payment->getAuthorizationTransaction()) {
            parent::execute($subject);
        } else {
            $this->getSaleCommand()->execute($subject);
        }
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
        $payment->setIsTransactionClosed(true);
        $payment->setTransactionId($parser->getLitleTxnId());
        $payment->setTransactionAdditionalInfo(
            Transaction::RAW_DETAILS,
            $parser->toTransactionRawDetails()
        );
    }
}
