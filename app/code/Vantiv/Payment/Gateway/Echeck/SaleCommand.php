<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Gateway\Echeck;

use Magento\Payment\Gateway\Command\CommandException;
use Vantiv\Payment\Gateway\Common\Client\HttpClient;
use Vantiv\Payment\Gateway\Common\SubjectReader as Reader;
use Vantiv\Payment\Gateway\Echeck\Builder\EcheckSaleBuilder;
use Vantiv\Payment\Gateway\Echeck\Builder\EcheckRedepositBuilder;
use Vantiv\Payment\Gateway\Echeck\Parser\EcheckSalesResponseParserFactory as SalesParserFactory;
use Vantiv\Payment\Gateway\Echeck\Parser\EcheckRedepositResponseParserFactory as RedepositParserFactory;
use Magento\Sales\Model\Order\Payment\Transaction;
use Vantiv\Payment\Gateway\Common\Parser\ResponseParserInterface;
use Vantiv\Payment\Gateway\Common\AbstractCommand;
use Vantiv\Payment\Gateway\Echeck\Handler\VaultHandler;
use Magento\Sales\Model\Order\Email\Sender\OrderCommentSender;

/**
 * Sale command implementation.
 */
class SaleCommand extends AbstractCommand
{
    /**
     * Sale attempts count key.
     *
     * @var string
     */
    const SALE_ATTEMPTS_KEY = 'sale_attempts_count';

    /**
     * Max attempts for Redeposit action.
     */
    const REDEPOSIT_MAX_ATTEMPTS = 2;

    /**
     * Sale litle-transaction ID key.
     *
     * @var string
     */
    const SALE_TRANSACTION_KEY = 'sale_transaction_id';

    /**
     * Sale transaction amount value.
     *
     * @var string
     */
    const SALE_AMOUNT_KEY = 'sale_amount_key';

    /**
     * Echeck sale request builder.
     *
     * @var EcheckSaleBuilder
     */
    private $saleBuilder = null;

    /**
     * Echeck re-deposit request builder.
     *
     * @var EcheckRedepositBuilder
     */
    private $redepositBuilder = null;

    /**
     * Subject reader.
     *
     * @var Reader
     */
    private $reader = null;

    /**
     * HTTP client instance.
     *
     * @var Client
     */
    private $client = null;

    /**
     * Response parser factory.
     *
     * @var SalesParserFactory
     */
    private $salesParserFactory = null;

    /**
     * Response parser factory.
     *
     * @var RedepositParserFactory
     */
    private $redepositParserFactory = null;

    /**
     * Vauld data handler.
     *
     * @var VaultHandler
     */
    private $vaultHandler = null;

    /**
     * @var \Magento\Sales\Model\Order\Email\Sender\OrderCommentSender
     */
    private $orderCommentSender;

    /**
     * Constructor
     *
     * @param HttpClient $client
     * @param EcheckSaleBuilder $saleBuilder
     * @param EcheckRedepositBuilder $redepositBuilder
     * @param Reader $reader
     * @param SalesParserFactory $salesParserFactory
     * @param RedepositParserFactory $redepositParserFactory
     * @param VaultHandler $vaultHandler
     */
    public function __construct(
        HttpClient $client,
        EcheckSaleBuilder $saleBuilder,
        EcheckRedepositBuilder $redepositBuilder,
        Reader $reader,
        SalesParserFactory $salesParserFactory,
        RedepositParserFactory $redepositParserFactory,
        VaultHandler $vaultHandler,
        OrderCommentSender $orderCommentSender
    ) {
        $this->client = $client;
        $this->saleBuilder = $saleBuilder;
        $this->redepositBuilder = $redepositBuilder;
        $this->reader = $reader;
        $this->salesParserFactory = $salesParserFactory;
        $this->redepositParserFactory = $redepositParserFactory;
        $this->vaultHandler = $vaultHandler;
        $this->orderCommentSender = $orderCommentSender;
    }

    /**
     * Get sale request builder instance.
     *
     * @return EcheckSaleBuilder
     */
    private function getSaleBuilder()
    {
        return $this->saleBuilder;
    }

    /**
     * Get re-deposit request builder.
     *
     * @return EcheckRedepositBuilder
     */
    private function getRedepositBuilder()
    {
        return $this->redepositBuilder;
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
     * Get HTTP client.
     *
     * @return HttpClient
     */
    private function getClient()
    {
        return $this->client;
    }

    /**
     * Get sales response parser factory.
     *
     * @return SalesParserFactory
     */
    private function getSalesParserFactory()
    {
        return $this->salesParserFactory;
    }

    /**
     * Get redeposit parser factory.
     *
     * @return RedepositParserFactory
     */
    private function getRedepositParserFactory()
    {
        return $this->redepositParserFactory;
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

    /**
     * Execute command.
     *
     * @param array $subject
     * @return void
     * @throws CommandException
     */
    public function execute(array $subject)
    {
        $payment = $this->getReader()->readPayment($subject);

        if ($payment->getAuthorizationTransaction()) {
            /*
             * Will be executed when creating invoice.
             */
            $count = (int) $payment->getAdditionalInformation(self::SALE_ATTEMPTS_KEY);
            if ($count === 0) {
                /*
                 * Will be executed for initial capture attempt.
                 */
                $this->executeCapture($subject);
            } else {
                /*
                 * Will be executed for subsequent capture attempts.
                 */
                $this->executeRedeposit($subject);
            }
        } else {
            /*
             * Will be executed only for initial capture on checkout.
             */
            $this->executeSale($subject);
        }
    }

    /**
     * Execute sale command.
     *
     * @param array $subject
     * @throws CommandException
     */
    private function executeSale(array $subject)
    {
        $method = $this->getReader()->readPayment($subject)->getMethodInstance();

        $response = $this->getClient()->post([
            'url'         => $this->getUrlByEnvironment($method->getConfigData('environment')),
            'body'        => $this->getSaleBuilder()->build($subject),
            'debug'       => $method->getConfigData('debug'),
            'http_timeout' => $method->getConfigData('http_timeout'),
            'http_proxy'   => $method->getConfigData('http_proxy'),
        ]);

        /** @var \Vantiv\Payment\Gateway\Echeck\Parser\EcheckSalesResponseParser $parser */
        $parser = $this->getSalesParserFactory()->create(['xml' => $response]);

        if ($parser->getResponse() !== ResponseParserInterface::PAYMENT_APPROVED) {
            throw new CommandException(__($parser->getMessage()));
        }

        $this->getReader()->readPayment($subject)
            ->setTransactionId($parser->getLitleTxnId());

        $this->getReader()->readPayment($subject)
            ->setTransactionAdditionalInfo(Transaction::RAW_DETAILS, $parser->toTransactionRawDetails());

        /*
         * Handle vault data.
         */
        $this->getVaultHandler()->handle($subject, $parser);
    }

    /**
     * Execute capture command.
     *
     * @param array $subject
     * @throws CommandException
     */
    private function executeCapture(array $subject)
    {
        $payment = $this->getReader()->readPayment($subject);
        $method = $payment->getMethodInstance();

        $response = $this->getClient()->post([
            'url'         => $this->getUrlByEnvironment($method->getConfigData('environment')),
            'body'        => $this->getSaleBuilder()->build($subject),
            'debug'       => $method->getConfigData('debug'),
            'http_timeout' => $method->getConfigData('http_timeout'),
            'http_proxy'   => $method->getConfigData('http_proxy'),
        ]);

        /** @var \Vantiv\Payment\Gateway\Echeck\Parser\EcheckSalesResponseParser $parser */
        $parser = $this->getSalesParserFactory()->create(['xml' => $response]);

        if ($parser->getResponse() !== ResponseParserInterface::PAYMENT_APPROVED) {
            if ($parser->getResponse() === ResponseParserInterface::INSUFFICIENT_FUNDS) {
                $txnId = $parser->getLitleTxnId();
                $payment->setAdditionalInformation(self::SALE_TRANSACTION_KEY, $txnId);

                $amount = $this->getReader()->readAmount($subject);
                $payment->setAdditionalInformation(self::SALE_AMOUNT_KEY, $amount);

                $count = 1;
                $payment->setAdditionalInformation(self::SALE_ATTEMPTS_KEY, $count);

                $payment->save();

                $this->processCapturingFail($subject);
            }

            throw new CommandException(__($parser->getMessage()));
        }

        $this->getReader()->readPayment($subject)
            ->setTransactionId($parser->getLitleTxnId());

        $this->getReader()->readPayment($subject)
            ->setTransactionAdditionalInfo(Transaction::RAW_DETAILS, $parser->toTransactionRawDetails());
    }

    /**
     * Execute re-deposit command.
     *
     * @param array $subject
     * @throws CommandException
     */
    private function executeRedeposit(array $subject)
    {
        $payment = $this->getReader()->readPayment($subject);
        $captureAmount = $payment->getAdditionalInformation(self::SALE_AMOUNT_KEY);
        $redepositAmount = $this->getReader()->readAmount($subject);
        if ($captureAmount !== $redepositAmount) {
            throw new CommandException(__('Initial capture amount is not equal to re-deposit amount.'));
        }

        $method = $this->getReader()->readPayment($subject)->getMethodInstance();

        $response = $this->getClient()->post([
            'url'         => $this->getUrlByEnvironment($method->getConfigData('environment')),
            'body'        => $this->getRedepositBuilder()->build($subject),
            'debug'       => $method->getConfigData('debug'),
            'http_timeout' => $method->getConfigData('http_timeout'),
            'http_proxy'   => $method->getConfigData('http_proxy'),
        ]);

        /** @var \Vantiv\Payment\Gateway\Echeck\Parser\EcheckRedepositResponseParser $parser */
        $parser = $this->getRedepositParserFactory()->create(['xml' => $response]);

        if ($parser->getResponse() !== ResponseParserInterface::PAYMENT_APPROVED) {
            if ($parser->getResponse() === ResponseParserInterface::INSUFFICIENT_FUNDS) {
                $count = (int) $payment->getAdditionalInformation(self::SALE_ATTEMPTS_KEY);
                $count++;
                $payment->setAdditionalInformation(self::SALE_ATTEMPTS_KEY, $count);

                $payment->save();

                $this->processCapturingFail($subject);
            }

            throw new CommandException(__($parser->getMessage()));
        }

        $this->getReader()->readPayment($subject)
            ->setTransactionId($parser->getLitleTxnId());

        $this->getReader()->readPayment($subject)
            ->setTransactionAdditionalInfo(Transaction::RAW_DETAILS, $parser->toTransactionRawDetails());
    }

    /**
     * Handle capturing transaction fail.
     *
     * @param array $subject
     * @return $this
     */
    private function processCapturingFail(array $subject)
    {
        $comment = __('Can\'t create invoice: Insufficient funds');
        $order = $this->getReader()
            ->readPayment($subject)
            ->getOrder();
        $order->addStatusHistoryComment($comment)
            ->setIsCustomerNotified(true)
            ->save();

        $this->orderCommentSender->send($order, true, $comment);

        return $this;
    }
}
