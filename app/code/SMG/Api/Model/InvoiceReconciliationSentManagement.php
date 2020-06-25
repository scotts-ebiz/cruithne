<?php

namespace SMG\Api\Model;

use Magento\Framework\Webapi\Rest\Request;

use SMG\Api\Helper\InvoiceReconciliationSentHelper;
use SMG\Api\Helper\RequestHelper;
use SMG\Api\Api\InvoiceReconciliationSentManagementInterface;

class InvoiceReconciliationSentManagement implements InvoiceReconciliationSentManagementInterface
{
    /**
     * @var InvoiceReconciliationSentHelper
     */
    protected $_invoiceReconciliationSentHelper;

    /**
     * @var Request
     */
    protected $_request;

    /**
     * @var RequestHelper
     */
    protected $_requestHelper;

    /**
     * OrdersManagement constructor.
     *
     * @param InvoiceReconciliationSentHelper $invoiceReconciliationSentHelper
     * @param Request $request
     * @param RequestHelper $requestHelper
     */
    public function __construct(InvoiceReconciliationSentHelper $invoiceReconciliationSentHelper,
        Request $request,
        RequestHelper $requestHelper)
    {
        $this->_invoiceReconciliationSentHelper = $invoiceReconciliationSentHelper;
        $this->_request = $request;
        $this->_requestHelper = $requestHelper;
    }

    /**
     * Update the orders to notify that the invoice reconciliation was
     * sent to SAP successfully
     *
     * @return string
     */
    public function updateOrders()
    {
        return $this->_invoiceReconciliationSentHelper->updateOrders($this->_requestHelper->getRequest($this->_request->getRequestData()));
    }
}