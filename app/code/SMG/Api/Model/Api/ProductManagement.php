<?php

namespace SMG\Api\Model\Api;

use SMG\Api\Helper\ProductHelper;
use SMG\Api\Model\ProductManagementInterface;

class ProductManagement implements ProductManagementInterface
{
    /**
     * @var ProductHelper
     */
    protected $_productHelper;

    /**
     * ProductManagement constructor.
     *
     * @param ProductHelper $productHelper
     */
    public function __construct(
        ProductHelper $productHelper
    ) {
        $this->_productHelper = $productHelper;
    }

    /**
     * This function will return formatted product information.
     *
     * @return array
     */
    public function processGetProductInfo()
    {
        return $this->_productHelper->getProductInfo();
    }
}
