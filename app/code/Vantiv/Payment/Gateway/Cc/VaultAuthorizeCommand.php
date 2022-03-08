<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Gateway\Cc;

use Vantiv\Payment\Gateway\Common\SubjectReader as Reader;
use Vantiv\Payment\Gateway\Cc\Builder\VaultAuthorizationBuilder as Builder;
use Vantiv\Payment\Gateway\Common\Client\HttpClient as Client;
use Vantiv\Payment\Gateway\Cc\Parser\AuthorizationResponseParserFactory as ParserFactory;

use Magento\Payment\Gateway\Command\CommandException;
use Magento\Sales\Model\Order\Payment\Transaction;
use Vantiv\Payment\Gateway\Cc\Handler\IssuerCountryHandler;
use Vantiv\Payment\Gateway\Common\Handler\AdvancedFraudResultsHandler;
use Vantiv\Payment\Gateway\Common\Parser\ResponseParserInterface;
use Vantiv\Payment\Gateway\Common\AbstractPaymentCommand;
use Vantiv\Payment\Gateway\Recurring\Handler\RecurringResponseHandler;
use Vantiv\Payment\Gateway\Cc\Handler\CardProductTypeHandler;
use Vantiv\Payment\Gateway\Cc\Handler\AccountUpdaterHandler;

/**
 * Vault authorize command implementation.
 */
class VaultAuthorizeCommand extends AbstractPaymentCommand
{
    /**
     * Issuer country handler.
     *
     * @var IssuerCountryHandler
     */
    private $issuerCountryHandler = null;

    /**
     * Advanced fraud results handler.
     *
     * @var AdvancedFraudResultsHandler
     */
    private $advancedFraudHandler = null;

    /**
     * Deny command instance
     *
     * @var DenyCommand
     */
    private $denyCommand = null;

    /**
     * @var RecurringResponseHandler
     */
    private $recurringResponseHandler = null;

    /**
     * @var CardProductTypeHandler
     */
    private $cardProductTypeHandler = null;

    /**
     * @var AccountUpdaterHandler
     */
    private $accountUpdaterHandler = null;

    /**
     * Constructor.
     *
     * @param Client $client
     * @param ParserFactory $parserFactory
     * @param Builder $builder
     * @param Reader $reader
     * @param IssuerCountryHandler $issuerCountryHandler
     * @param AdvancedFraudResultsHandler $advancedFraudHandler
     * @param DenyCommand $denyCommand
     * @param RecurringResponseHandler $recurringResponseHandler
     * @param CardProductTypeHandler $cardProductTypeHandler
     * @param AccountUpdaterHandler $accountUpdaterHandler
     */
    public function __construct(
        Client $client,
        ParserFactory $parserFactory,
        Builder $builder,
        Reader $reader,
        IssuerCountryHandler $issuerCountryHandler,
        AdvancedFraudResultsHandler $advancedFraudHandler,
        DenyCommand $denyCommand,
        RecurringResponseHandler $recurringResponseHandler,
        CardProductTypeHandler $cardProductTypeHandler,
        AccountUpdaterHandler $accountUpdaterHandler
    ) {
        parent::__construct($reader, $builder, $client, $parserFactory);

        $this->issuerCountryHandler = $issuerCountryHandler;
        $this->advancedFraudHandler = $advancedFraudHandler;
        $this->denyCommand = $denyCommand;
        $this->recurringResponseHandler = $recurringResponseHandler;
        $this->cardProductTypeHandler = $cardProductTypeHandler;
        $this->accountUpdaterHandler = $accountUpdaterHandler;
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

        /*
         * Handle transaction information.
         */
        $payment = $this->getReader()->readPayment($subject);

        $payment->setIsTransactionClosed(false);
        $payment->setTransactionId($parser->getLitleTxnId());
        $payment->setTransactionAdditionalInfo(
            Transaction::RAW_DETAILS,
            $parser->toTransactionRawDetails()
        );

        /*
         * Handle advanced fraud results.
         */
        $advancedFraudHandlerResult = $this->getAdvancedFraudHandler()->handle($subject, $parser);

        /*
         * Handle suspected issuer country.
         */
        $issuerCountryHandlerResult = $this->getIssuerCountryHandler()->handle($subject, $parser);

        if (!$advancedFraudHandlerResult || !$issuerCountryHandlerResult) {
            $subject['transaction_id'] = $parser->getLitleTxnId();
            $this->denyCommand->executeDenyAuth($subject);
            throw new CommandException(__('Transaction has been declined. Please try again later.'));
        }

        /*
         * Handle recurring response data
         */
        $this->getRecurringResponseHandler()->handle($subject, $parser);

        /*
         * Handle card product type data.
         */
        $this->getCardProductTypeHandler()->handle($subject, $parser);

        /*
         * Handle account updates.
         */
        $this->getAccountUpdaterHandler()->handle($subject, $parser);
    }

    /**
     * Get issuer country handler.
     *
     * @return IssuerCountryHandler
     */
    private function getIssuerCountryHandler()
    {
        return $this->issuerCountryHandler;
    }

    /**
     * Get advanced fraud results handler.
     *
     * @return AdvancedFraudResultsHandler
     */
    private function getAdvancedFraudHandler()
    {
        return $this->advancedFraudHandler;
    }

    /**
     * Get recurring response handler
     *
     * @return RecurringResponseHandler
     */
    private function getRecurringResponseHandler()
    {
        return $this->recurringResponseHandler;
    }

    /**
     * Get card product type handler.
     *
     * @return CardProductTypeHandler
     */
    private function getCardProductTypeHandler()
    {
        return $this->cardProductTypeHandler;
    }

    /**
     * Get account updates handler.
     *
     * @return AccountUpdaterHandler
     */
    private function getAccountUpdaterHandler()
    {
        return $this->accountUpdaterHandler;
    }
}
