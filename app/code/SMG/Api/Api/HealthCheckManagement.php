<?php

namespace SMG\Api\Api;

use Magento\Framework\Webapi\Rest\Request;

use SMG\Api\Helper\HealthCheckHelper;
use SMG\Api\Api\Interfaces\HealthCheckInterface;

class HealthCheckManagement implements HealthCheckInterface
{
    /**
     * @var HealthCheckHelper
     */
    protected $_healthCheckHelper;
   
    /**
     * HealthCheckManagement constructor.
     *
     * @param HealthCheckHelper $healthCheckHelper
     */
    public function __construct(HealthCheckHelper $healthCheckHelper)
    {
        $this->_healthCheckHelper = $healthCheckHelper;
    }

    /**
     * Get the health status of magento
     *
     * @return boolean
     */
    public function getHealthCheck()
    {
        return $this->_healthCheckHelper->getHealthCheck();
    }
}
