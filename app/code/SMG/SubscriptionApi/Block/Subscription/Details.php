<?php
namespace SMG\SubscriptionApi\Block\Subscription;

use Recurly_Client;
use Recurly_SubscriptionList;
use Recurly_NotFoundError;
use Recurly_Invoice;
use Recurly_InvoiceList;
use Recurly_BillingInfo;

class Details extends \Magento\Framework\View\Element\Template
{
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
     * Subscriptions block constructor.
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Customer\Model\Customer $customer
     * @param \SMG\SubscriptionApi\Helper\RecurlyHelper $recurlyHelper
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Customer\Model\Customer $customer,
        \SMG\SubscriptionApi\Helper\RecurlyHelper $recurlyHelper,
        array $data = []
    ) {
        $this->_customerSession = $customerSession;
        $this->_customer = $customer;
        $this->_recurlyHelper = $recurlyHelper;
        parent::__construct($context, $data);
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

    public function getSubscriptions()
    {
        Recurly_Client::$apiKey = $this->_recurlyHelper->getRecurlyPrivateApiKey();
        Recurly_Client::$subdomain = $this->_recurlyHelper->getRecurlySubdomain();

        $isAnnualSubscription = false;
        $annualSubscription = array();
        $addonSubscription = array();
        $futureSubscription = array();
        $subscriptionIds = array();
        $allSubscriptions = array();

        try {
            $activeSubscriptions = Recurly_SubscriptionList::getForAccount( $this->getCustomerRecurlyAccountCode(), [ 'state' => 'active' ] );
            $futureSubscriptions = Recurly_SubscriptionList::getForAccount( $this->getCustomerRecurlyAccountCode(), [ 'state' => 'future' ] );

            foreach( $activeSubscriptions as $subscription ) {
                if( $subscription->plan->plan_code == 'annual' ) {
                    $isAnnualSubscription = true;
                    array_push( $annualSubscription, $subscription );
                }
                if( $subscription->plan->plan_code = 'add-ons' ) {
                    array_push( $addonSubscription, $subscription );
                }
                array_push( $allSubscriptions, $subscription );
            }

            foreach( $futureSubscriptions as $subscription ) {
                array_push( $futureSubscription, $subscription );
                array_push( $allSubscriptions, $subscription );
            }

            return array( 
                'success'               => true,
                'is_annual'             => $isAnnualSubscription,
                'annual_subscription'   => $annualSubscription,
                'addon_subscription'    => $addonSubscription,
                'all'                   => $allSubscriptions,
            );

        } catch (Recurly_NotFoundError $e) {
            return array(
                'success' => false,
                'error_message' => $e->getMessage()
            );
        }
    }

    public function getInvoices() {
        Recurly_Client::$apiKey = $this->_recurlyHelper->getRecurlyPrivateApiKey();
        Recurly_Client::$subdomain = $this->_recurlyHelper->getRecurlySubdomain();

        $invoiceIds = array();

        try {
            $activeSubscriptions = Recurly_SubscriptionList::getForAccount( $this->getCustomerRecurlyAccountCode(), [ 'state' => 'active' ] );
            $futureSubscriptions = Recurly_SubscriptionList::getForAccount( $this->getCustomerRecurlyAccountCode(), [ 'state' => 'future' ] );

            foreach( $activeSubscriptions as $subscription ) {
                if( $subscription->invoice ) {
                    array_push( $invoiceIds, $subscription->invoice->get()->invoice_number );
                }
            }

            foreach( $futureSubscriptions as $subscription ) {
                if( $subscription->invoice ) {
                    array_push( $invoiceIds, $subscription->invoice->get()->invoice_number );
                }
            }

            $invoiceIds = array_unique( $invoiceIds );

            return $invoiceIds;        
        } catch (Recurly_NotFoundError $e) {
            return array(
                'success' => false,
                'error_message' => $e->getMessage()
            );
        }
    }

    public function getInvoice( $id )
    {
        Recurly_Client::$apiKey = $this->_recurlyHelper->getRecurlyPrivateApiKey();
        Recurly_Client::$subdomain = $this->_recurlyHelper->getRecurlySubdomain();

        try {
            $invoice = Recurly_Invoice::get( $id );

           return $invoice;
        } catch (Recurly_NotFoundError $e) {
            print "Account not found: $e";
        }
    }

    /**
     * Convert cents to dollars
     *
     */
    public function convertAmountToDollars($amount)
    {
        return number_format(($amount/100), 2, '.', ' ');
    }

    public function getBillingInformation()
    {
        Recurly_Client::$apiKey = $this->_recurlyHelper->getRecurlyPrivateApiKey();
        Recurly_Client::$subdomain = $this->_recurlyHelper->getRecurlySubdomain();

        try {
            $billing_info = Recurly_BillingInfo::get($this->getCustomerRecurlyAccountCode());
            
            return $billing_info;
        } catch (Recurly_NotFoundError $e) {
            // Could not find account or account
            // doesn't have billing info
            print "Not found: $e";
        }
    }

}
