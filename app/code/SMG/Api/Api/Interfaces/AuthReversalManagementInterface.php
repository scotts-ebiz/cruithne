<?php

namespace SMG\Api\Api\Interfaces;

interface AuthReversalManagementInterface
{
    /**
     * This function will process all of the
     * unauthorization requests to the credit
     * card
     *
     * @return string
     */
    public function processAuthReversal();
}