<?php

namespace SMG\Api\Model;

use Magento\Framework\Webapi\Rest\Request;

use SMG\Api\Helper\BatchCaptureHelper;
use SMG\Api\Api\BatchCaptureManagementInterface;

class BatchCaptureManagement implements BatchCaptureManagementInterface
{
    /**
     * @var BatchCaptureHelper
     */
    protected $_batchCaptureHelper;

    /**
     * @var Request
     */
    protected $_request;

    /**
     * BatchCaptureManagement constructor.
     *
     * @param BatchCaptureHelper $batchCaptureHelper
     * @param Request $request
     */
    public function __construct(BatchCaptureHelper $batchCaptureHelper, Request $request)
    {
        $this->_batchCaptureHelper = $batchCaptureHelper;
        $this->_request = $request;
    }

    /**
     * This function will capture the credit cards
     * for orders that have been properly processed
     * at SAP
     *
     * @return string
     */
    public function processBatchCapture()
    {
        return $this->_batchCaptureHelper->processBatchCapture();
    }
}