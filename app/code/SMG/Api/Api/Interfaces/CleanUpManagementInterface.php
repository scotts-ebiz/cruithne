<?php

namespace SMG\Api\Api\Interfaces;

interface CleanUpManagementInterface
{


    /**
     * This function will create an order and return it in a JSON format.
     *
     * @return string
     */
    public function cleanupDuplicateSubscriptions();

}
