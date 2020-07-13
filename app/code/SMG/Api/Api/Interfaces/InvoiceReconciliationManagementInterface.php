<?php

namespace SMG\Api\Api\Interfaces;

interface InvoiceReconciliationManagementInterface
{
    /**
     * This function will get the orders in a JSON format.
     *
     * @return string
     */
    public function getOrders();
}