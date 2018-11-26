<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Gateway\Cc;

use Vantiv\Payment\Gateway\Common\Client\HttpClient as Client;
use Vantiv\Payment\Gateway\Common\SubjectReader as Reader;
use Vantiv\Payment\Gateway\Cc\Builder\RegisterTokenBuilder as Builder;
use Vantiv\Payment\Gateway\Cc\Parser\RegisterTokenResponseParserFactory as ParserFactory;

use Magento\Payment\Gateway\Command\CommandException;
use Vantiv\Payment\Gateway\Common\Parser\ResponseParserInterface;
use Vantiv\Payment\Gateway\Cc\Config\VantivCcConfig;
use Vantiv\Payment\Gateway\Common\AbstractCommand;

/**
 * Register token command implementation.
 */
class RegisterTokenCommand extends AbstractCommand
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
     * @var ParserFactory
     */
    private $parserFactory = null;

    /**
     * Payment configuration instance.
     *
     * @var VantivCcConfig
     */
    private $config = null;

    /**
     * Constructor.
     *
     * @param Client $client
     * @param ParserFactory $parserFactory
     * @param Builder $builder
     * @param Reader $reader
     * @param VantivCcConfig $config
     */
    public function __construct(
        Client $client,
        ParserFactory $parserFactory,
        Builder $builder,
        Reader $reader,
        VantivCcConfig $config
    ) {
        $this->client = $client;
        $this->reader = $reader;
        $this->builder = $builder;
        $this->parserFactory = $parserFactory;
        $this->config = $config;
    }

    /**
     * Run token command.
     *
     * @param array $subject
     */
    public function execute(array $subject)
    {
        $response = $this->client->post([
            'url'          => $this->getUrlByEnvironment($this->config->getValue('environment')),
            'body'         => $this->builder->build($subject),
            'debug'        => $this->config->getValue('debug'),
            'http_timeout' => $this->config->getValue('http_timeout'),
            'http_proxy'   => $this->config->getValue('http_proxy'),
        ]);

        /** @var ResponseParserInterface $parser */
        $parser = $this->parserFactory->create([
            'xml' => $response,
        ]);

        $successResponses = [
            ResponseParserInterface::TOKEN_PREVIOUSLY_REGISTERED,
            ResponseParserInterface::TOKEN_SUCCESSFULLY_REGISTERED,
        ];

        if (!in_array($parser->getResponse(), $successResponses)) {
            throw new CommandException(__($parser->getMessage()));
        }

        $tokenValue = $parser->getLitleToken();

        $lastFour = (array_key_exists('last_four', $subject) && strlen($subject['last_four']))
            ? $subject['last_four']
            : substr($tokenValue, -4);

        $expMonth = (array_key_exists('exp_month', $subject) && strlen($subject['exp_month']))
            ? $subject['exp_month']
            : '--';

        $expYear = (array_key_exists('exp_year', $subject) && strlen($subject['exp_year']))
            ? $subject['exp_year']
            : '--';

        $ccType = $parser->getLitleTokenType() ? $parser->getLitleTokenType() :
            isset($subject['cc_type']) ? $subject['cc_type'] : null;

        $details = [
            'ccType' => $ccType,
            'ccLast4' => $lastFour,
            'ccExpMonth' => $expMonth,
            'ccExpYear' => $expYear,
        ];

        $token = $this->reader->readPaymentToken($subject);
        $token->setGatewayToken($tokenValue);
        $token->setTokenDetails(json_encode($details));
    }
}
