<?php

namespace SMG\Api\Api\Interfaces;

interface ProductExportManagementInterface
{
    /**
     * This function will return formatted product information.
     *
     * @return mixed
     */
    public function processGetProductInfo();
}