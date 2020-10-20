<?php

namespace SMG\BackendService\Model\Client;

use \GuzzleHttp\Client;
use \GuzzleHttp\ClientFactory;
use \GuzzleHttp\Exception\GuzzleException;
use \GuzzleHttp\Psr7\Response;
use \GuzzleHttp\Psr7\ResponseFactory;
use \Magento\Framework\Webapi\Rest\Request;
use \Psr\Log\LoggerInterface;

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
     * Api constructor.
     * @param ClientFactory $clientFactory
     * @param ResponseFactory $responseFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        ClientFactory $clientFactory,
        ResponseFactory $responseFactory,
        LoggerInterface $logger
    ) {
        $this->clientFactory = $clientFactory;
        $this->responseFactory = $responseFactory;
        $this->logger = $logger;
    }

    /**
     * @param string $apiEndPoint
     * @param string $apiFunction
     * @param array $params
     * @param string $requestMethod
     * @return bool
     */
    public function execute(
        $apiEndPoint,
        $apiFunction,
        $params,
        $requestMethod = Request::HTTP_METHOD_POST
    ) {
        $apiUrl = $apiEndPoint . $apiFunction;
        $client = $this->clientFactory->create(['config' => [
            'base_uri' => $apiUrl
        ]]);

        try {
            $this->logger->info(
                sprintf('API %s : %s', $apiUrl, print_r($params))
            );
            $response = $client->request($requestMethod, $params);

            $this->logger->info(
                sprintf('Response from API %s : %s', $apiUrl, print_r($response))
            );

            if ($response->getStatusCode() == "200") {
                return $response->getBody();
            }
        } catch (\Exception $ex) {
            $this->logger->info(
                sprintf('API Exception %s : %s', $apiUrl, $ex->getMessage())
            );
            return false;
        }

        return false;
    }

}
