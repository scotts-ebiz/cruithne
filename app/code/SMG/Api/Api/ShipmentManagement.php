<?php

namespace SMG\Api\Api;

use Magento\Framework\Webapi\Rest\Request;

use SMG\Api\Helper\ShipmentHelper;
use SMG\Api\Api\Interfaces\ShipmentManagementInterface;

class ShipmentManagement implements ShipmentManagementInterface
{
    /**
     * @var ShipmentHelper
     */
    protected $_shipmentHelper;

    /**
     * @var Request
     */
    protected $_request;

    /**
     * ShipmentManagement constructor.
     *
     * @param $shipmentHelper $shipmentHelper
     * @param Request $request
     */
    public function __construct(ShipmentHelper $shipmentHelper, Request $request)
    {
        $this->_shipmentHelper = $shipmentHelper;
        $this->_request = $request;
    }

    /**
     * This function will process the orders
     * that have been set as ready to ship
     *
     * @return string
     */
    public function processShipment()
    {
        return $this->_shipmentHelper->processShipment();
    }
}