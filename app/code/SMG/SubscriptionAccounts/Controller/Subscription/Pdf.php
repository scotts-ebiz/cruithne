<?php

namespace SMG\SubscriptionAccounts\Controller\Subscription;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\ResultFactory;
use Recurly_Client;
use Recurly_NotFoundError;
use Recurly_Invoice;
use Recurly_InvoiceList;

class Pdf extends \Magento\Framework\App\Action\Action
{

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $_request;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $_customerSession;

    /**
     * @var \Magento\Customer\Model\Customer
     */
    protected $_customer;

    /**
     * @var \SMG\SubscriptionApi\Helper\RecurlyHelper
     */
    protected $_recurlyHelper;

    /**
     * Save constructor.
     * 
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\App\RequestInterface $request
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Customer\Model\Customer $customer
     * @param \SMG\SubscriptionApi\Helper\RecurlyHelper $recurlyHelper
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Customer\Model\Customer $customer,
        \SMG\SubscriptionApi\Helper\RecurlyHelper $recurlyHelper
    ) {
        $this->_request = $request;
        $this->_customerSession = $customerSession;
        $this->_customer = $customer;
        $this->_recurlyHelper = $recurlyHelper;
        parent::__construct($context);
    }

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
     * 
     * @return string
     */
    private function getCustomerId()
    {
        return $this->_customerSession->getCustomer()->getId();
    }

    /**
     * Return customer's Recurly account code
     * 
     * @return string|bool
     */
    private function getCustomerRecurlyAccountCode()
    {
        $customer = $this->_customer->load( $this->getCustomerId() );

        if( $customer->getRecurlyAccountCode() ) {
            return $customer->getRecurlyAccountCode();
        }

        return false;
    }

    /**
     * Return customer's invoice ids
     * 
     * @throws Recurly_NotFoundError
     * @return array
     */
    private function getCustomerInvoices()
    {
        Recurly_Client::$apiKey = $this->_recurlyHelper->getRecurlyPrivateApiKey();
        Recurly_Client::$subdomain = $this->_recurlyHelper->getRecurlySubdomain();

        $customerInvoiceNumbers = array();

        try {
            $invoices = Recurly_InvoiceList::getForAccount($this->getCustomerRecurlyAccountCode());

            foreach( $invoices as $invoice ) {
                array_push( $customerInvoiceNumbers, $invoice->invoice_number );
            }
            
           return $customerInvoiceNumbers;
        } catch (Recurly_NotFoundError $e) {
            print "Account not found: $e";
        }
    }

    /**
     * Return invoice pdf content
     * 
     * @param int $id
     * 
     * @throws Recurly_NotFoundError
     * @return string
     */
    private function getInvoicePdf( $id )
    {
        Recurly_Client::$apiKey = $this->_recurlyHelper->getRecurlyPrivateApiKey();
        Recurly_Client::$subdomain = $this->_recurlyHelper->getRecurlySubdomain();

        try {
            $pdf = Recurly_Invoice::getInvoicePdf( $id );

            return $pdf;
        } catch (Recurly_NotFoundError $e) {
            print "Invoice not found: $e";
        }
    }

}
