<?php

namespace SMG\Api\Model;

interface ConsumerDataManagementInterface
{
    /**
     * This function will get consumer data
     * to be used to upload to the consumer database
     *
     * @return string
     */
    public function getConsumerData();
}