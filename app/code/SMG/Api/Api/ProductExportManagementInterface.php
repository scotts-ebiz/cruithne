<?php

namespace SMG\Api\Api;

interface ProductExportManagementInterface
{
    /**
     * This function will return formatted product information.
     *
     * @return SMG\Api\Model\ProductExportManagement[]
     */
    public function processGetProductInfo();
}