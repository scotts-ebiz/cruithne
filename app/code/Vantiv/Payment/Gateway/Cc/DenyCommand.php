<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Gateway\Cc;

use Magento\Payment\Gateway\Command\CommandException;
use Vantiv\Payment\Gateway\Common\AbstractCommand;
use Vantiv\Payment\Gateway\Common\Client\HttpClient as Client;
use Vantiv\Payment\Gateway\Common\SubjectReader as Reader;
use Vantiv\Payment\Gateway\Cc\Builder\DenyBuilder as Builder;
use Vantiv\Payment\Gateway\Cc\Parser\VoidResponseParserFactory as VoidParserFactory;
use Vantiv\Payment\Gateway\Cc\Parser\VoidSaleResponseParserFactory as VoidSaleParserFactory;
use Vantiv\Payment\Gateway\Cc\Parser\VoidResponseParser;
use Vantiv\Payment\Gateway\Cc\Parser\VoidSaleResponseParser;
use Vantiv\Payment\Gateway\Common\Parser\ResponseParserInterface;
use Vantiv\Payment\Gateway\Cc\Builder\DenyBuilder;

/**
 * Deny Command implementation
 */
class DenyCommand extends AbstractCommand
{
    /**
     * HTTP client instance
     *
     * @var Client
     */
    private $client;

    /**
     * Deny request builder instance
     *
     * @var Builder
     */
    private $builder;

    /**
     * Subject reader instance
     *
     * @var Reader
     */
    private $reader;

    /**
     * Void response parser factory
     *
     * @var VoidParserFactory
     */
    private $voidParserFactory;

    /**
     * Void sale response parser factory
     *
     * @var VoidSaleParserFactory
     */
    private $voidSaleParserFactory;

    /**
     * Constructor
     *
     * @param Client $client
     * @param Builder $builder
     * @param Reader $reader
     * @param VoidParserFactory $voidParserFactory
     * @param VoidSaleParserFactory $voidSaleParserFactory
     */
    public function __construct(
        Client $client,
        Builder $builder,
        Reader $reader,
        VoidParserFactory $voidParserFactory,
        VoidSaleParserFactory $voidSaleParserFactory
    ) {
        $this->client = $client;
        $this->builder = $builder;
        $this->reader = $reader;
        $this->voidParserFactory = $voidParserFactory;
        $this->voidSaleParserFactory = $voidSaleParserFactory;
    }

    /**
     * Execute command
     *
     * @param array $subject
     * @return void
     * @throws CommandException
     */
    public function execute(array $subject)
    {
        $payment = $this->reader->readPayment($subject);

        if ($payment->getAuthorizationTransaction()) {
            $this->executeDenyAuth($subject);
        } else {
            $this->executeDenySale($subject);
        }
    }

    /**
     * Execute deny authorization transaction command
     *
     * @param array $subject
     * @throws CommandException
     */
    public function executeDenyAuth(array $subject)
    {
        $payment = $this->reader->readPayment($subject);
        $method = $payment->getMethodInstance();
        $subject['void_node'] = DenyBuilder::VOID_REQUEST_AUTH_NODE;

        $response = $this->client->post([
            'url'          => $this->getUrlByEnvironment($method->getConfigData('environment')),
            'body'         => $this->builder->build($subject),
            'debug'        => $method->getConfigData('debug'),
            'http_timeout' => $method->getConfigData('http_timeout'),
            'http_proxy'   => $method->getConfigData('http_proxy'),
        ]);

        /** @var VoidResponseParser $parser */
        $parser = $this->voidParserFactory->create(['xml' => $response]);

        if ($parser->getResponse() !== ResponseParserInterface::PAYMENT_APPROVED) {
            throw new CommandException(__($parser->getMessage()));
        }
    }

    /**
     * Execute deny sale transaction command
     *
     * @param array $subject
     * @throws CommandException
     */
    public function executeDenySale(array $subject)
    {
        $payment = $this->reader->readPayment($subject);
        $method = $payment->getMethodInstance();
        $subject['void_node'] = DenyBuilder::VOID_REQUEST_SALE_NODE;

        $response = $this->client->post([
            'url'          => $this->getUrlByEnvironment($method->getConfigData('environment')),
            'body'         => $this->builder->build($subject),
            'debug'        => $method->getConfigData('debug'),
            'http_timeout' => $method->getConfigData('http_timeout'),
            'http_proxy'   => $method->getConfigData('http_proxy'),
        ]);

        /** @var VoidSaleResponseParser $parser */
        $parser = $this->voidSaleParserFactory->create(['xml' => $response]);

        if ($parser->getResponse() !== ResponseParserInterface::PAYMENT_APPROVED) {
            throw new CommandException(__($parser->getMessage()));
        }
    }
}
