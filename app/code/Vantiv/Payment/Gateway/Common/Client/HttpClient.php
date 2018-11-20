<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Gateway\Common\Client;

use Magento\Payment\Gateway\Http\ClientException;
use Vantiv\Payment\Model\Logger\Logger;

/**
 * HTTP Client.
 */
class HttpClient
{
    /**
     * Logger instance.
     *
     * @var Logger
     */
    private $logger = null;

    /**
     * Constructor.
     *
     * @param Logger $logger
     */
    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Get debug logger.
     *
     * @return Logger
     */
    private function getLogger()
    {
        return $this->logger;
    }

    /**
     * Make API request.
     *
     * @param array $request
     * @return string
     */
    public function post(array $request)
    {
        $curl = curl_init();

        curl_setopt($curl, CURLOPT_PROXY, $request['http_proxy']);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-type: text/xml','Expect: ']);
        curl_setopt($curl, CURLOPT_URL, $request['url']);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $request['body']);
        curl_setopt($curl, CURLOPT_HTTPPROXYTUNNEL, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, $request['http_timeout']);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSLVERSION, 6);

        $response = curl_exec($curl);

        $errorStatus = false;
        $errorMessage = '';

        if (curl_errno($curl) !== 0) {
            $errorStatus = true;
            $errorMessage = curl_error($curl);
        } elseif (curl_getinfo($curl, CURLINFO_HTTP_CODE) != '200') {
            $errorStatus = true;
            $errorMessage = 'HTTP Status Code: ' . curl_getinfo($curl, CURLINFO_HTTP_CODE);
        } elseif (empty($response)) {
            $errorStatus = true;
            $errorMessage = 'Empty Response.';
        }

        curl_close($curl);

        if ($request['debug']) {
            $debug = [
                'url'      => $request['url'],
                'request'  => $request['body'],
                'response' => $response,
                'error'    => $errorMessage,
            ];
            $this->getLogger()->debug(print_r($debug, true));
        }

        if ($errorStatus) {
            throw new ClientException(__($errorMessage));
        }

        return $response;
    }
}
