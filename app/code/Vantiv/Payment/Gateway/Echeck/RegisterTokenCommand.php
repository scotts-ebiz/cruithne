<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Gateway\Echeck;

use Vantiv\Payment\Gateway\Common\Client\HttpClient;
use Vantiv\Payment\Gateway\Common\SubjectReader as Reader;
use Vantiv\Payment\Gateway\Echeck\Builder\RegisterTokenBuilder as Builder;
use Vantiv\Payment\Gateway\Echeck\Parser\RegisterTokenResponseParserFactory as ParserFactory;
use Magento\Payment\Gateway\Command\CommandException;
use Vantiv\Payment\Gateway\Echeck\Parser\RegisterTokenResponseParser;
use Vantiv\Payment\Gateway\Common\Parser\ResponseParserInterface;
use Vantiv\Payment\Gateway\Echeck\Config\VantivEcheckConfig;
use Vantiv\Payment\Gateway\Common\AbstractCommand;

/**
 * Register token command implementation.
 */
class RegisterTokenCommand extends AbstractCommand
{
    /**
     * HTTP client instance.
     *
     * @var HttpClient
     */
    private $client = null;

    /**
     * Payment configuration instance.
     *
     * @var VantivEcheckConfig
     */
    private $config = null;

    /**
     * Get subject reader.
     *
     * @var Reader
     */
    private $reader = null;

    /**
     * Request builder.
     *
     * @var Builder
     */
    private $builder = null;

    /**
     * Parser factory.
     *
     * @var ParserFactory
     */
    private $parserFactory = null;

    /**
     * Constructor.
     *
     * @param HttpClient $client
     * @param VantivEcheckConfig $config
     * @param ParserFactory $parserFactory
     * @param Builder $builder
     * @param Reader $reader
     */
    public function __construct(
        HttpClient $client,
        VantivEcheckConfig $config,
        ParserFactory $parserFactory,
        Builder $builder,
        Reader $reader
    ) {
        $this->client = $client;
        $this->config = $config;
        $this->builder = $builder;
        $this->reader = $reader;
        $this->parserFactory = $parserFactory;
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
     * Get configuration instance.
     *
     * @return VantivEcheckConfig
     */
    private function getConfig()
    {
        return $this->config;
    }

    /**
     * Get subject reader.
     *
     * @return Reader
     */
    private function getReader()
    {
        return $this->reader;
    }

    /**
     * Get request builder.
     *
     * @return Builder
     */
    private function getBuilder()
    {
        return $this->builder;
    }

    /**
     * Get parser factory.
     *
     * @return ParserFactory
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
     */
    public function execute(array $subject)
    {
        $response = $this->getClient()->post([
            'url'          => $this->getUrlByEnvironment($this->getConfig()->getValue('environment')),
            'body'         => $this->getBuilder()->build($subject),
            'debug'        => $this->getConfig()->getValue('debug'),
            'http_timeout' => $this->getConfig()->getValue('http_timeout'),
            'http_proxy'   => $this->getConfig()->getValue('http_proxy'),
        ]);

        /** @var RegisterTokenResponseParser $parser */
        $parser = $this->getParserFactory()->create(['xml' => $response]);

        $successResponses = [
            ResponseParserInterface::TOKEN_PREVIOUSLY_REGISTERED,
            ResponseParserInterface::TOKEN_SUCCESSFULLY_REGISTERED,
        ];

        if (!in_array($parser->getResponse(), $successResponses)) {
            throw new CommandException(__($parser->getMessage()));
        }

        $tokenValue = $parser->getLitleToken();
        $this->getReader()->readPaymentToken($subject)->setGatewayToken($tokenValue);
    }
}
