<?php

namespace SMG\Api\Model;

use SMG\Api\Helper\InvoiceReconciliationHelper;
use SMG\Api\Api\InvoiceReconciliationManagementInterface;

class InvoiceReconciliationManagement implements InvoiceReconciliationManagementInterface
{
    /**
     * @var InvoiceReconciliationHelper
     */
    protected $_invoiceReconciliationHelper;

    /**
     * InvoiceReconciliationManagement constructor.
     *
     * @param InvoiceReconciliationHelper $invoiceReconciliationHelper
     */
    public function __construct(InvoiceReconciliationHelper $invoiceReconciliationHelper)
    {
        $this->_invoiceReconciliationHelper = $invoiceReconciliationHelper;
    }

    /**
     * Get the List of Desired Orders
     *
     * @return string
     */
    public function getOrders()
    {
        return $this->_invoiceReconciliationHelper->getOrders();
    }
}