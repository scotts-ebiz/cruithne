<?php

namespace SMG\Api\Api;

interface ManualShipmentManagementInterface
{
    /**
     * This function will process the orders
     * sent in the json file
     *
     * @return string
     */
    public function processShipment();
}