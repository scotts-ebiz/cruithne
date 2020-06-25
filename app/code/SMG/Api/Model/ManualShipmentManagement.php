<?php

namespace SMG\Api\Model;

use Magento\Framework\Webapi\Rest\Request;

use SMG\Api\Helper\ManualShipmentHelper;
use SMG\Api\Api\ManualShipmentManagementInterface;
use SMG\Api\Helper\RequestHelper;

class ManualShipmentManagement implements ManualShipmentManagementInterface
{
    /**
     * @var ManualShipmentHelper
     */
    protected $_manualShipmentHelper;

    /**
     * @var Request
     */
    protected $_request;
    
    /**
     * @var RequestHelper
     */
    protected $_requestHelper;

    /**
     * ManualShipmentManagement constructor.
     * 
     * @param ManualShipmentHelper $manualShipmentHelper
     * @param Request $request
     * @param RequestHelper $requestHelper
     */
    public function __construct(ManualShipmentHelper $manualShipmentHelper,
    	Request $request,
    	RequestHelper $requestHelper)
    {
        $this->_manualShipmentHelper = $manualShipmentHelper;
        $this->_request = $request;
        $this->_requestHelper = $requestHelper;
    }

    /**
     * This function will process the orders
     * sent in the json file
     *
     * @return string
     */
    public function processShipment()
    {
        return $this->_manualShipmentHelper->processShipment($this->_requestHelper->getRequest($this->_request->getRequestData()));
    }
}