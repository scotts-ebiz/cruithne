<?php

namespace SMG\Api\Model;

interface ShipmentManagementInterface
{
    /**
     * This function will process the orders
     * that have been set as ready to ship
     *
     * @return string
     */
    public function processShipment();
}