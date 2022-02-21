<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Gateway\Common;

use Vantiv\Payment\Gateway\Common\Client\HttpClient;
use Vantiv\Payment\Gateway\Common\Config\VantivCustomConfig;

/**
 * Class Vantiv Gateway AbstractCustomCommand
 *
 * @api
 */
abstract class AbstractCustomCommand extends AbstractCommand
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
     * Performs API call to Vantiv API and returns result.
     *
     * @param string $request Request Body
     * @param string $storeId Store ID
     * @return $string
     */
    public function call($request, $storeId = null)
    {
        $url = $this->getUrlByEnvironment($this->getConfig()->getValue('environment', $storeId));

        $response = $this->getClient()->post([
            'url'          => $this->getUrlByEnvironment($this->getConfig()->getValue('environment', $storeId)),
            'body'         => $request,
            'http_proxy'   => $this->getConfig()->getValue('http_proxy', $storeId),
            'http_timeout' => $this->getConfig()->getValue('http_timeout', $storeId),
            'debug'        => $this->getConfig()->getValue('debug', $storeId),
        ]);

        return $response;
    }
}
