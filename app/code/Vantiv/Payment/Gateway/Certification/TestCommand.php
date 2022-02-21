<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Gateway\Certification;

use Vantiv\Payment\Gateway\Common\AbstractCommand;
use Vantiv\Payment\Gateway\Common\Client\HttpClient;
use Vantiv\Payment\Gateway\Common\Config\VantivCustomConfig;
use Magento\Payment\Gateway\Command\CommandException;
use Vantiv\Payment\Model\Config\Source\VantivEnvironment;

/**
 * Class Vantiv Gateway TestCommand
 *
 * @api
 */
class TestCommand extends AbstractCommand
{
    /**
     * HTTP client instance.
     *
     * @var HttpClient
     */
    private $client = null;

    /**
     * Custom configuration instance.
     *
     * @var VantivCustomConfig
     */
    private $config = null;

    /**
     * Constructor.
     *
     * @param HttpClient $client
     * @param VantivCustomConfig $config
     */
    public function __construct(
        HttpClient $client,
        VantivCustomConfig $config
    ) {
        $this->client = $client;
        $this->config = $config;
    }

    /**
     * Get HTTP client instance.
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
     * @return VantivCustomConfig
     */
    protected function getConfig()
    {
        return $this->config;
    }

    /**
     * Execute Test Command
     *
     * @param array $subject
     * @throws CommandException
     */
    public function execute(array $subject)
    {
        throw new CommandException("Not implemented");
    }

    /**
     * Performs API call to Vantiv API and returns result.
     *
     * @param string $request Request Body
     * @param string $storeId Store ID
     * @return string
     */
    public function call($request, $storeId = null)
    {
        $response = $this->getClient()->post([
            'url'          => $this->getUrlByEnvironment(VantivEnvironment::PRELIVE),
            'body'         => $request,
            'debug'        => $this->getConfig()->getValue('debug'),
            'http_timeout' => $this->getConfig()->getValue('http_timeout'),
            'http_proxy'   => $this->getConfig()->getValue('http_proxy'),
        ]);

        return $response;
    }
}
