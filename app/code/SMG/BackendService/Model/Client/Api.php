<?php

namespace SMG\BackendService\Model\Client;

use \GuzzleHttp\Client;
use \GuzzleHttp\ClientFactory;
use \GuzzleHttp\Exception\GuzzleException;
use \GuzzleHttp\Psr7\Response;
use \GuzzleHttp\Psr7\ResponseFactory;
use \Magento\Framework\Webapi\Rest\Request;
use \Psr\Log\LoggerInterface;
use Magento\Backend\Model\Auth\Session;
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
     * @var Session
     */
    private $session;

    /**
     * Api constructor.
     * @param ClientFactory $clientFactory
     * @param ResponseFactory $responseFactory
     * @param LoggerInterface $logger
     * @param Config $config
     * @param Session $session
     */
    public function __construct(
        ClientFactory $clientFactory,
        ResponseFactory $responseFactory,
        LoggerInterface $logger,
        Config $config,
        Session $session
    ) {
        $this->clientFactory = $clientFactory;
        $this->responseFactory = $responseFactory;
        $this->logger = $logger;
        $this->config = $config;
        $this->session = $session;
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
        if ($this->session->isLoggedIn()) {
            $apiUrl = $apiEndPoint . $apiFunction;

            $headers = ['x-apikey' => $this->config->getApikey()];
            $client = $this->clientFactory->create(['config' => [
                'headers' => $headers
            ]]);

            try {

                $this->logger->info(
                    sprintf('API %s : %s', $apiUrl, print_r($params))
                );

                $response = $client->request($requestMethod, $apiUrl, $params);

                $this->logger->info(
                    sprintf('Response from API %s : %s', $apiUrl, print_r($response))
                );

                if ($response->getStatusCode() == "200") {
                    return $response->getBody();
                }

            } catch (\Exception $ex) {

                $this->logger->info(sprintf('API Exception %s : %s', $apiUrl, $ex->getMessage()));

                return false;
            }

            return false;
        }
    }

}
