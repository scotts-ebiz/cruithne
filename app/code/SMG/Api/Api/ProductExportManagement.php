<?php

namespace SMG\Api\Api;

use SMG\Api\Helper\ProductExportHelper;
use SMG\Api\Api\Interfaces\ProductExportManagementInterface;

class ProductExportManagement implements ProductExportManagementInterface
{
    /**
     * @var ProductExportHelper
     */
    protected $_productExportHelper;

    /**
     * ProductManagement constructor.
     *
     * @param ProductExportHelper $productExportHelper
     */
    public function __construct(
        ProductExportHelper $productExportHelper
    ) {
        $this->_productExportHelper = $productExportHelper;
    }

    /**
     * This function will return formatted product information.
     *
     * @return mixed
     */
    public function processGetProductInfo()
    {
        return $this->_productExportHelper->getProductInfo();
    }
}