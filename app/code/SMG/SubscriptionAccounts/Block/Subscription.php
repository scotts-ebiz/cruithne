<?php
namespace SMG\SubscriptionAccounts\Block;

use Recurly_Client;
use Recurly_SubscriptionList;
use Recurly_NotFoundError;
use Recurly_Invoice;
use Recurly_InvoiceList;
use Recurly_BillingInfo;

class Subscription extends \Magento\Framework\View\Element\Template
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

    /**
     * Return customer subscriptions
     * 
     * @return array
     */
    public function getSubscriptions()
    {
        Recurly_Client::$apiKey = $this->_recurlyHelper->getRecurlyPrivateApiKey();
        Recurly_Client::$subdomain = $this->_recurlyHelper->getRecurlySubdomain();

        $isAnnualSubscription = false;
        $activeSubscription = array(); // Used for storing the active subscription, can be of any type
        $mainSubscription = array(); // Used for stroing the main subscription, annual or seasonal
        $subscriptions = array(); // Used for merging the active and future subscriptions
        $invoices = array();

        try {
            // Get active, future and expired subscriptions
            $activeSubscriptions = Recurly_SubscriptionList::getForAccount( $this->getCustomerRecurlyAccountCode(), [ 'state' => 'active' ] );
            $futureSubscriptions = Recurly_SubscriptionList::getForAccount( $this->getCustomerRecurlyAccountCode(), [ 'state' => 'future' ] );

            // Merge active and future subscriptions
            foreach( $activeSubscriptions as $subscription ) {
                array_push( $subscriptions, $subscription );
            }
            foreach( $futureSubscriptions as $subscription ) {
                array_push( $subscriptions, $subscription );
            }

            foreach( $subscriptions as $subscription ) {
                if( $subscription->plan->plan_code == 'annual' || $subscription->plan->plan_code == 'seasonal' ) {
                    // Get Subscription Type
                    $isAnnualSubscription = ( $subscription->plan->plan_code == 'annual' ) ? true : false;

                    // Get main subscription
                    $mainSubscription = $subscription;
                }

                // Get active subscription, and it's not addons
                if( $subscription->state == 'active' && $subscription->plan->plan_code != 'add-ons' ) {
                    $activeSubscription = $subscription; 
                }

                // Get invoice numbers if there is an invoice generated for the subscription
                if( $subscription->invoice ) {
                    array_push( $invoices, $subscription->invoice->get()->invoice_number );
                }
            }

            return array(
                'is_annual'             => $isAnnualSubscription,
                'main_subscription'     => $mainSubscription,
                'active_subscription'   => $activeSubscription,
                'invoices'              => array_unique( $invoices ),
            );
        } catch (Recurly_NotFoundError $e) {
            return array(
                'success' => false,
                'error_message' => $e->getMessage()
            );
        }
    }

    /**
     * Return invoice based on Invoice ID
     * 
     * @param int $id;
     * @return object $invoice
     */
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
     * @param int $amount
     * @return float
     */
    public function convertAmountToDollars($amount)
    {
        return number_format(($amount/100), 2, '.', ' ');
    }

    /**
     * Return customer's billing information
     * 
     * @return object $billing_info
     */
    public function getBillingInformation()
    {
        Recurly_Client::$apiKey = $this->_recurlyHelper->getRecurlyPrivateApiKey();
        Recurly_Client::$subdomain = $this->_recurlyHelper->getRecurlySubdomain();

        try {
            $billing_info = Recurly_BillingInfo::get($this->getCustomerRecurlyAccountCode());
            
            return $billing_info;
        } catch (Recurly_NotFoundError $e) {
            print "Not found: $e";
        }
    }

}
