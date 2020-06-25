<?php

namespace SMG\Api\Model;

use Magento\Framework\Webapi\Rest\Request;

use SMG\Api\Helper\AuthReversalHelper;
use SMG\Api\Api\AuthReversalManagementInterface;

class AuthReversalManagement implements AuthReversalManagementInterface
{
    /**
     * @var AuthReversalHelper
     */
    protected $_authReversalHelper;

    /**
     * @var Request
     */
    protected $_request;

    /**
     * BatchCaptureManagement constructor.
     *
     * @param AuthReversalHelper $authReversalHelper
     * @param Request $request
     */
    public function __construct(AuthReversalHelper $authReversalHelper, Request $request)
    {
        $this->_authReversalHelper = $authReversalHelper;
        $this->_request = $request;
    }

    /**
     * This function will process all of the
     * unauthorization requests to the credit
     * card
     *
     * @return string
     */
    public function processAuthReversal()
    {
        return $this->_authReversalHelper->processAuthReversal();
    }
}