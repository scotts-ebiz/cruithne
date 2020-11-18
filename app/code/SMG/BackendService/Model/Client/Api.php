<?php

namespace SMG\BackendService\Model\Client;

use \GuzzleHttp\Client;
use \GuzzleHttp\ClientFactory;
use GuzzleHttp\Exception\ClientException;
use \GuzzleHttp\Exception\GuzzleException;
use \GuzzleHttp\Psr7\Response;
use \GuzzleHttp\Psr7\ResponseFactory;
use \Magento\Framework\Webapi\Rest\Request;
use \Psr\Log\LoggerInterface;

use SMG\BackendService\Helper\Data as Config;

class Api
{

    /**
     * @var ClientFactory
     */
    private $clientFactory;

    /**
     * @var ResponseFactory
     */
    private $responseFactory;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Config
     */
    private $config;

    /**
     * Api constructor.
     * @param ClientFactory $clientFactory
     * @param ResponseFactory $responseFactory
     * @param LoggerInterface $logger
     * @param Config $config
     */
    public function __construct(
        ClientFactory $clientFactory,
        ResponseFactory $responseFactory,
        LoggerInterface $logger,
        Config $config
    ) {
        $this->clientFactory = $clientFactory;
        $this->responseFactory = $responseFactory;
        $this->logger = $logger;
        $this->config = $config;
    }

    /**
     * @param $apiEndPoint
     * @param $apiFunction
     * @param $params
     * @param string $requestMethod
     * @param bool $excludeLogin
     * @return bool
     */
    public function execute(
        $apiEndPoint,
        $apiFunction,
        $params,
        $requestMethod = Request::HTTP_METHOD_POST,
        $excludeLogin = true
    ) {
        if ($excludeLogin) {
            $apiUrl = $apiEndPoint . $apiFunction;

            $headers = ['x-apikey' => $this->config->getApikey()];
            $client = $this->clientFactory->create(['config' => [
                'headers' => $headers
            ]]);

            try {

                $this->logger->info(
                    sprintf('API %s : %s', $apiUrl, print_r($params ?? "empty", true))
                );

                $response = $client->request($requestMethod, $apiUrl, ['json' => $params]);

                $this->logger->info(
                    sprintf('Response from API %s : %s', $apiUrl, print_r($response ?? "empty", true))
                );

                if ($response->getStatusCode() == "200") {
                    return $response->getBody();
                }

            } catch (ClientException $e) {

                $this->logger->info(sprintf('API Exception %s : %s', $apiUrl, $e->getResponse()->getBody()->getContents()));

                return false;
            } catch (\Exception $ex) {

                $this->logger->info(sprintf('API Exception %s : %s', $apiUrl, $ex->getMessage()));

                return false;
            }

            return false;
        }
    }

}
