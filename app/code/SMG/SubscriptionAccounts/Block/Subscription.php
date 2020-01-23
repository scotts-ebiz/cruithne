<?php

namespace SMG\SubscriptionAccounts\Block;

use Magento\Customer\Model\Customer;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Recurly_BillingInfo;
use Recurly_Client;
use Recurly_Invoice;
use Recurly_SubscriptionList;
use SMG\SubscriptionApi\Helper\RecurlyHelper;
use \SMG\SubscriptionApi\Model\ResourceModel\Subscription\CollectionFactory as SubscriptionCollectionFactory;

/**
 * Class Subscription
 * @package SMG\SubscriptionAccounts\Block
 */
class Subscription extends Template
{
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
     * @var SubscriptionCollectionFactory
     */
    protected $_subscriptionCollectionFactory;

    /**
     * Subscriptions block constructor.
     * @param Context $context
     * @param CustomerSession $customerSession
     * @param Customer $customer
     * @param RecurlyHelper $recurlyHelper
     * @param array $data
     */
    public function __construct(
        Context $context,
        CustomerSession $customerSession,
        Customer $customer,
        RecurlyHelper $recurlyHelper,
        SubscriptionCollectionFactory $subscriptionCollectionFactory,
        array $data = []
    ) {
        $this->_customerSession = $customerSession;
        $this->_customer = $customer;
        $this->_recurlyHelper = $recurlyHelper;
        $this->_subscriptionCollectionFactory = $subscriptionCollectionFactory;
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
     * Return customer's Gigya UID / Recurly Account Code
     *
     * @return string|bool
     */
    private function getGigyaUid()
    {
        $customer = $this->_customer->load($this->getCustomerId());

        if ($customer->getGigyaUid()) {
            return $customer->getGigyaUid();
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
        $subscriptionFactory = $this->_subscriptionCollectionFactory->create();
        $hasActiveSubscription = $subscriptionFactory
            ->addFilter('subscription_status', 'active')
            ->addFilter('customer_id', $this->getCustomerId())
            ->count();

        Recurly_Client::$apiKey = $this->_recurlyHelper->getRecurlyPrivateApiKey();
        Recurly_Client::$subdomain = $this->_recurlyHelper->getRecurlySubdomain();

        $isAnnualSubscription = false;
        $activeSubscription = []; // Used for storing the active subscription, can be of any type
        $mainSubscription = []; // Used for storing the main subscription, annual or seasonal
        $subscriptions = []; // Used for merging the active and future subscriptions
        $invoices = [];

        try {
            if (! $hasActiveSubscription) {
                throw new \Exception('No active subscriptions found.');
            }

            // Get active, future and expired subscriptions
            $activeSubscriptions = Recurly_SubscriptionList::getForAccount($this->getGigyaUid(), [ 'state' => 'active' ]);
            $futureSubscriptions = Recurly_SubscriptionList::getForAccount($this->getGigyaUid(), [ 'state' => 'future' ]);

            // Merge active and future subscriptions
            foreach ($activeSubscriptions as $subscription) {
                array_push($subscriptions, $subscription);
            }
            foreach ($futureSubscriptions as $subscription) {
                array_push($subscriptions, $subscription);
            }

            foreach ($subscriptions as $subscription) {
                if ($subscription->plan->plan_code == 'annual' || $subscription->plan->plan_code == 'seasonal') {
                    // Get Subscription Type
                    $isAnnualSubscription = ($subscription->plan->plan_code == 'annual') ? true : false;

                    // Get main subscription
                    $mainSubscription['invoice_number'] = $subscription->invoice->get()->invoice_number;
                    $mainSubscription['starts_at'] = $subscription->current_period_started_at->format('M d, Y');
                    $mainSubscription['ends_at'] = $subscription->current_period_ends_at->format('M d, Y');
                    $mainSubscription['next_billing_date'] = $subscription->current_period_ends_at->format('F d, Y');
                    $mainSubscription['cc_last_four'] = $this->getBillingInformation()->last_four;

                    // Get items from the main invoice
                    $mainInvoice = $this->getInvoice($mainSubscription['invoice_number']);
                    $notAddonProduct = [ 'annual', 'add-ons', 'early-spring', 'late-spring', 'early-summer', 'early-fall', 'seasonal' ];
                    $totalAddonAmount = 0;
                    $totalMainAmount = 0;
                    $numberOfAddonProducts = 0;

                    foreach ($mainInvoice->line_items as $item) {
                        if (! in_array($item->product_code, $notAddonProduct)) {
                            $totalAddonAmount += $item->total_in_cents;
                            $numberOfAddonProducts++;
                        }
                        if ($item->product_code == 'annual' || $item->product_code == 'seasonal') {
                            $totalMainAmount = $item->total_in_cents;
                        }
                    }

                    $mainSubscription['addon_count'] = $numberOfAddonProducts;
                    $mainSubscription['addon_total_amount'] = $this->convertAmountToDollars($totalAddonAmount);
                    $mainSubscription['main_total_amount'] = $this->convertAmountToDollars($totalMainAmount);
                    $mainSubscription['total_amount'] = $this->convertAmountToDollars($mainInvoice->total_in_cents);
                }

                // Get active subscription, and it's not addons
                if ($subscription->state == 'active' && $subscription->plan->plan_code != 'add-ons') {
                    $activeSubscription['invoice_number'] = $subscription->invoice->get()->invoice_number;
                }

                // Get invoice numbers if there is an invoice generated for the subscription
                if ($subscription->invoice) {
                    array_push($invoices, $subscription->invoice->get()->invoice_number);
                }
            }

            $invoices = $this->getInvoices(array_unique($invoices));

            return [
                'success'               => true,
                'is_annual'             => $isAnnualSubscription,
                'subscription_type'     => ($isAnnualSubscription) ? 'Annual' : 'Seasonal',
                'main_subscription'     => $mainSubscription,
                'active_subscription'   => $activeSubscription,
                'invoices'              => $invoices,
            ];
        } catch (\Exception $e) {
            $this->_logger->error($e->getMessage());

            return [
                'success' => false,
                'error_message' => $e->getMessage(),
                'api' => Recurly_Client::$apiKey,
                'subdomain' => Recurly_Client::$subdomain,
            ];
        }
    }

    /**
     * Return all invoices
     *
     * @param $invoices
     * @return array
     */
    private function getInvoices($invoices)
    {
        $invoicesArray = [];

        foreach ($invoices as $index => $invoiceId) {
            $invoice = $this->getInvoice($invoiceId);
            $invoicesArray[$index]['invoice_number'] = $invoiceId;
            $invoicesArray[$index]['created_at'] = $invoice->created_at->format('M d, Y');
            $invoicesArray[$index]['due_on'] = $invoice->created_at->format('M d, Y');
            $invoicesArray[$index]['paid'] = ($invoice->state == 'paid') ? 'YES' : 'NO';
            $invoicesArray[$index]['total'] = $this->convertAmountToDollars($invoice->total_in_cents);
        }

        return $invoicesArray;
    }

    /**
     * Return invoice based on Invoice ID
     *
     * @param int $id
     * @return array|object
     */
    public function getInvoice($id)
    {
        Recurly_Client::$apiKey = $this->_recurlyHelper->getRecurlyPrivateApiKey();
        Recurly_Client::$subdomain = $this->_recurlyHelper->getRecurlySubdomain();

        try {
            return Recurly_Invoice::get($id);
        } catch (\Exception $e) {
            $this->_logger->error('Account not found: ' . $e->getMessage());
            return [];
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
     * @return array|object
     */
    public function getBillingInformation()
    {
        Recurly_Client::$apiKey = $this->_recurlyHelper->getRecurlyPrivateApiKey();
        Recurly_Client::$subdomain = $this->_recurlyHelper->getRecurlySubdomain();

        try {
            return Recurly_BillingInfo::get($this->getGigyaUid());
        } catch (\Exception $e) {
            $this->_logger->error($e->getMessage());
            return [];
        }
    }
}
