<?php

namespace SMG\Api\Api\Interfaces;

interface InvoiceReconciliationSentManagementInterface
{
    /**
     * This function will get the orders in a JSON format.
     *
     * @return string
     */
    public function updateOrders();
}