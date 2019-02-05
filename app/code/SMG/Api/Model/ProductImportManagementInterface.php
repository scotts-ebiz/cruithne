<?php

namespace SMG\Api\Model;

interface ProductImportManagementInterface
{
    /**
     * This function will process the material
     * master data into magento
     *
     * @return string
     */
    public function processProductImport();
}