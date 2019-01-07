<?php

namespace SMG\Api\Helper;

class RequestHelper
{
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
            $requestValue = json_decode($requestData[0], true);
        }

        // return
        return $requestValue;
    }
}