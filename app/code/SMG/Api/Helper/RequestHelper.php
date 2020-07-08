<?php

namespace SMG\Api\Helper;

use Psr\Log\LoggerInterface;

class RequestHelper
{
    /**
     * @var LoggerInterface
     */
    protected $_logger;

    /**
     * RequestHelper constructor.
     *
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->_logger = $logger;
    }

    /**
     * Takes the request data from the request and because
     * it can be sent differently we need to check how it was sent and
     * process accordingly.
     *
     * @param $requestData
     * @return array|mixed
     */
    public function getRequest($requestData)
    {
        // set the response equal to the input
        $requestValue = $requestData;

        // check if the dates are provided
        // for some reason the call came back differently when ran
        // sometimes it would come back with one value in the array
        // as a JSON string other times it would come back with two
        // different values in the array so we needed to accommodate for them
        if (!empty($requestData) && count($requestData) === 1)
        {
            // get the first element from the request data
            $data = $requestData[0];

            // determine the type of data is in the $data bc json_decode
            // expects it should be either string or array
            if (gettype($data) === "string")
            {
                $requestValue = json_decode($requestData[0], true);
            }
            else
            {
                // create a new array
                $tempData = [];

                // add the data array to the new array
                $tempData[] = $data;

                // set the return value
                $requestValue = $tempData;
            }
        }

        // return
        return $requestValue;
    }
}