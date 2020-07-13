<?php

namespace SMG\Api\Helper;

class ResponseHelper
{
    /**
     * Create the Response
     *
     * @param $isSuccess
     * @param $message
     * @return string
     */
    public function createResponse($isSuccess, $message)
    {
        // variables
        $response = array();

        // determine if this is a success or failure response
        if ($isSuccess)
        {
            $response['statusCode'] = '200';
            $response['statusMessage'] = 'Success';
            $response['response'] = $message;
        }
        else
        {
            $response['statusCode'] = '300';
            $response['statusMessage'] = $message;
            $response['response'] = null;
        }

        // return
        return json_encode($response);
    }

    /**
     * Create the Response as a Failure with a more
     * detailed error code and error message
     *
     * Error Code can not be the following.
     *  - 200 - Success
     *  - 300 - Failure (This is generic default failure code)
     *
     * @param $isSuccess
     * @param $message
     * @return string
     */
    public function createErrorResponse($statusCode, $statusMessage, $message)
    {
        // variables
        $response = array();

        $response['statusCode'] = $statusCode;
        $response['statusMessage'] = $statusMessage;
        $response['response'] = $message;

        // return
        return json_encode($response);
    }
}