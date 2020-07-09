<?php

namespace SMG\Api\Api;

use Magento\Framework\Webapi\Rest\Request;

use SMG\Api\Helper\ProductImportHelper;
use SMG\Api\Helper\RequestHelper;
use SMG\Api\Api\Interfaces\ProductImportManagementInterface;

class ProductImportManagement implements ProductImportManagementInterface
{
    /**
     * @var ProductImportHelper
     */
    protected $_productImportHelper;

    /**
     * @var Request
     */
    protected $_request;

    /**
     * @var RequestHelper
     */
    protected $_requestHelper;

    /**
     * ProductImportManagement constructor.
     *
     * @param ProductImportHelper $productImportHelper
     * @param Request $request
     * @param RequestHelper $requestHelper
     */
    public function __construct(ProductImportHelper $productImportHelper,
        Request $request,
        RequestHelper $requestHelper)
    {
        $this->_productImportHelper = $productImportHelper;
        $this->_request = $request;
        $this->_requestHelper = $requestHelper;
    }

    /**
     * This function will process the material
     * master data into magento
     *
     * @return string
     */
    public function processProductImport()
    {
        return $this->_productImportHelper->processProductImport($this->_requestHelper->getRequest($this->_request->getRequestData()));
    }
}