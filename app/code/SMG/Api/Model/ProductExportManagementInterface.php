<?php

namespace SMG\Api\Model;

interface ProductExportManagementInterface
{
    /**
     * This function will return formatted product information.
     *
     * @return array
     */
    public function processGetProductInfo();
}