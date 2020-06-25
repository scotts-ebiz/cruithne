<?php

namespace SMG\Api\Api;

interface BatchCaptureManagementInterface
{
    /**
     * This function will capture the credit cards
     * for orders that have been properly processed
     * at SAP
     *
     * @return string
     */
    public function processBatchCapture();
}