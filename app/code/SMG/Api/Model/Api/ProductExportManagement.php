<?php

namespace SMG\Api\Model\Api;

use SMG\Api\Helper\ProductExportHelper;
use SMG\Api\Model\ProductExportManagementInterface;

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
     * @return array
     */
    public function processGetProductInfo()
    {
        return $this->_productExportHelper->getProductInfo();
    }
}