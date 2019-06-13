<?php

namespace SMG\Api\Model;

interface InvoiceReconciliationSentManagementInterface
{
    /**
     * This function will get the orders in a JSON format.
     *
     * @return string
     */
    public function updateOrders();
}