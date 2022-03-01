<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Gateway\Common;

use Vantiv\Payment\Gateway\Common\Client\HttpClient as Client;
use Vantiv\Payment\Gateway\Common\SubjectReader as Reader;
use Vantiv\Payment\Gateway\Common\Builder\RequestBuilderInterface as Builder;
use Vantiv\Payment\Gateway\Common\Parser\ResponseParserInterface;

/**
 * Abstract command implementation.
 */
abstract class AbstractPaymentCommand extends AbstractCommand
{
    /**
     * Subject reader.
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
     * HTTP client instance.
     *
     * @var Client
     */
    private $client = null;

    /**
     * Response parser factory.
     *
     * @var mixed
     */
    private $parserFactory = null;

    /**
     * Constructor.
     *
     * @param Reader $reader
     * @param Builder $builder
     * @param Client $client
     * @param mixed $parserFactory
     */
    public function __construct(
        Reader $reader,
        Builder $builder,
        Client $client,
        $parserFactory
    ) {
        $this->reader = $reader;
        $this->builder = $builder;
        $this->client = $client;
        $this->parserFactory = $parserFactory;
    }

    /**
     * Execute Authorize Command.
     *
     * @param array $subject
     * @throws CommandException
     * @return void
     */
    public function execute(array $subject)
    {
        $method = $this->getReader()->readPayment($subject)->getMethodInstance();

        $response = $this->getClient()->post([
            'url'         => $this->getUrlByEnvironment($method->getConfigData('environment')),
            'body'        => $this->getBuilder()->build($subject),
            'debug'       => $method->getConfigData('debug'),
            'http_timeout' => $method->getConfigData('http_timeout'),
            'http_proxy'   => $method->getConfigData('http_proxy'),
        ]);

        /** @var ResponseParserInterface $parser */
        $parser = $this->getParserFactory()->create([
            'xml' => $response,
        ]);

        /*
         * Handle response.
         */
        $this->handle($subject, $parser);
    }

    /**
     * Each command must implement own handling.
     *
     * @param array $subject
     * @param ResponseParserInterface $parser
     */
    abstract protected function handle(array $subject, ResponseParserInterface $parser);

    /**
     * Get subject reader.
     *
     * @return Reader
     */
    protected function getReader()
    {
        return $this->reader;
    }

    /**
     * Get request builder.
     *
     * @return Builder
     */
    protected function getBuilder()
    {
        return $this->builder;
    }

    /**
     * Get HTTP client.
     *
     * @return Client
     */
    protected function getClient()
    {
        return $this->client;
    }

    /**
     * Get parser factory.
     *
     * @return mixed
     */
    protected function getParserFactory()
    {
        return $this->parserFactory;
    }
}
