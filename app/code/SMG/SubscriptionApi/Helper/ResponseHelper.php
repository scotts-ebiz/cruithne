<?php

namespace SMG\SubscriptionApi\Helper;

use Magento\Framework\App\Helper\Context;
use Magento\Framework\Webapi\Rest\Response;
use Magento\Framework\App\Helper\AbstractHelper;

class ResponseHelper extends AbstractHelper
{
    /**
     * @var Response
     */
    protected $_response;

    public function __construct(
        Response $response,
        Context $context
    ) {
        parent::__construct($context);

        $this->_response = $response;
    }

    /**
     * Send a success response.
     *
     * @param string $message
     * @param array $data
     * @return string
     */
    public function success($message, $data = [])
    {
        $this->_response->setHttpResponseCode(200);

        $response = [
            'data' => $data,
            'message' => $message,
            'status' => 'success',
        ];

        return json_encode($response);
    }

    /**
     * Send an error response.
     *
     * @param string $message
     * @param array $data
     * @param int $code
     * @return string
     */
    public function error($message, $data = [], $code = 400)
    {
        $this->_response->setHttpResponseCode($code);

        $response = [
            'data' => $data,
            'message' => $message,
            'status' => 'error',
        ];

        return json_encode($response);
    }
}
