<?php

namespace SMG\Api\Api\Interfaces;

interface HealthCheckInterface
{
    /**
     * This function will return true or false.
     *
     * @return boolean
     */
    public function getHealthCheck();
}