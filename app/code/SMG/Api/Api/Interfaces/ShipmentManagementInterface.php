<?php

namespace SMG\Api\Api\Interfaces;

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