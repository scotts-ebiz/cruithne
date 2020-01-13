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
use SMG\SubscriptionApi\Model\ResourceModel\Subscription\CollectionFactory as SubscriptionCollectionFactory;

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
        $currentSubscription = $subscriptionFactory
            ->addFilter('subscription_status', 'active')
            ->addFilter('customer_id', $this->getCustomerId())
            ->getFirstItem();

        Recurly_Client::$apiKey = $this->_recurlyHelper->getRecurlyPrivateApiKey();
        Recurly_Client::$subdomain = $this->_recurlyHelper->getRecurlySubdomain();

        $isAnnualSubscription = false;
        $subscriptions = []; // Used for merging the active and future subscriptions
        $invoices = [];

        try {
            if (! $currentSubscription || ! $currentSubscription->getId()) {
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

            // Sort subscriptions
            $episodics = [];
            $master = null;
            $current = null;
            $next = null;
            $addon = null;
            foreach ($subscriptions as $subscription) {
                if ($subscription->plan->plan_code != 'annual' && $subscription->plan->plan_code != 'seasonal' && $subscription->plan->plan_code != 'add-ons') {
                    $episodics[(int)$subscription->activated_at->format('YmdHis')] = $subscription;
                } elseif ($subscription->plan->plan_code == 'annual') {
                    $master = $subscription;
                    $current = $subscription;
                    $subscription->activated_at = $subscription->activated_at->add(new \DateInterval('P1Y'));
                    $next = $subscription;
                } elseif ($subscription->plan->plan_code == 'seasonal') {
                    $master = $subscription;
                } elseif ($subscription->plan->plan_code == 'add-ons') {
                    $addon = $subscription;
                }
            }

            if ($master->plan->plan_code == 'seasonal') {
                ksort($episodics);
                foreach ($episodics as $subscription) {
                    if ($subscription->state == 'active') {
                        $current = $subscription;
                    } else {
                        $next = $subscription;
                        break;
                    }
                }
            }

            // Get invoices
            $invoices[] = $master->invoice->get()->invoice_number;
            foreach ($episodics as $subscription) {
                if ($subscription->invoice && $invoice = $subscription->invoice->get()) {
                    $invoices[] = $invoice->invoice_number;
                }
            }
            $invoices = $this->getInvoices($invoices);

            $activeTotal = is_null($current) || $current->unit_amount_in_cents == 0 ? $this->convertAmountToDollars($next->unit_amount_in_cents) : $this->convertAmountToDollars($current->unit_amount_in_cents - $current->invoice->get()->discount_in_cents );
            $addonTotal = is_null($addon) ? 0 : $this->convertAmountToDollars($addon->unit_amount_in_cents) * $addon->quantity;

            return [
                'success'               => true,
                'is_annual'             => $master->plan->plan_code == 'annual',
                'subscription_type'     => ($isAnnualSubscription) ? 'Annual' : 'Seasonal',
                'billing_information'   => [
                    'last_four'             => $this->getBillingInformation()->last_four
                ],
                'master_subscription' => [
                    'subscription_type'     => $master->plan->plan_code,
                    'invoice_number'        => $master->invoice->get()->invoice_number,
                    'starts_at'             => $master->current_period_started_at->format('M d, Y'),
                    'ends_at'               => $master->current_period_ends_at->format('M d, Y'),
                    'total_amount'          => $this->convertAmountToDollars($master->total_in_cents)
                ],
                'active_subscription'   => [
                    'invoice_number'        => is_null($current) ? null : $current->invoice->get()->invoice_number,
                    'total_amount'          => $activeTotal,
                    'is_invoiced'           => ! is_null($current) && $current->unit_amount_in_cents > 0
                ],
                'addon_subscription'    => [
                    'quantity'              => is_null($addon) ? 0 : $addon->quantity,
                    'total_amount'          => $addonTotal
                ],
                'total_row' => [
                    'total_text'            => is_null($current) || $current->invoice->get()->total_in_cents == 0 ? 'Total' : 'Current Total',
                    'total_amount'          => $addonTotal + $activeTotal
                ],
                'invoices'              => $invoices,
                'next_billing_date'     => $next->activated_at->format('F d, Y')
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
            if ($this->convertAmountToDollars($invoice->total_in_cents) > 0) {
                $invoicesArray[$index]['invoice_number'] = $invoiceId;
                $invoicesArray[$index]['created_at'] = $invoice->created_at->format('M d, Y');
                $invoicesArray[$index]['due_on'] = $invoice->created_at->format('M d, Y');
                $invoicesArray[$index]['paid'] = ($invoice->state == 'paid') ? 'YES' : 'NO';
                $invoicesArray[$index]['total'] = $this->convertAmountToDollars($invoice->total_in_cents);
            }
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
