<?php

namespace SMG\SubscriptionAccounts\Block;

use DateInterval;
use DateTime;
use DateTimeImmutable;
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Recurly_BillingInfo;
use Recurly_Client;
use Recurly_Invoice;
use Recurly_InvoiceList;
use Recurly_Subscription;
use Recurly_SubscriptionList;
use SMG\SubscriptionApi\Helper\RecurlyHelper;
use SMG\SubscriptionApi\Model\ResourceModel\Subscription\CollectionFactory as SubscriptionCollectionFactory;
use SMG\SubscriptionApi\Model\SubscriptionAddonOrder;
use SMG\SubscriptionApi\Model\SubscriptionOrder;

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
     * @param SubscriptionCollectionFactory $subscriptionCollectionFactory
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

        Recurly_Client::$apiKey = $this->_recurlyHelper->getRecurlyPrivateApiKey();
        Recurly_Client::$subdomain = $this->_recurlyHelper->getRecurlySubdomain();
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
     * @throws \Exception
     */
    public function getSubscriptions()
    {
        $subscriptionCollection = $this->_subscriptionCollectionFactory->create();

        /** @var \SMG\SubscriptionApi\Model\Subscription $currentSubscription */
        $currentSubscription = $subscriptionCollection
            ->addFilter('subscription_status', 'active')
            ->addFilter('customer_id', $this->getCustomerId())
            ->getFirstItem();

        // We don't have a subscription so return empty object.
        if (! $currentSubscription || ! $currentSubscription->getData('entity_id')) {
            return [
                'subscription' => [],
            ];
        }

        $subscriptionOrders = $currentSubscription
            ->getSubscriptionOrders()
            ->getItems();

        $subscriptionAddonOrders = $currentSubscription
            ->getSubscriptionAddonOrders()
            ->addFieldToFilter('subscription_id', ['notnull' => true])
            ->getItems();

        // Get the subscriptions from Recurly.
        $recurlySubscriptions = Recurly_SubscriptionList::getForAccount(
            $currentSubscription->getData('gigya_id'),
            ['state' => 'active']
        );

        foreach ($recurlySubscriptions as $recurlySubscription) {
            /** @var Recurly_Subscription $recurlySubscription */
            if ($recurlySubscription->uuid == $currentSubscription->getData('subscription_id')) {
                $currentSubscription->setData('recurly', $recurlySubscription->getValues());
                break;
            }
        }

        $subscriptionOrders = array_values(array_map(function ($subscriptionOrder) use ($recurlySubscriptions) {
            /** @var SubscriptionOrder $subscriptionOrder */
            foreach ($recurlySubscriptions as $recurlySubscription) {
                /** @var Recurly_Subscription $recurlySubscription */
                if ($recurlySubscription->uuid == $subscriptionOrder->getData('subscription_id')) {
                    $subscriptionOrder->setData('recurly', $recurlySubscription->getValues());
                    break;
                }
            }

            $order = $subscriptionOrder->getOrder();
            $order = $order ? $order->toArray() : null;
            $subscriptionOrder->setData('order', $order);

            return $subscriptionOrder->toArray();
        }, $subscriptionOrders));

        $subscriptionAddonOrders = array_values(array_map(function ($subscriptionAddonOrder) use ($recurlySubscriptions) {
            /** @var SubscriptionAddonOrder $subscriptionAddonOrder */
            foreach ($recurlySubscriptions as $recurlySubscription) {
                /** @var Recurly_Subscription $recurlySubscription */
                if ($recurlySubscription->uuid == $subscriptionAddonOrder->getData('subscription_id')) {
                    $subscriptionAddonOrder->setData('recurly', $recurlySubscription->getValues());
                    break;
                }
            }

            $order = $subscriptionAddonOrder->getOrder();
            $order = $order ? $order->toArray() : null;
            $subscriptionAddonOrder->setData('order', $order);

            return $subscriptionAddonOrder->toArray();
        }, $subscriptionAddonOrders));

        $addonOrder = count($subscriptionAddonOrders) ? $subscriptionAddonOrders[0] : null;

        // Get the next billing date.
        $startDate = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $subscriptionOrders[0]['ship_start_date'])
            ->add(new DateInterval('P1Y'));
        $renewalDate = $startDate->add(new DateInterval('P1Y'));
        $nextBilling = null;
        $nextOrder = null;

        // Get the next billing date for seasonal subscription.
        $initialOrder = true;
        if ($currentSubscription->getData('subscription_type') == 'seasonal') {
            foreach ($subscriptionOrders as $subscriptionOrder) {
                $shipStartDate = DateTime::createFromFormat(
                    'Y-m-d H:i:s',
                    $subscriptionOrder['ship_start_date']
                );

                // Find the first order that has not yet been invoiced by Recurly.
                if (empty($subscriptionOrder['recurly'])) {
                    $nextOrder = $subscriptionOrder;
                    $nextBilling = $shipStartDate;
                    break;
                }

                $initialOrder = false;
            }
        }

        $invoiceList = Recurly_InvoiceList::getForAccount(
            $currentSubscription->getData('gigya_id')
        );

        $invoices = [];

        // Loop through the invoices to filter out zero dollar invoices.
        foreach ($invoiceList as $invoice) {
            /** @var Recurly_Invoice $invoice */
            if ($invoice->total_in_cents > 0) {
                $invoice->due_on = $invoice->due_on->format('M d, Y');
            }
            $invoice->created_at = $invoice->created_at->format('M d, Y');
            $invoices[] = $invoice->getValues();
        }

        return [
            'addonOrder' => $addonOrder,
            'initialOrder' => $initialOrder,
            'invoices' => $invoices,
            'lastFour' => $this->getBillingInformation()->last_four,
            'nextBillingDate' => $nextBilling ? $nextBilling->format('M d, Y') : null,
            'nextOrder' => $nextOrder,
            'orders' => $subscriptionOrders,
            'renewalDate' => $renewalDate->format('M d, Y'),
            'startDate' => $startDate->format('M d, Y'),
            'subscription' => $currentSubscription->toArray(),
        ];
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
        try {
            return Recurly_BillingInfo::get($this->getGigyaUid());
        } catch (\Exception $e) {
            $this->_logger->error($e->getMessage());
            return [];
        }
    }
}
