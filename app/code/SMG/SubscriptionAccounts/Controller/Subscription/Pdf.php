<?php

namespace SMG\SubscriptionAccounts\Controller\Subscription;

use Magento\Backend\App\Action\Context;
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Psr\Log\LoggerInterface;
use Recurly_Client;
use Recurly_Invoice;
use Recurly_InvoiceList;
use SMG\SubscriptionApi\Helper\RecurlyHelper;

/**
 * Class Pdf
 * @package SMG\SubscriptionAccounts\Controller\Subscription
 */
class Pdf extends Action
{

    /**
     * @var RequestInterface
     */
    protected $_request;

    /**
     * @var CustomerSession
     */
    protected $_customerSession;

    /**
     * @var Customer
     */
    protected $_customer;

    /**
     * @var RecurlyHelper
     */
    protected $_recurlyHelper;

    /**
     * @var LoggerInterface
     */
    protected $_logger;

    /**
     * Pdf constructor.
     * @param Context $context
     * @param RequestInterface $request
     * @param CustomerSession $customerSession
     * @param Customer $customer
     * @param RecurlyHelper $recurlyHelper
     * @param LoggerInterface $logger
     */
    public function __construct(
        Context $context,
        RequestInterface $request,
        CustomerSession $customerSession,
        Customer $customer,
        RecurlyHelper $recurlyHelper,
        LoggerInterface $logger
    ) {
        $this->_request = $request;
        $this->_customerSession = $customerSession;
        $this->_customer = $customer;
        $this->_recurlyHelper = $recurlyHelper;
        $this->_logger = $logger;
        parent::__construct($context);
    }

    /**
     * Execute
     * @return ResponseInterface|ResultInterface
     */
    public function execute()
    {
        $request = $this->_request->getParams();

        // Check if invoice ID exists and this is current customer's invoice
        if( ! empty( $request['invoice'] ) && in_array( $request['invoice'], $this->getCustomerInvoices() ) ) {
            header( 'Content-type: application/pdf' );
            echo $this->getInvoicePdf( $request['invoice'] );
        } else {
            echo 'Invoice does\'t exist or is not yours. Redirecting back...';
            $resultRedirect = $this->resultFactory->create( ResultFactory::TYPE_REDIRECT );
            $resultRedirect->setUrl( $this->_redirect->getRefererUrl() );
            return $resultRedirect;
        }
    }

    /**
     * Return customer id
     * @return string
     */
    private function getCustomerId()
    {
        return $this->_customerSession->getCustomer()->getId();
    }

    /**
     * Return customer's Recurly account code
     * @return string|bool
     */
    private function getGigyaUid()
    {
        $customer = $this->_customer->load( $this->getCustomerId() );

        if( $customer->getGigyaUid() ) {
            return $customer->getGigyaUid();
        }

        return false;
    }

    /**
     * Return customer's invoice ids
     * @return array
     */
    private function getCustomerInvoices()
    {
        Recurly_Client::$apiKey = $this->_recurlyHelper->getRecurlyPrivateApiKey();
        Recurly_Client::$subdomain = $this->_recurlyHelper->getRecurlySubdomain();

        $customerInvoiceNumbers = array();

        try {
            $invoices = Recurly_InvoiceList::getForAccount($this->getGigyaUid());
            foreach( $invoices as $invoice ) {
                array_push( $customerInvoiceNumbers, $invoice->invoice_number );
            }
           return $customerInvoiceNumbers;
        } catch (\Exception $e) {
            $error = "Account not found: $e";
            $this->_logger->error($error);
            echo $error;
        }
    }

    /**
     * Return invoice pdf content
     * @param int $id
     * @return string
     */
    private function getInvoicePdf( $id )
    {
        Recurly_Client::$apiKey = $this->_recurlyHelper->getRecurlyPrivateApiKey();
        Recurly_Client::$subdomain = $this->_recurlyHelper->getRecurlySubdomain();

        try {
            return Recurly_Invoice::getInvoicePdf( $id );
        } catch (\Exception $e) {
            $error = "Invoice not found: $e";
            $this->_logger->error($error);
            echo $error;
        }
    }

}
