<?php

namespace SMG\Api\Model;

interface ProductManagementInterface
{
    /**
     * This function will return formatted product information.
     *
     * @return array
     */
    public function processGetProductInfo();
}