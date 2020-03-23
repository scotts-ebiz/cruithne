<?php

namespace SMG\SubscriptionApi\Model;

use DateTime;
use Exception;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\ResourceModel\CustomerFactory;
use Magento\Customer\Model\Session;
use Magento\Customer\Model\Url;
use Magento\Directory\Model\ResourceModel\Region\CollectionFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Session\SessionManagerInterface;
use Psr\Log\LoggerInterface;
use Recurly_Account;
use Recurly_BillingInfo;
use Recurly_Client;
use Recurly_Coupon;
use Recurly_CustomField;
use Recurly_Error;
use Recurly_Invoice;
use Recurly_InvoiceCollection;
use Recurly_NotFoundError;
use Recurly_Purchase;
use Recurly_ShippingAddress;
use Recurly_Subscription;
use Recurly_SubscriptionList;
use SMG\SubscriptionApi\Helper\RecurlyHelper;
use SMG\SubscriptionApi\Helper\SubscriptionHelper;
use SMG\SubscriptionApi\Helper\SubscriptionOrderHelper;
use SMG\SubscriptionApi\Helper\TestHelper;
use SMG\SubscriptionApi\Model\ResourceModel\Subscription\CollectionFactory as SubscriptionCollectionFactory;
use SMG\SubscriptionApi\Model\ResourceModel\SubscriptionAddonOrderItem\Collection;

/**
 * Class RecurlySubscription
 * @package SMG\SubscriptionApi\Model
 */
class RecurlySubscription
{
    /**
     * @var RecurlyHelper
     */
    protected $_recurlyHelper;

    /**
     * @var SubscriptionHelper
     */
    protected $_subscriptionHelper;

    /**
     * @var Session
     */
    protected $_customerSession;

    /**
     * @var Customer
     */
    protected $_customer;

    /**
     * @var CustomerFactory
     */
    protected $_customerFactory;

    /**
     * @var string
     */
    protected $_couponCode;

    /**
     * @var string
     */
    protected $_currency;

    /**
     * @var ProductRepositoryInterface
     */
    protected $_productRepository;

    /**
     * @var CheckoutSession
     */
    protected $_checkoutSession;

    /**
     * @var CollectionFactory
     */
    protected $_collectionFactory;

    /**
     * @var Url
     */
    protected $_customerUrl;

    /**
     * @var LoggerInterface
     */
    protected $_logger;

    /**
     * @var SubscriptionOrderHelper
     */
    protected $_subscriptionOrderHelper;

    /**
     * @var SubscriptionCollectionFactory
     */
    protected $_subscriptionCollectionFactory;

    /**
     * @var TestHelper
     */
    protected $_testHelper;

    /**
     * @var SessionManagerInterface
     */
    protected $_coreSession;

    /**
     * RecurlySubscription constructor.
     * @param RecurlyHelper $recurlyHelper
     * @param SubscriptionHelper $subscriptionHelper
     * @param Session $customerSession
     * @param Customer $customer
     * @param CustomerFactory $customerFactory
     * @param ProductRepositoryInterface $productRepository
     * @param CheckoutSession $checkoutSession
     * @param CollectionFactory $collectionFactory
     * @param Url $customerUrl
     * @param LoggerInterface $logger
     * @param SubscriptionOrderHelper $subscriptionOrderHelper
     * @param SubscriptionCollectionFactory $subscriptionCollectionFactory
     * @param TestHelper $testHelper
     * @param SessionManagerInterface $coreSession
     */
    public function __construct(
        RecurlyHelper $recurlyHelper,
        SubscriptionHelper $subscriptionHelper,
        Session $customerSession,
        Customer $customer,
        CustomerFactory $customerFactory,
        ProductRepositoryInterface $productRepository,
        CheckoutSession $checkoutSession,
        CollectionFactory $collectionFactory,
        Url $customerUrl,
        LoggerInterface $logger,
        SubscriptionOrderHelper $subscriptionOrderHelper,
        SubscriptionCollectionFactory $subscriptionCollectionFactory,
        TestHelper $testHelper,
        SessionManagerInterface $coreSession
    ) {
        $this->_recurlyHelper = $recurlyHelper;
        $this->_subscriptionHelper = $subscriptionHelper;
        $this->_customerSession = $customerSession;
        $this->_customer = $customer;
        $this->_customerFactory = $customerFactory;
        $this->_productRepository = $productRepository;
        $this->_checkoutSession = $checkoutSession;
        $this->_collectionFactory = $collectionFactory;
        $this->_customerUrl = $customerUrl;
        $this->_couponCode = 'annual_subscription_discount';
        $this->_currency = 'USD';
        $this->_logger = $logger;
        $this->_subscriptionOrderHelper = $subscriptionOrderHelper;
        $this->_subscriptionCollectionFactory = $subscriptionCollectionFactory;
        $this->_testHelper = $testHelper;
        $this->_coreSession = $coreSession;

        // Configure Recurly Client
        Recurly_Client::$apiKey = $this->_recurlyHelper->getRecurlyPrivateApiKey();
        Recurly_Client::$subdomain = $this->_recurlyHelper->getRecurlySubdomain();
    }

    /**
     * Create new Recurly subscription for the customer. Use it's existing Recurly account if there is one,
     * otherwise create new Recurly account for the customer
     *
     * @param string $token
     * @param Subscription $subscription
     * @param Customer $customer
     * @return Recurly_Purchase
     * @throws LocalizedException
     */
    public function createRecurlyPurchase($token, $subscription, $customer)
    {
        $quizId = $subscription->getData('quiz_id');
        $subscriptionType = $subscription->getData('subscription_type');

        if (empty($token)) {
            $this->_logger->error('Cannot create Recurly subscription. Missing Recurly token.');
            throw new LocalizedException(__('The provided billing information is invalid. Please try again.'));
        }

        if (empty($subscriptionType)) {
            $this->_logger->error('Cannot create Recurly subscription. Missing subscription type.');
            throw new LocalizedException(__('There was an issue creating your subscription, please go back to the plan page and make your selection.'));
        }

        if (empty($quizId)) {
            $this->_logger->error('Cannot create Recurly subscription. Missing quiz ID.');
            throw new LocalizedException(__('There was an issue creating your subscription, please go back and retake the quiz.'));
        }

        // Set addressees.
        $checkoutShipping = $this->_coreSession->getCheckoutShipping();

        try {
            $account = $this->loadRecurlyAccount($customer);
        } catch (LocalizedException $e) {
            $this->_logger->error('Could not create subscription account. - ' . $e->getMessage());

            throw new LocalizedException(__('Could not create subscription account.'));
        }

        $recurlyShippingAddress = $this->createRecurlyShippingAddress($checkoutShipping, $account->email);

        // Create Recurly Purchase
        try {
            $recurlyPurchase = new Recurly_Purchase();
            $recurlyPurchase->currency = $this->_currency;
            $recurlyPurchase->collection_method = 'automatic';
            $recurlyPurchase->shipping_address = $recurlyShippingAddress;
            $recurlyPurchase->account = $account;
        } catch (Exception $e) {
            $this->_logger->error($e->getMessage());

            throw new LocalizedException(__('There was an error creating the purchase.'));
        }

        // Create billing information with the token from Recurly.
        try {
            $recurlyPurchase->account->billing_info = $this->createBillingInfo($account->account_code, $token);
        } catch (Exception $e) {
            $this->_logger->error($e->getMessage());
            $error = 'There is a problem with the billing information.';

            throw new LocalizedException(__($error));
        }

        // Set shipping information.
        try {
            $recurlyPurchase->account->shipping_address = $recurlyShippingAddress;
        } catch (Exception $e) {
            $error = 'There is a problem with the shipping information.';
            $this->_logger->error($error . " : " . $e->getMessage());
            throw new LocalizedException(__($error));
        }

        $recurlySubscriptions = $this->createRecurlySubscriptions($subscription);

        // Add Coupon Code if annual
        try {
            if ($subscription->getData('subscription_type') == 'annual') {
                $recurlyPurchase->coupon_codes = [$this->getCouponCode()];
            }
        } catch (Exception $e) {
            $error = 'There was a problem applying the discount code.';
            $this->_logger->error($error . " : " . $e->getMessage());
            throw new LocalizedException(__($error));
        }

        $recurlyPurchase->subscriptions = $recurlySubscriptions;

        return $recurlyPurchase;
    }

    /**
     * @param Recurly_Purchase $recurlyPurchase
     * @param Subscription $subscription
     * @throws LocalizedException
     */
    public function invoiceRecurlyPurchase(
        Recurly_Purchase $recurlyPurchase,
        Subscription $subscription
    ) {
        // Get the Recurly invoices and set the values on the subscription.
        try {
            /* @var Recurly_InvoiceCollection $invoiceCollection */
            $invoiceCollection = Recurly_Purchase::invoice($recurlyPurchase);
            $invoices = $invoiceCollection->getValues();

            // We have an annual subscription so add the invoice amounts to the
            // subscription.
            if ($subscription->getData('subscription_type') == 'annual' && ! empty($invoices)) {
                /* @var Recurly_Invoice $invoice */
                $invoice = reset($invoices);
                $subscription->addData([
                    'recurly_invoice' => $invoice->invoice_number,
                    'paid' => $this->convertAmountToDollars($invoice->total_in_cents),
                    'price' => $this->convertAmountToDollars($invoice->subtotal_before_discount_in_cents),
                    'discount' => $this->convertAmountToDollars(-$invoice->discount_in_cents),
                    'tax' => $this->convertAmountToDollars($invoice->tax_in_cents),
                ])->save();
            }
        } catch (Exception $e) {
            $error = 'There was an error invoicing the subscription.';
            $this->_logger->error($error . " : " . $e->getMessage());

            throw new LocalizedException(__($error));
        }

        try {
            $this->updateSubscriptionIDs($subscription);
            $subscription->setData('subscription_status', 'pending_order')->save();
        } catch (Exception $e) {
            $error = 'There was an issue saving the subscription information.';
            $this->_logger->error($error . " : " . $e->getMessage());

            throw new LocalizedException(__($error));
        }
    }

    /**
     * Check if the customer already has a Recurly subscription
     *
     * @api
     */
    public function checkRecurlySubscription()
    {
        $subscriptionFactory = $this->_subscriptionCollectionFactory->create();
        $hasActiveSubscription = $subscriptionFactory
            ->addFieldToFilter('subscription_status', 'active')
            ->addFieldToFilter('customer_id', $this->_customerSession->getCustomerId())
            ->count();
        $customer = $this->_customerSession->getCustomer();

        if (! $hasActiveSubscription) {
            $response = [
                'success'           => true,
                'has_subscription'  => false,
            ];

            return json_encode($response);
        }

        // Get Customer's Recurly Account or create new one using current customer's data
        $account = ($this->getRecurlyAccount()) ? $this->getRecurlyAccount() : $this->createRecurlyAccount($customer);

        // Check if the customer has an active subscription
        $activeSubscriptions = $this->hasRecurlySubscription($account->account_code);
        if ($activeSubscriptions['has_subscriptions'] === true) {
            $response = [
                'success'           => true,
                'has_subscription'  => true,
                'refund_amount'     => $activeSubscriptions['refund_amount'],
                'redirect_url'      => $this->_customerUrl->getAccountUrl()
            ];
        } else {
            $response = [
                'success'           => true,
                'has_subscription'  => false,
            ];
        }

        return json_encode($response);
    }

    /**
     * Create billing information with the token provided from Recurly.js
     *
     * @param string $account_code
     * @param string $token
     *
     * @throws Exception
     *
     * @return object|bool
     */
    protected function createBillingInfo($account_code, $token)
    {
        $billing_info = new Recurly_BillingInfo();
        $billing_info->account_code = $account_code;
        $billing_info->token_id = $token;
        $billing_info->create();

        return $billing_info;
    }

    /**
     * Check if customer already has a subscription
     *
     * @param $account_code
     * @return array
     */
    public function hasRecurlySubscription($account_code)
    {
        try {
            $subscriptions = Recurly_SubscriptionList::getForAccount($account_code, [ 'state' => 'active' ]);
            $subscriptions_amount = 0;

            foreach ($subscriptions as $subscription) {
                $subscriptions_amount += $subscription->unit_amount_in_cents;
            }

            if (count($subscriptions) > 0) {
                return [
                    'has_subscriptions' => true,
                    'refund_amount'     => $this->convertAmountToDollars($subscriptions_amount)
                ];
            }

            return [
                'has_subscriptions' => false,
                'refund_amount'     => 0
            ];
        } catch (Exception $e) {
            return [
                'has_subscriptions' => false,
                'refund_amount'     => 0
            ];
        }
    }

    /**
     * Terminate any subscriptions that were created during a failed checkout.
     *
     * @param string $gigyaID
     */
    public function terminateFailedRecurlySubscriptions($gigyaID)
    {
        try {
            $subscriptions = Recurly_SubscriptionList::getForAccount($gigyaID);

            foreach ($subscriptions as $subscription) {
                /**
                 * @var Recurly_Subscription $subscription
                 */

                // Continue if the subscription is not active or future.
                if (!in_array($subscription->state, ['active', 'future'])) {
                    continue;
                }

                try {
                    if ($subscription->unit_amount_in_cents > 0 && $subscription->state == 'active') {
                        $subscription->terminateAndRefund();
                    } else {
                        $subscription->terminateWithoutRefund();
                    }
                } catch (Recurly_Error $e) {
                    $this->_logger->error('Failed subscription "' . $subscription->uuid . '" could not be terminated and may need adjusted manually.');
                }
            }
        } catch (Recurly_NotFoundError $e) {
            $this->_logger->error('Could not find a Recurly account for user: ' . $gigyaID);
        }
    }

    /**
     * @param array $shippingAddress
     * @param string $email
     * @return Recurly_ShippingAddress
     */
    protected function createRecurlyShippingAddress(array $shippingAddress, string $email): Recurly_ShippingAddress
    {
        $recurlyShippingData = [
            'first_name' => $shippingAddress['firstname'],
            'last_name' => $shippingAddress['lastname'],
            'email' => $email,
            'city' => $shippingAddress['city'],
            'state' => $shippingAddress['region'],
            'zip' => substr($shippingAddress['postcode'], 0, 5),
            'phone' => $shippingAddress['telephone'],
            'address1' => $shippingAddress['street'][0],
            'address2' => isset($shippingAddress['street'][1]) ? $shippingAddress['street'][1] : '',
            'country' => $shippingAddress['country_id'],
        ];

        $recurlyShippingAddress = new Recurly_ShippingAddress();
        $recurlyShippingAddress->setValues($recurlyShippingData);

        return $recurlyShippingAddress;
    }

    /**
     * @param Customer $customer
     * @return Recurly_Account
     * @throws LocalizedException
     */
    protected function loadRecurlyAccount(Customer $customer): Recurly_Account
    {
        // Get Customer's Recurly account
        $this->_logger->debug('Gigya ID: ' . $customer->getData('gigya_uid'));

        try {
            /** @var Recurly_Account $account */
            $account = $this->getRecurlyAccount($customer->getData('gigya_uid'))
                ?: $this->createRecurlyAccount($customer);

            if (! $account) {
                throw new LocalizedException(__('Could not find or create Recurly account.'));
            }

            return $account;
        } catch (Exception $e) {
            $error = 'There was an issue creating your subscription. Your account could not be found or created.';
            $this->_logger->error($error . " : " . $e->getMessage());
            throw new LocalizedException(__($error));
        }
    }

    /**
     * @param Subscription $subscription
     * @return array|Recurly_Subscription[]
     * @throws LocalizedException
     */
    protected function createRecurlySubscriptions(Subscription $subscription)
    {
        /**
         * @var Recurly_Subscription[] $recurlySubscriptions
         */
        $recurlySubscriptions = [];

        // Create the master subscription.
        try {
            $recurlySubscriptions[] = $this->createMasterSubscription($subscription);
        } catch (Exception $e) {
            $error = 'There is a problem creating the master subscription. Please try again.';
            $this->_logger->error($error . " : " . $e->getMessage());

            throw new LocalizedException(__($error));
        }

        // Create Seasonal Subscriptions
        try {
            $recurlySubscriptions = array_merge($recurlySubscriptions, $this->createSeasonalSubscriptions($subscription));
        } catch (LocalizedException $e) {
            $error = 'There is a problem creating the subscription. Please try again.';
            $this->_logger->error($error . " : " . $e->getMessage());

            throw new LocalizedException(__($error));
        }

        // Create Add-on Charges
        try {
            $recurlySubscriptions = array_merge($recurlySubscriptions, $this->createAddonSubscription($subscription));
        } catch (LocalizedException $e) {
            $error = 'There is a problem creating the add-ons.';
            $this->_logger->error($error . " : " . $e->getMessage());

            throw new LocalizedException(__($error));
        }

        return $recurlySubscriptions;
    }

    /**
     * Convert order grand total from dollars to cents
     *
     * @param int|float $amount
     * @return int
     */
    protected function convertAmountToCents($amount)
    {
        $cents = number_format((float) $amount * 100, 2, '.', '');

        if (explode('.', $cents)[1] > 0) {
            $cents = (int) $cents + 1;
        }

        return (int) $cents;
    }

    /**
     * Convert cents to dollars
     *
     */
    protected function convertAmountToDollars($amount)
    {
        return number_format(($amount/100), 2, '.', ' ');
    }

    /**
     * Check if the current customer has a Recurly account.
     * If it does, return it's account, otherwise return false.
     *
     * @param string $gigyaId
     * @return Recurly_Account|object|bool
     */
    public function getRecurlyAccount(string $gigyaId = null)
    {
        if (is_null($gigyaId)) {
            $gigyaId = $this->_customerSession->getCustomer()->getGigyaUid();
        }

        if (! empty($gigyaId)) {
            try {
                return Recurly_Account::get($gigyaId);
            } catch (Recurly_NotFoundError $e) {
                if ($e->getCode() == 0) {
                    return false;
                }

                return false;
            }
        }

        return false;
    }

    /**
     * Create Recurly account using the data from the checkout page
     *
     * @param Customer $customer
     * @return Recurly_Account $account
     */
    protected function createRecurlyAccount(Customer $customer)
    {
        $this->_logger->debug('Creating Recurly account...');

        // Generate account code from customer's email
        $customerID = $customer->getData('entity_id');
        $gigyaID = $customer->getData('gigya_uid');
        $email = $customer->getData('email');

        try {
            $account = new Recurly_Account($gigyaID);

            // Save Recurly account code to the eav table
            $this->saveRecurlyAccountCode($email, $customerID);

            // Populate Recurly Account Data
            $account->email = $email;
            $account->first_name = $customer->getData('firstname');
            $account->last_name = $customer->getData('lastname');

            $account->create();

            return $account;
        } catch (Exception $e) {
            // If a Recurly account with the account_code exists, save the code as a custom attribute,
            // and get Recurly account using that account code
            if ($e->getCode() === 0) {
                $this->saveRecurlyAccountCode($email, $customerID);

                return $this->getRecurlyAccount();
            }
        }
    }

    /**
     * Get current customer and save it's Recurly account code as a custom attribute,
     * generated from customer's email
     *
     */
    protected function saveRecurlyAccountCode($customer_email, $customer_id)
    {
        if (! empty($customer_email) && ! empty($customer_id) && filter_var($customer_email, FILTER_VALIDATE_EMAIL)) {
            $recurly_account_code = md5($customer_email);

            $this->_customerSession->getCustomer()->setData('recurly_account_code', $recurly_account_code);
            $customer = $this->_customer->load($customer_id);
            $customerData = $customer->getDataModel();
            $customerData->setCustomAttribute('recurly_account_code', $recurly_account_code);
            $customer->updateData($customerData);
            $customerResource = $this->_customerFactory->create();
            $customerResource->saveAttribute($customer, 'recurly_account_code');
        }
    }

    /**
     * Check if coupon code exists on Recurly and return it,
     * or return false if it doesn't
     *
     * @return mixed
     */
    protected function checkIfCouponExists()
    {
        try {
            $coupon = Recurly_Coupon::get($this->_couponCode);
            return $coupon;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * If the coupon code exists, return the code, otherwise create
     * a new Recurly coupon code that can be used for the annual subscriptions
     *
     * @return mixed
     */
    protected function getCouponCode()
    {
        if ($this->checkIfCouponExists()) {
            return $this->_couponCode;
        }

        // Create coupon if it doesn't exist
        try {
            $coupon = new Recurly_Coupon();
            $coupon->coupon_code = $this->_couponCode;
            $coupon->name = '10% Discount Coupon Code';
            $coupon->discount_type = 'percent';
            $coupon->discount_percent = 10;
            $coupon->invoice_description = '10% Discount';
            $coupon->duration = 'forever';
            $coupon->redemption_resource = 'subscription';
            $coupon->applies_to_all_plans = false;
            $coupon->plan_codes = [ 'annual' ];
            $coupon->create();

            return $coupon->coupon_code;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Return region code by name
     *
     * @param $region
     * @return array
     */
    protected function getRegionCode($region)
    {
        $regionCode = $this->_collectionFactory->create()->addRegionNameFilter($region)->getFirstItem()->toArray();
        return $regionCode['code'];
    }

    /**
     * Create the master subscription
     * @param Subscription $subscription
     * @return Recurly_Subscription
     * @throws LocalizedException
     */
    protected function createMasterSubscription(Subscription $subscription)
    {
        try {
            $masterSubscription = new Recurly_Subscription();
            $subscriptionType = $subscription->getData('subscription_type');
            $price = $subscription->getData('price');
            $quizID = $subscription->getData('quiz_id');

            $masterSubscription->plan_code = $subscriptionType;
            $masterSubscription->auto_renew = true;
            $masterSubscription->custom_fields[] = new Recurly_CustomField('quiz_id', $quizID);

            if ($subscriptionType == 'annual') {
                // Create Annual Subscription (Master Subscription)
                $masterSubscription->total_billing_cycles = 1;
                $masterSubscription->unit_amount_in_cents = $this->convertAmountToCents($price);
            } else {
                // Create Seasonal Subscription (Master Subscription)
                $masterSubscription->unit_amount_in_cents = 0;
            }

            return $masterSubscription;
        } catch (Exception $e) {
            throw new LocalizedException(__($e->getMessage()));
        }
    }

    /**
     * Create Seasonal Subscriptions
     * @param Subscription $subscription
     * @return Recurly_Subscription[]
     * @throws LocalizedException
     */
    protected function createSeasonalSubscriptions(Subscription $subscription)
    {
        try {
            $subscriptionType = $subscription->getData('subscription_type');
            $quizID = $subscription->getData('quiz_id');
            $subscriptionOrders = $subscription->getSubscriptionOrders();
            $recurlySubscriptions = [];

            /** @var DateTime|\DateTimeImmutable|null $lastShipDate */
            $lastShipDate = null;

            /** @var SubscriptionOrder $subscriptionOrder */
            foreach ($subscriptionOrders as $subscriptionOrder) {
                $recurlySubscription = new Recurly_Subscription();
                $recurlySubscription->plan_code = $this->_recurlyHelper->getSeasonSlugByName($subscriptionOrder->getData('season_name'));
                $recurlySubscription->auto_renew = true;
                $recurlySubscription->total_billing_cycles = 1;

                if ($subscriptionType != 'annual') {
                    $recurlySubscription->unit_amount_in_cents = $this->convertAmountToCents($subscriptionOrder->getData('price'));
                }

                $recurlySubscription->custom_fields[] = new Recurly_CustomField('quiz_id', $quizID);
                $recurlySubscription->starts_at = $subscriptionOrder->getData('ship_start_date');
                $today = new \DateTimeImmutable();

                // If this subscription order can ship now, we do not need to
                // send a starts_at date to Recurly, otherwise, it will get
                // queued and charged at the top of the hour.
                if ($subscriptionOrder->isCurrentlyShippable()) {
                    $recurlySubscription->starts_at = null;
                }

                // We're in test mode, so use custom subscription dates.
                if ($this->_testHelper->inTestMode()) {
                    $this->_logger->info('Test mode subscription_order');
                    $start = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $recurlySubscription->starts_at);

                    // No last ship date so this is the first order.
                    if (! $lastShipDate && $start <= $today) {
                        // Since it is available to ship, lets just set it to now.
                        $start = $today;
                    } elseif (! $lastShipDate && $start > $today) {
                        $start = $today->add(new \DateInterval('PT' . $this->_testHelper->getTestMinutes() . 'M'));
                    }

                    // We have a last ship date, so lets add the test time to it.
                    if ($lastShipDate) {
                        $start = $lastShipDate->add(new \DateInterval('PT' . $this->_testHelper->getTestMinutes() . 'M'));
                    }

                    // Set the ship dates on the Recurly subscription and subscription order.
                    $recurlySubscription->starts_at = $start->format('Y-m-d H:i:s');
                    $subscriptionOrder->setData(
                        'ship_start_date',
                        $start->format('Y-m-d H:i:s')
                    )->save();
                    $lastShipDate = $start;
                }

                $recurlySubscriptions[] = $recurlySubscription;
            }

            return $recurlySubscriptions;
        } catch (Exception $e) {
            throw new LocalizedException(__($e->getMessage()));
        }
    }

    /**
     * Create Addon Subscription
     * @param Subscription $subscription
     * @return Recurly_Subscription[]
     * @throws LocalizedException
     */
    protected function createAddonSubscription(Subscription $subscription)
    {
        $subscriptionType = $subscription->getData('subscription_type');
        $quizID = $subscription->getData('quiz_id');
        $recurlySubscriptions = [];

        try {
            $subscriptionAddonOrders = $subscription->getSubscriptionAddonOrders();

            /** @var SubscriptionAddonOrder $subscriptionAddonOrder */
            foreach ($subscriptionAddonOrders as $subscriptionAddonOrder) {

                /** @var Collection $subscriptionAddonOrderItems */
                $subscriptionAddonOrderItems = $subscriptionAddonOrder->getOrderItems(true);

                if ($subscriptionAddonOrderItems->getSize() === 0) {
                    break;
                }

                $productSkus = [];
                $productNames = [];
                $addonPrice = 0;
                $addonQty = 0;

                /** @var SubscriptionAddonOrderItem $subscriptionAddonOrderItem */
                foreach ($subscriptionAddonOrderItems as $subscriptionAddonOrderItem) {
                    $productSkus[] = $subscriptionAddonOrderItem->getProduct()->getSku();
                    $productNames[] = $subscriptionAddonOrderItem->getProduct()->getName();
                    $addonPrice += $subscriptionAddonOrderItem->getPrice();
                    $addonQty += $subscriptionAddonOrderItem->getQty();
                }

                $recurlySubscription = new Recurly_Subscription();
                $recurlySubscription->plan_code = 'add-ons';
                $recurlySubscription->auto_renew = false;
                $recurlySubscription->total_billing_cycles = 1;
                $recurlySubscription->unit_amount_in_cents = $this->convertAmountToCents($addonPrice);
                $recurlySubscription->quantity = $addonQty;
                $recurlySubscription->custom_fields[] = new Recurly_CustomField('quiz_id', $quizID);
                $recurlySubscription->custom_fields[] = new Recurly_CustomField('is_addon', true);
                $recurlySubscription->custom_fields[] = new Recurly_CustomField('addon_skus', implode(',', $productSkus));

                if ($subscriptionType != 'annual') {
                    $recurlySubscription->starts_at = $subscriptionAddonOrder->getData('ship_start_date');

                    // We're in test mode, so set custom start dates.
                    if ($this->_testHelper->inTestMode()) {
                        $this->_logger->info('Test mode subscription_addon_order');
                        $today = new \DateTimeImmutable();
                        $start = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $recurlySubscription->starts_at);

                        // If the ship date is before today, lets just set it to
                        // today for testing.
                        if ($start < $today) {
                            // Since it is available to ship, lets just set it to now.
                            $start = $today;
                        } else {
                            // Ship date is later than today, so lets set it ahead
                            // by the test time.
                            $start = $today->add(new \DateInterval('PT' . $this->_testHelper->getTestMinutes() . 'M'));
                        }

                        // Set the ship dates on the Recurly subscription and subscription order.
                        $recurlySubscription->starts_at = $start->format('Y-m-d H:i:s');
                        $subscriptionAddonOrder->setData(
                            'ship_start_date',
                            $start->format('Y-m-d H:i:s')
                        )->save();
                    }
                }

                $recurlySubscriptions[] = $recurlySubscription;
            }
        } catch (Exception $e) {
            throw new LocalizedException(__($e->getMessage()));
        }

        return $recurlySubscriptions;
    }

    /**
     * Get Subscription Ids and save them
     * @param Subscription $subscription
     * @throws LocalizedException
     */
    protected function updateSubscriptionIDs($subscription)
    {
        try {
            $activeSubscriptions = Recurly_SubscriptionList::getForAccount(
                $subscription->getData('gigya_id'),
                ['state' => 'active']
            );

            $futureSubscriptions = Recurly_SubscriptionList::getForAccount(
                $subscription->getData('gigya_id'),
                ['state' => 'future']
            );

            $subCodes = [];
            foreach ($activeSubscriptions as $activeSubscription) {
                $subCodes[] = [
                    'subscription_id' => $activeSubscription->getValues()['uuid'],
                    'plan_code' => $activeSubscription->getValues()['plan']->getValues()['plan_code']
                ];
            }

            foreach ($futureSubscriptions as $futureSubscription) {
                $subCodes[] = [
                    'subscription_id' => $futureSubscription->getValues()['uuid'],
                    'plan_code' => $futureSubscription->getValues()['plan']->getValues()['plan_code']
                ];
            }

            // Get the master subscription ID.
            $masterSubscriptionID = '';
            foreach ($subCodes as $subCode) {
                if (in_array($subCode['plan_code'], ['annual', 'seasonal'])) {
                    $masterSubscriptionID = $subCode['subscription_id'];

                    break;
                }
            }

            foreach ($subCodes as $subCode) {
                if (in_array($subCode['plan_code'], ['annual', 'seasonal'])) {
                    $subscription
                        ->setData('subscription_id', $subCode['subscription_id'])
                        ->save();
                } elseif ($subCode['plan_code'] === 'add-ons') {
                    // Get the add-on and update the subscription ID.
                    $addOns = $subscription->getSubscriptionAddonOrders();

                    if ($addOns && $addOns->getFirstItem()) {
                        $addOn = $addOns->getFirstItem();
                        /** @var SubscriptionAddonOrder $addOn */
                        $addOn
                            ->setData('subscription_id', $subCode['subscription_id'])
                            ->save();

                        // Check if there is an associated order and update the
                        // subscription IDs.
                        $order = $addOn->getOrder();

                        if ($order) {
                            $order->addData([
                                'master_subscription_id' => $masterSubscriptionID,
                                'subscription_id' => $subCode['subscription_id'],
                            ])->save();
                        }
                    }
                } else {
                    // Get the subscription order and update the subscription
                    // ID.
                    $subscriptionOrder = $subscription->getSubscriptionOrderBySeasonSlug($subCode['plan_code']);

                    $subscriptionOrder
                        ->setData('subscription_id', $subCode['subscription_id'])
                        ->save();

                    // Check if there is an associated order and update the
                    // subscription IDs.
                    $order = $subscriptionOrder->getOrder();

                    if ($order) {
                        $order->addData([
                            'master_subscription_id' => $masterSubscriptionID,
                            'subscription_id' => $subCode['subscription_id'],
                        ])->save();
                    }
                }
            }
        } catch (Exception $e) {
            $this->_logger->error($e->getMessage());

            throw new LocalizedException(__($e->getMessage()));
        }
    }
}
