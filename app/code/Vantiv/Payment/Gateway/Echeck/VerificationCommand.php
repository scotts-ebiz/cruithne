<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Gateway\Echeck;

use Magento\Payment\Gateway\Command\CommandException;
use Vantiv\Payment\Gateway\Common\Client\HttpClient;
use Vantiv\Payment\Gateway\Common\SubjectReader as Reader;
use Vantiv\Payment\Gateway\Echeck\Builder\EcheckVerificationBuilder as Builder;
use Vantiv\Payment\Gateway\Echeck\Parser\EcheckVerificationResponseParserFactory as ParserFactory;
use Magento\Sales\Model\Order\Payment\Transaction;
use Vantiv\Payment\Gateway\Echeck\Handler\VaultHandler;
use Vantiv\Payment\Gateway\Common\AbstractPaymentCommand;
use Vantiv\Payment\Gateway\Common\Parser\ResponseParserInterface;
use Magento\Vault\Model\Ui\VaultConfigProvider;
use Vantiv\Payment\Gateway\Echeck\Builder\RegisterTokenBuilderDetached;
use Vantiv\Payment\Gateway\Echeck\Parser\RegisterTokenResponseParserFactory;
use Vantiv\Payment\Model\Logger\Logger;

/**
 * Verification command implementation.
 */
class VerificationCommand extends AbstractPaymentCommand
{
    /**
     * Vault data handler.
     *
     * @var VaultHandler
     */
    private $vaultHandler = null;

    /**
     * @var RegisterTokenBuilderDetached
     */
    private $registerTokenBuilder = null;

    /**
     * @var RegisterTokenResponseParserFactory
     */
    private $registerTokenResponseParserFactory = null;

    /**
     * Logger instance.
     *
     * @var Logger
     */
    private $logger = null;

    /**
     * @param \Vantiv\Payment\Gateway\Common\Client\HttpClient $client
     * @param \Vantiv\Payment\Gateway\Echeck\Builder\EcheckVerificationBuilder $builder
     * @param \Vantiv\Payment\Gateway\Common\SubjectReader $reader
     * @param \Vantiv\Payment\Gateway\Echeck\Parser\EcheckVerificationResponseParserFactory $parserFactory
     * @param \Vantiv\Payment\Gateway\Echeck\Handler\VaultHandler $vaultHandler
     * @param \Vantiv\Payment\Gateway\Echeck\Builder\RegisterTokenBuilderDetached $registerTokenBuilder
     * @param RegisterTokenResponseParserFactory $registerTokenResponseParserFactory
     * @param \Vantiv\Payment\Model\Logger\Logger $logger
     */
    public function __construct(
        HttpClient $client,
        Builder $builder,
        Reader $reader,
        ParserFactory $parserFactory,
        VaultHandler $vaultHandler,
        RegisterTokenBuilderDetached $registerTokenBuilder,
        RegisterTokenResponseParserFactory $registerTokenResponseParserFactory,
        Logger $logger
    ) {
        parent::__construct($reader, $builder, $client, $parserFactory);

        $this->vaultHandler = $vaultHandler;
        $this->registerTokenBuilder = $registerTokenBuilder;
        $this->registerTokenResponseParserFactory = $registerTokenResponseParserFactory;
        $this->logger = $logger;
    }

    /**
     * Execute command.
     *
     * @param array $subject
     * @return void
     */
    public function execute(array $subject)
    {
        $countryId = $this->getReader()->readOrderAdapter($subject)->getBillingAddress()->getCountryId();

        if ($countryId === 'US') {
            parent::execute($subject);
        } else {
            $statusHistoryComment = (string) __('Bank account verification skipped.');
            $this->getReader()
                ->readPayment($subject)
                ->getOrder()
                ->addStatusHistoryComment($statusHistoryComment);

            try {
                $this->processVault($subject);
            } catch (\Magento\Payment\Gateway\Http\ClientException $e) {
                $this->logger->debug('Echeck Register token exception:' . $e->getMessage());
            }
        }
    }

    /**
     * @param array $subject
     * @throws \Magento\Payment\Gateway\Http\ClientException
     */
    private function processVault(array $subject)
    {
        $payment = $this->getReader()->readPayment($subject);
        if ($payment->getAdditionalInformation(VaultConfigProvider::IS_ACTIVE_CODE)) {
            $parser = $this->registerToken($subject);
            $this->getVaultHandler()->handle($subject, $parser);
        }
    }

    /**
     * @param array $subject
     * @return Parser\RegisterTokenResponseParser
     * @throws \Magento\Payment\Gateway\Http\ClientException
     */
    private function registerToken(array $subject)
    {
        $payment = $this->getReader()->readPayment($subject);
        $method = $payment->getMethodInstance();

        $response = $this->getClient()->post([
            'url' => $this->getUrlByEnvironment($method->getConfigData('environment')),
            'body' => $this->registerTokenBuilder->build($subject),
            'debug' => $method->getConfigData('debug'),
            'http_timeout' => $method->getConfigData('http_timeout'),
            'http_proxy' => $method->getConfigData('http_proxy'),
        ]);

        return $this->registerTokenResponseParserFactory->create(['xml' => $response]);
    }

    protected function handle(array $subject, ResponseParserInterface $parser)
    {
        $method = $this->getReader()->readPayment($subject)->getMethodInstance();
        if ($parser->getResponse() === '000' || $method->getConfigData('accept_on_fail')) {
            $this->getReader()
               ->readPayment($subject)
                ->setIsTransactionClosed(false)
                ->setTransactionId($parser->getLitleTxnId());

            $this->getReader()
                ->readPayment($subject)
                ->getOrder()
                ->addStatusHistoryComment($parser->getMessage());

            $this->getReader()
                ->readPayment($subject)
                ->setTransactionAdditionalInfo(Transaction::RAW_DETAILS, $parser->toTransactionRawDetails());
        } else {
            throw new CommandException(__('Transaction has been declined. Please try again later.'));
        }

        /*
         * Handle vault data.
         */
        $this->getVaultHandler()->handle($subject, $parser);
    }

    /**
     * Get vault data handler.
     *
     * @return VaultHandler
     */
    private function getVaultHandler()
    {
        return $this->vaultHandler;
    }
}
