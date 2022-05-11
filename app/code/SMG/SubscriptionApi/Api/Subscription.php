<?php

namespace SMG\SubscriptionApi\Api;

use Exception;
use Gigya\GigyaIM\Helper\GigyaMageHelper;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Checkout\Model\Cart;
use Magento\Checkout\Model\Session;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\Address;
use Magento\Customer\Model\AddressFactory;
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Model\ResourceModel\Customer as CustomerResource;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Data\Form\FormKey;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\SecurityViolationException;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Framework\Webapi\Rest\Response;
use Magento\Sales\Model\Order\Status\History;
use Magento\Sales\Model\Order\Status\HistoryFactory;
use Magento\Sales\Model\ResourceModel\Order as OrderResource;
use Magento\Sales\Model\ResourceModel\Order\Status\History as HistoryResource;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Recurly_Client;
use SMG\RecommendationApi\Helper\RecommendationHelper;
use SMG\Sap\Model\ResourceModel\SapOrderBatch as SapOrderBatchResource;
use SMG\Sap\Model\SapOrderBatchFactory;
use SMG\SubscriptionApi\Api\Interfaces\SubscriptionInterface;
use SMG\SubscriptionApi\Exception\SubscriptionException;
use SMG\SubscriptionApi\Helper\RecurlyHelper;
use SMG\SubscriptionApi\Helper\ResponseHelper;
use SMG\SubscriptionApi\Helper\SubscriptionHelper;
use SMG\SubscriptionApi\Helper\SubscriptionOrderHelper;
use SMG\SubscriptionApi\Model\RecurlySubscription;
use SMG\SubscriptionApi\Model\ResourceModel\Subscription as SubscriptionResource;
use SMG\SubscriptionApi\Model\ResourceModel\Subscription as SubscriptionResourceModel;
use SMG\SubscriptionApi\Model\ResourceModel\SubscriptionAddonOrder as SubscriptionAddonOrderResource;
use SMG\SubscriptionApi\Model\ResourceModel\SubscriptionOrder as SubscriptionOrderResource;
use SMG\SubscriptionApi\Model\ResourceModel\SubscriptionOrderItem;
use SMG\SubscriptionApi\Model\Subscription as SubscriptionModel;
use SMG\SubscriptionApi\Model\SubscriptionAddonOrder;
use SMG\SubscriptionApi\Model\SubscriptionFactory;
use SMG\SubscriptionApi\Model\SubscriptionOrder;
use SMG\SubscriptionApi\Model\SubscriptionAddonOrderFactory;
use SMG\SubscriptionApi\Model\SubscriptionRenewalErrorFactory;
use SMG\SubscriptionApi\Model\SubscriptionAddonOrderItemFactory;
use SMG\SubscriptionApi\Model\SubscriptionOrderFactory;
use SMG\SubscriptionApi\Model\SubscriptionOrderItemFactory;
use Magento\Directory\Model\ResourceModel\Region\CollectionFactory as RegionCollectionFactory;
use SMG\SubscriptionApi\Helper\CancelHelper;

/**
 * Class Subscription
 * @package SMG\SubscriptionApi\Api
 */
class Subscription implements SubscriptionInterface
{
    /** @var LoggerInterface */
    protected $_logger;

    /** @var RecommendationHelper */
    protected $_recommendationHelper;

    /** @var RecurlyHelper */
    protected $_recurlyHelper;

    /** @var SubscriptionHelper */
    protected $_subscriptionHelper;

    /** @var Session */
    protected $_customerSession;

    /** @var FormKey */
    protected $_formKey;

    /** @var Cart */
    protected $_cart;

    /** @var Product */
    protected $_product;

    /** @var ProductRepositoryInterface */
    protected $_productRepository;

    /** @var Session */
    protected $_checkoutSession;

    /** @var StoreManagerInterface */
    protected $_storeManager;

    /** @var CustomerFactory */
    protected $_customerFactory;

    /** @var CustomerResource */
    protected $_customerResource;

    /** @var CustomerRepositoryInterface */
    protected $_customerRepository;

    /** @var AddressRepositoryInterface */
    protected $_addressRepository;

    /**
     * @var Address
     */
    protected $_customerAddress;

    /**  @var SubscriptionResourceModel */
    protected $_subscription;

    /** @var SessionManagerInterface */
    protected $_coreSession;

    /**
     * @var AddressFactory
     */
    protected $_addressFactory;

    /**
     * @var SubscriptionOrderHelper
     */
    protected $_subscriptionOrderHelper;

    /**
     * @var Response
     */
    protected $_response;

    /**
     * @var ResponseHelper
     */
    protected $_responseHelper;

    /**
     * @var RecurlySubscription
     */
    protected $_recurlySubscription;

    /**
     * @var GigyaMageHelper
     */
    protected $_gigyaHelper;

    /**
     * @var HistoryFactory
     */
    protected $_historyFactory;

    /**
     * @var HistoryResource
     */
    protected $_historyResource;

    /**
     * @var string
     */
    protected $_loggerPrefix;

    /**
     * @var SubscriptionResource
     */
    protected $_subscriptionResource;

    /**
     * @var SubscriptionOrderResource
     */
    protected $_subscriptionOrderResource;

    /**
     * @var SubscriptionAddonOrderResource
     */
    protected $_subscriptionAddonOrderResource;

    /**
     * @var OrderResource
     */
    protected $_orderResource;

    /**
     * @var SapOrderBatchFactory
     */
    protected $_sapOrderBatchFactory;

    /**
     * @var SapOrderBatchResource
     */
    protected $_sapOrderBatchResource;

    /**
     * @var SubscriptionFactory
     */
    protected $_subscriptionFactory;

    protected $_subscriptionAddonOrderFactory;
    protected $_subscriptionAddonOrderItemFactory;
    protected $_subscriptionOrderFactory;
    protected $_subscriptionOrderItemFactory;
    protected $_regionCollectionFactory;
    protected $_subscriptionRenewalErrorFactory;

    /**
     * @var CancelHelper
     */
    protected $_cancelHelper;

    /**
     * Subscription constructor.
     * @param LoggerInterface $logger
     * @param RecommendationHelper $recommendationHelper
     * @param RecurlyHelper $recurlyHelper
     * @param SubscriptionHelper $subscriptionHelper
     * @param CustomerSession $customerSession
     * @param FormKey $formKey
     * @param Cart $cart
     * @param Session $checkoutSession
     * @param Product $product
     * @param ProductRepositoryInterface $productRepository
     * @param StoreManagerInterface $storeManager
     * @param CustomerFactory $customerFactory
     * @param CustomerRepositoryInterface $customerRepository
     * @param CustomerResource $customerResource
     * @param AddressRepositoryInterface $addressRepository
     * @param Address $customerAddress
     * @param OrderResource $orderResource
     * @param SubscriptionResourceModel $subscription
     * @param SubscriptionFactory $subscriptionFactory
     * @param SubscriptionResource $subscriptionResource
     * @param SubscriptionOrderResource $subscriptionOrderResource
     * @param SubscriptionAddonOrderResource $subscriptionAddonOrderResource
     * @param SapOrderBatchFactory $sapOrderBatchFactory
     * @param SapOrderBatchResource $sapOrderBatchResource
     * @param SessionManagerInterface $coreSession
     * @param AddressFactory $addressFactory
     * @param SubscriptionOrderHelper $subscriptionOrderHelper
     * @param RecurlySubscription $recurlySubscription
     * @param Response $response
     * @param ResponseHelper $responseHelper
     * @param GigyaMageHelper $gigyaMageHelper
     * @param HistoryFactory $historyFactory
     * @param HistoryResource $historyResource
     * @param SubscriptionRenewalErrorFactory $subscriptionRenewalErrorFactory
     * @param CancelHelper $cancelHelper
     */
    public function __construct(
        LoggerInterface $logger,
        RecommendationHelper $recommendationHelper,
        RecurlyHelper $recurlyHelper,
        SubscriptionHelper $subscriptionHelper,
        CustomerSession $customerSession,
        FormKey $formKey,
        Cart $cart,
        Session $checkoutSession,
        Product $product,
        ProductRepositoryInterface $productRepository,
        StoreManagerInterface $storeManager,
        CustomerFactory $customerFactory,
        CustomerRepositoryInterface $customerRepository,
        CustomerResource $customerResource,
        AddressRepositoryInterface $addressRepository,
        Address $customerAddress,
        OrderResource $orderResource,
        SubscriptionResourceModel $subscription,
        SubscriptionFactory $subscriptionFactory,
        SubscriptionResource $subscriptionResource,
        SubscriptionOrderResource $subscriptionOrderResource,
        SubscriptionAddonOrderResource $subscriptionAddonOrderResource,
        SapOrderBatchFactory $sapOrderBatchFactory,
        SapOrderBatchResource $sapOrderBatchResource,
        SessionManagerInterface $coreSession,
        AddressFactory $addressFactory,
        SubscriptionOrderHelper $subscriptionOrderHelper,
        RecurlySubscription $recurlySubscription,
        Response $response,
        ResponseHelper $responseHelper,
        GigyaMageHelper $gigyaMageHelper,
        HistoryFactory $historyFactory,
        HistoryResource $historyResource,
        SubscriptionAddonOrderFactory $subscriptionAddonOrderFactory,
        SubscriptionAddonOrderItemFactory $subscriptionAddonOrderItemFactory,
        SubscriptionOrderFactory $subscriptionOrderFactory,
        SubscriptionOrderItemFactory $subscriptionOrderItemFactory,
        RegionCollectionFactory $regionCollectionFactory,
        SubscriptionRenewalErrorFactory $subscriptionRenewalErrorFactory,
        CancelHelper $cancelHelper
    ) {
        $this->_logger = $logger;
        $this->_recommendationHelper = $recommendationHelper;
        $this->_recurlyHelper = $recurlyHelper;
        $this->_subscriptionHelper = $subscriptionHelper;
        $this->_customerSession = $customerSession;
        $this->_formKey = $formKey;
        $this->_cart = $cart;
        $this->_checkoutSession = $checkoutSession;
        $this->_product = $product;
        $this->_productRepository = $productRepository;
        $this->_storeManager = $storeManager;
        $this->_customerFactory = $customerFactory;
        $this->_customerRepository = $customerRepository;
        $this->_customerResource = $customerResource;
        $this->_recurlyHelper = $recurlyHelper;
        $this->_addressRepository = $addressRepository;
        $this->_customerAddress = $customerAddress;
        $this->_orderResource = $orderResource;
        $this->_subscription = $subscription;
        $this->_subscriptionFactory = $subscriptionFactory;
        $this->_subscriptionResource = $subscriptionResource;
        $this->_subscriptionOrderResource = $subscriptionOrderResource;
        $this->_subscriptionAddonOrderResource = $subscriptionAddonOrderResource;
        $this->_sapOrderBatchFactory = $sapOrderBatchFactory;
        $this->_sapOrderBatchResource = $sapOrderBatchResource;
        $this->_coreSession = $coreSession;
        $this->_addressFactory = $addressFactory;
        $this->_subscriptionOrderHelper = $subscriptionOrderHelper;
        $this->_recurlySubscription = $recurlySubscription;
        $this->_response = $response;
        $this->_responseHelper = $responseHelper;
        $this->_gigyaHelper = $gigyaMageHelper;
        $this->_historyFactory = $historyFactory;
        $this->_historyResource = $historyResource;
        $this->_subscriptionAddonOrderFactory = $subscriptionAddonOrderFactory;
        $this->_subscriptionAddonOrderItemFactory = $subscriptionAddonOrderItemFactory;
        $this->_subscriptionOrderFactory = $subscriptionOrderFactory;
        $this->_subscriptionOrderItemFactory = $subscriptionOrderItemFactory;
        $this->_regionCollectionFactory = $regionCollectionFactory;
        $this->_subscriptionRenewalErrorFactory = $subscriptionRenewalErrorFactory;
        $this->_cancelHelper = $cancelHelper;
        $host = gethostname();
        $ip = gethostbyname($host);
        $this->_loggerPrefix = 'SERVER: ' . $ip . ' SESSION: ' . session_id() . ' - ';

        Recurly_Client::$apiKey = $this->_recurlyHelper->getRecurlyPrivateApiKey();
        Recurly_Client::$subdomain = $this->_recurlyHelper->getRecurlySubdomain();
    }

    /**
     * Process quiz data, build order object and send customer to checkout. Note that we are hijacking the cart for
     * the addition of subscriptions and to make the display easier.
     * @param string $key
     * @param string $subscription_plan
     * @param mixed $data
     * @param mixed $addons
     * @return array|false|string
     *
     * @throws NoSuchEntityException
     * @throws SecurityViolationException
     * @throws LocalizedException
     * @api
     */
    public function addSubscriptionToCart($key, $subscription_plan, $data, $addons = [])
    {
        // Test the form key
        if (!$this->formValidation($key)) {
            throw new SecurityViolationException(__('Unauthorized'));
        }

        // Add subscription to cart
        try {
            /** @var SubscriptionModel $subscription */
            $subscription = $this->_subscription->getSubscriptionByQuizId($this->_coreSession->getQuizId());

            if ($subscription->getSubscriptionStatus() != 'pending') {
                // Subscription is already active or has been canceled, so return.
                $this->_logger->error($this->_loggerPrefix . "Subscription with quiz ID '{$subscription->getQuizId()}' cannot be added to cart since it is active or canceled.");

                $redirect = '/quiz';

                if ($this->_customerSession->isLoggedIn()) {
                    $redirect = '/account/subscription';
                }

                $this->_response->setHttpResponseCode(400);

                return [[
                    'success' => false,
                    'redirect' => $redirect,
                ]];
            }

            // Set the subscription details into the session.
            $this->_coreSession->setData('subscription_details', [
                'subscription_plan' => $subscription_plan,
                'addons' => $addons,
            ]);

            return json_encode(['success' => true]);
        } catch (Exception $e) {
            $this->_logger->error($this->_loggerPrefix . $e->getMessage());
            $response = ['success' => false, 'message' => $e->getMessage()];

            return json_encode($response);
        }
    }

    /**
     * Process cart products and create multiple orders
     *
     * @param string $key
     * @param string $token
     * @param string $quiz_id
     * @param mixed $billing_address
     * @param bool $billing_same_as_shipping
     * @return string
     *
     * @api
     */
    public function createSubscription($key, $token, $quiz_id, $billing_address, $billing_same_as_shipping)
    {
        try {
            // We submitted an order in the last minute, so do not submit
            // another.
            if (time() - $this->_coreSession->getOrderProcessing() <= 60) {
                return;
            }

            $this->_coreSession->setOrderProcessing(time());

            // Get store and website information
            $store = $this->_storeManager->getStore();
            $websiteId = $store->getWebsiteId();

            // Get customer
            $email = $this->_checkoutSession->getQuote()->getCustomerEmail();
            $this->_logger->info($this->_loggerPrefix . "Loading the customer by email: {$email}...");
            $customer = $this->_customerFactory->create();
            $customer->setWebsiteId($websiteId);
            $customer->loadByEmail($email);

            // Make sure customer was found.
            if (! $customer->getId()) {
                $error = 'Customer with email' . $email . ' not found during checkout.';
                $this->_logger->error($this->_loggerPrefix . $error);
                $this->_coreSession->setOrderProcessing(0);

                return $this->_responseHelper->error('Customer account not found.', [], 404);
            }

            // Get customer shipping and billing address
            $mainQuote = $this->_checkoutSession->getQuote();
            $customerShippingAddress = $this->_subscriptionOrderHelper->formatAddress($mainQuote->getShippingAddress());

            // Update the zip codes in the addresses to just use the first 5.
            $customerShippingAddress['postcode'] = substr($customerShippingAddress['postcode'], 0, 5);
            $billing_address['postcode'] = substr($billing_address['postcode'], 0, 5);

            if ($billing_same_as_shipping) {
                $customerBillingAddress = $customerShippingAddress;
            } else {
                $customerBillingAddress = $billing_address;
            }

            // Add checkout addresses to the session.
            $this->_coreSession->setCheckoutShipping($customerShippingAddress);
            $this->_coreSession->setCheckoutBilling($customerBillingAddress);
        } catch (Exception $e) {
            $this->_logger->error($this->_loggerPrefix . $e->getMessage());
            $this->_coreSession->setOrderProcessing(0);

            return $this->_responseHelper->error('There was an error preparing your subscription, please try again.');
        }

        // Update the customer's name from the shipping address.
        try {
            $this->_logger->info($this->_loggerPrefix . "Updating name in M2 and Gigya for account with Gigya ID: {$customer->getData('gigya_uid')}");
            // Update the customer's M2 account.
            $customer->addData([
                'firstname' => $customerShippingAddress['firstname'],
                'lastname' => $customerShippingAddress['lastname'],
            ]);
            $this->_customerResource->save($customer);

            // Update the customer's Gigya account.
            $gigyaData = [
                'profile' => [
                    'firstName' => $customerShippingAddress['firstname'],
                    'lastName' => $customerShippingAddress['lastname'],
                ],
            ];

            try {
                $this->_gigyaHelper->updateGigyaAccount($customer->getData('gigya_uid'), $gigyaData);
            } catch (Exception $e) {
                $this->_logger->error($this->_loggerPrefix . $e->getMessage());
            }
        } catch (Exception $e) {
            $this->_logger->error($this->_loggerPrefix . $e->getMessage());
            $this->_coreSession->setOrderProcessing(0);

            return $this->_responseHelper->error('There was an error updating your account, please try again.');
        }

        // Check the zip code to make sure that it is what they entered during the quiz
        $this->_logger->info($this->_loggerPrefix . 'Verifying shipping zip code matches quiz zip code...');
        try {
            if (
                is_null($this->_coreSession->getZipCode())
                || empty($customerShippingAddress['postcode'])
                || strpos($customerShippingAddress['postcode'], $this->_coreSession->getZipCode()) !== 0 // if the provided quiz zip does not match the first five of the avatax corrected zip then error
            ) {
                $error = 'Your shipping zip code and quiz zip code do not match.';
                $this->_logger->error($this->_loggerPrefix . $error);
                try {
                    $this->_logger->info($this->_loggerPrefix . "Quiz Zip: {$this->_coreSession->getZipCode()} Address Zip Code: {$customerShippingAddress['postcode']}");
                } catch (Exception $e) {
                    $this->_logger->info($this->_loggerPrefix . 'The quiz or shipping zip code is missing.');
                }
                $this->_coreSession->setOrderProcessing(0);

                return $this->_responseHelper->error(
                    $error,
                    ['error_code' => 'Z1']
                );
            }

            // Get the subscription
            $this->_logger->info($this->_loggerPrefix . "Getting the subscription object with quiz ID '{$quiz_id}':...");
            /** @var SubscriptionModel $subscription */
            $subscription = $this->_subscriptionFactory->create();
            $this->_subscriptionResource->load($subscription, $quiz_id, 'quiz_id');

            if (! $subscription || ! $subscription->getId()) {
                $this->_response->setHttpResponseCode(404);
                $error = "Subscription with quiz ID {$quiz_id} not found during checkout.";
                $this->_logger->error($this->_loggerPrefix . $error);
                $this->_coreSession->setOrderProcessing(0);

                return $this->_responseHelper->error($error, ['refresh' => true]);
            }

            // Make sure the subscription is pending.
            if ($subscription->getData('subscription_status') != 'pending') {
                $error = "This subscription with quiz ID '{$quiz_id}' has already been completed or cancelled.";
                $this->_logger->error($this->_loggerPrefix . $error);

                return $this->_responseHelper->error($error, ['refresh' => true]);
            }
        } catch (Exception $e) {
            $this->_logger->error($this->_loggerPrefix . $e->getMessage());
            $this->_coreSession->setOrderProcessing(0);

            return $this->_responseHelper->error('There was an error finding your subscription information, please try again.', ['refresh' => true]);
        }

        // Add customer to subscription.
        $this->_logger->info($this->_loggerPrefix . "Adding the customer with Gigya ID: '{$customer->getData('gigya_uid')}' to the subscription...");
        try {
            $subscription->setData('customer_id', $customer->getData('entity_id'));
            $subscription->setData('gigya_id', $customer->getData('gigya_uid'));
            $this->_subscriptionResource->save($subscription);
        } catch (Exception $e) {
            $error = 'Your account could not be saved. Please try again.';
            $this->_logger->error($this->_loggerPrefix . $error . " : " . $e->getMessage());
            $this->_coreSession->setOrderProcessing(0);

            return $this->_responseHelper->error($error, ['refresh' => true]);
        }

        // Create the subscriptions in Recurly.
        $this->_logger->info($this->_loggerPrefix . 'Creating the Recurly Purchase...');
        try {
            try {
                $recurlyPurchase = $this->_recurlySubscription->createRecurlyPurchase(
                    $token,
                    $subscription,
                    $customer
                );
            } catch (LocalizedException $e) {
                $this->_logger->error($this->_loggerPrefix . $e->getMessage());
                $this->_coreSession->setOrderProcessing(0);

                return $this->_responseHelper->error($e->getMessage(), ['refresh' => true]);
            }

            // Reload the subscription
            $this->_logger->info($this->_loggerPrefix . 'Reloading the subscription...');
            $this->_subscriptionResource->load($subscription, $subscription->getId());

            // Clear the cart.
            $this->_logger->info($this->_loggerPrefix . 'Clearing the cart...');
            $this->clearCart();

            // Process the seasonal orders.
            $this->_logger->info($this->_loggerPrefix . 'Processing the seasonal orders...');
            $subscriptionOrders = $subscription->getSubscriptionOrders();
            $increment_id = [];
            foreach ($subscriptionOrders as $subscriptionOrder) {
                try {
                    $this->clearCustomerAddresses($customer);
                    $this->_logger->info($this->_loggerPrefix . "Processing {$subscriptionOrder->getData('season_name')} order...");
                    $order = $this->_subscriptionOrderHelper->processInvoiceWithSubscriptionId($subscriptionOrder);
                    $this->_logger->info("Created {$subscriptionOrder->getData('season_name')} order with ID: {$order->getId()} ({$order->getIncrementId()})");
                    $increment_id[] = $order->getIncrementId();
                } catch (SubscriptionException $e) {
                    $this->_logger->error($this->_loggerPrefix . $e->getMessage());

                    // We failed to create orders, lets remove any created orders.
                    $this->clearCustomerAddresses($customer);
                    $this->cancelFailedOrders($subscription);
                    $this->_coreSession->setOrderProcessing(0);

                    return $this->_responseHelper->error(
                        $e->getMessage(),
                        ['refresh' => true]
                    );
                } catch (Exception $e) {
                    // Catch any other not typical exceptions and return a generic
                    //error response to the customer.
                    $this->_logger->error($this->_loggerPrefix . $e->getMessage());

                    // We failed to create orders, lets remove any created orders.
                    $this->cancelFailedOrders($subscription);
                    $this->_coreSession->setOrderProcessing(0);

                    // Check if we receive a restricted product error.
                    if (strpos($e->getMessage(), 'selected products is restricted') !== false) {
                        // The restricted product error echo's out an error so
                        // we need to clear the output buffer to prevent that
                        // from corrupting the JSON return.
                        ob_clean();

                        return $this->_responseHelper->error(
                            $e->getMessage(),
                            ['refresh' => true]
                        );
                    }

                    return $this->_responseHelper->error(
                        'We could not process your order at this time. Please try again.',
                        ['refresh' => true]
                    );
                }
            }

            // Process the add-on orders.
            $this->_logger->info($this->_loggerPrefix . 'Processing the add-on orders...');
            $subscriptionAddonOrders = $subscription->getSubscriptionAddonOrders();

            foreach ($subscriptionAddonOrders as $subscriptionAddonOrder) {
                try {
                    $this->clearCustomerAddresses($customer);
                    // Add-on was not selected, so continue.
                    if (!$subscriptionAddonOrder->isSelected()) {
                        continue;
                    }

                    $this->_logger->info($this->_loggerPrefix . "Processing add-on order...");
                    $order = $this->_subscriptionOrderHelper->processInvoiceWithSubscriptionId($subscriptionAddonOrder);
                    $this->_logger->info("Created add-on order with ID: {$order->getId()} ({$order->getIncrementId()})");
                } catch (SubscriptionException $e) {
                    $this->_logger->error($this->_loggerPrefix . $e->getMessage());

                    // We failed to create orders, lets remove any created orders.
                    $this->clearCustomerAddresses($customer);
                    $this->cancelFailedOrders($subscription);
                    $this->_coreSession->setOrderProcessing(0);

                    return $this->_responseHelper->error(
                        $e->getMessage(),
                        ['refresh' => true]
                    );
                } catch (\Throwable $e) {
                    // Catch any other not typical exceptions and return a generic
                    //error response to the customer.
                    $this->_logger->error($this->_loggerPrefix . $e->getMessage());

                    // We failed to create orders, lets remove any created orders.
                    $this->cancelFailedOrders($subscription);
                    $this->_coreSession->setOrderProcessing(0);

                    // Check if we receive a restricted product error.
                    if (strpos($e->getMessage(), 'selected products is restricted') !== false) {
                        // The restricted product error echo's out an error so
                        // we need to clear the output buffer to prevent that
                        // from corrupting the JSON return.
                        ob_clean();

                        return $this->_responseHelper->error(
                            $e->getMessage(),
                            ['refresh' => true]
                        );
                    }

                    return $this->_responseHelper->error(
                        'We could not process your order at this time. Please try again.',
                        ['refresh' => true]
                    );
                }
            }

            // We created the orders, lets invoice the subscription.
            $this->_logger->info($this->_loggerPrefix . 'Invoicing the Recurly purchase...');
            try {
                $this->_recurlySubscription->invoiceRecurlyPurchase(
                    $recurlyPurchase,
                    $subscription,
                    $increment_id[0]
                );
            } catch (LocalizedException $e) {
                $this->_logger->error($this->_loggerPrefix . $e->getMessage());

                // We failed to invoice the Recurly subscription, so lets remove any
                // created orders.
                $this->_logger->error($this->_loggerPrefix . "Terminating any failed subscriptions...");
                $this->_recurlySubscription->terminateFailedRecurlySubscriptions($customer->getData('gigya_uid'));
                $this->clearCustomerAddresses($customer);
                $this->cancelFailedOrders($subscription);
                $this->_coreSession->setOrderProcessing(0);

                return $this->_responseHelper->error(
                    $e->getMessage(),
                    ['refresh' => true]
                );
            }

            // Reload the subscription
            $this->_logger->info($this->_loggerPrefix . 'Reloading the subscription via getData...');
            $subscription = $subscription->load($subscription->getData('entity_id'));

            $this->_logger->info($this->_loggerPrefix . 'Setting subscription to active status...');
            $subscription->setData('subscription_status', 'active');

            $this->_logger->info($this->_loggerPrefix . 'Saving subscription status...');
            $this->_subscriptionResource->save($subscription);

            $this->_logger->info($this->_loggerPrefix . 'Done...');
            $this->_coreSession->setOrderProcessing(0);

            return $this->_responseHelper->success(
                'Subscription created.',
                [
                    'subscription_id' => $subscription->getData('subscription_id'),
                ]
            );
        } catch (Exception $e) {
            $this->_logger->error($this->_loggerPrefix . $e->getMessage());

            if (isset($subscription)) {
                $this->_logger->error($this->_loggerPrefix . "Terminating any failed subscriptions...");
                $this->_recurlySubscription->terminateFailedRecurlySubscriptions($customer->getData('gigya_uid'));
                $this->cancelFailedOrders($subscription);
            }

            $this->_coreSession->setOrderProcessing(0);

            return $this->_responseHelper->error(
                'There was an error processing your subscription, please try again.',
                ['refresh' => true],
                400
            );
        }
    }

    /**
     * @param string $master_subscription_id
     * @param bool $force
     * @return mixed
     */
    public function renewSubscription($master_subscription_id, $force = false)
    {
        $this->_logger->info("Renewing master subscription id: " . $master_subscription_id);

        try {
            /** @var SubscriptionModel $sub */
            $sub = $this->_subscriptionResource->getSubscriptionByMasterSubscriptionId($master_subscription_id);

            /** Return 422 if not found. */
            if (empty($sub)) {
                $message = "Subscription not found for master subscription id: " . $master_subscription_id;
                $this->_logger->error($message);
                return $this->_responseHelper->error(
                    $message,
                    ['refresh' => false],
                    422
                );
            }
            // If force is true we don't care if it has been renewed recently
            if (!$force) {
                /** Return 409 if subscription has been renewed in the last 10 months. */
                $subCreatedAt = date('Y-m-d', strtotime($sub->getData('created_at')));
                $safetyNetDate = date('Y-m-d', strtotime("+10 months", strtotime($subCreatedAt)));
                $now = date('Y-m-d');

                if ($now < $safetyNetDate) {
                    $message = "Subscription has been renewed too recently for master subscription id: " . $master_subscription_id;
                    $this->_logger->error($message);
                    $this->createRenewalError($master_subscription_id, $message);
                    return $this->_responseHelper->error(
                        $message,
                        ['refresh' => false],
                        409
                    );
                }
            }

            // Get new recommendation for quiz id
            $url = filter_var(
                trim(
                    str_replace('{completedQuizId}', $sub->getData('quiz_id'), $this->_recommendationHelper->getQuizResultLegacyApiPath()),
                    '/'
                ),
                FILTER_SANITIZE_URL
            );
            $this->_logger->info("Fetching updated recommendation for quiz id: " . $sub->getData('quiz_id'));
            $response = $this->_recommendationHelper->request($url, '', 'GET');

            // If the new rec engine fails, use legacy renewal process
            if (empty($response)) {
                $this->createRenewalError($master_subscription_id, "New Rec Engine returned empty for quiz_id ". $sub->getData('quiz_id'));
                return $this->renewSubscriptionLegacy($master_subscription_id, true);
            }

            $this->_logger->info("Create renewal subscription for " . $master_subscription_id . " from subscription id " . $sub->getId());

            $newSub = $this->createRenewalSubscriptionWithNewRec($sub, $response[0]);

            // Recurly account
            $account = $this->_recurlySubscription->getRecurlyAccount($sub->getData('gigya_id'));
            // Magento customer
            $customer = $sub->getCustomer();

            $shippingAddresses = $account->shipping_addresses;
            $shippingAddress = null;
            $billingAddress = null;

            if ($shippingAddresses) {
                $shippingAddress = $shippingAddresses[count($shippingAddresses) - 1];
            }

            $billingInfo = $account->billing_info;

            if ($billingInfo && $billingInfo->getValues()['address']) {
                $billingAddress = $billingInfo->getValues()['address'];
            }

            $recurlySubs = $account->subscriptions->get();
            $invoice = null;
            $paid = 0;
            $price = 0;
            $discount = 0;
            $tax = 0;
            $invoiceNumber = '';

            // Use recurly to populate totals
            foreach ($recurlySubs as $recurlySub) {
                $planCode = $recurlySub->getValues()['plan']->getValues()['plan_code'];
                $state = $recurlySub->getValues()['state'];
                if (in_array($state, ['active', 'future']) and in_array($planCode, ['annual', 'seasonal']) and $recurlySub->invoice) {
                    $this->_logger->info("Get the recurly invoice for " . $master_subscription_id);
                    $invoice = $recurlySub->invoice->get();
                    $paid += $invoice->total_in_cents;
                    $price += $invoice->subtotal_before_discount_in_cents;
                    $discount -= $invoice->discount_in_cents;
                    $tax += $invoice->tax_in_cents;
                    $invoiceNumber = $invoice->invoice_number;
                } else if (in_array($state, ['active', 'future']) and $recurlySub->invoice) {
                    $invoice = $recurlySub->invoice->get();
                    $recurlySubPrices[$planCode] = $invoice->subtotal_before_discount_in_cents;
                    $paid += $invoice->total_in_cents;
                    $price += $invoice->subtotal_before_discount_in_cents;
                    $discount -= $invoice->discount_in_cents;
                    $tax += $invoice->tax_in_cents;
                }
            }

            $newSub->addData([
                'recurly_invoice' => $invoiceNumber ?: '',
                'paid' => $this->convertAmountToDollars($paid),
                'price' => $this->convertAmountToDollars($price),
                'discount' => $this->convertAmountToDollars($discount),
                'tax' => $this->convertAmountToDollars($tax)
            ]);

            // Get addresses from recurly
            $billing = $billingAddress->getValues();
            $shipping = $shippingAddress->getValues();

            if (!$billing || !$shipping) {
                throw new SubscriptionException("Could not retrieve shipping or billing address for customer.");
            }

            if (empty($billing['phone'])) {
                $billing['phone'] = $shipping['phone'];
            }

            $this->_coreSession->setCheckoutShipping($this->formatAddressFromRecurlyInfo($shipping));
            $this->_coreSession->setCheckoutBilling($this->formatAddressFromRecurlyInfo($billing));

            // Build Subscription Orders for processing
            $newOrders = [];

            foreach ($response[0]['plan']['products'] as $order) {
                $this->_logger->info("Create renewal subscription order for " . $master_subscription_id . " from subscription order id " . $order['applicationWindow']['seasonSlug']);

                $newOrder = $this->createRenewalSubscriptionOrderWithNewRec($newSub->getData('entity_id'), $order, 0);

                $subscriptionOrderPrice = 0;

                // Create the Subscription Order Items
                foreach ($order['childProducts'] as $item) {
                    /** @var SubscriptionOrderItem $subscriptionOrderItem */
                    $subscriptionOrderItem = $this->_subscriptionOrderItemFactory->create();
                    $subscriptionOrderItem->addData([
                        'subscription_order_entity_id' => $newOrder->getId(),
                        'catalog_product_sku' => $item['sku'],
                        'qty' => $item['quantity'],
                        'price' => $item['price'],
                    ])->save();
                    $newOrderItems[] = $subscriptionOrderItem;

                    $subscriptionOrderPrice += $item['price'] * $item['quantity'];
                }

                $newOrder->setData('price', $subscriptionOrderPrice)->save();

                $newOrders[] = $newOrder;
            }
            // Create and process magento orders from sub info
            foreach($newOrders as $order) {
                $this->_logger->info("Process the subscription order for " . $master_subscription_id);
                $this->_subscriptionOrderHelper->processOrder($customer, $order, true);
            }

            // Reconcile subscription ids with Recurly
            $this->_recurlySubscription->updateSubscriptionIDs($newSub);

            // Success. Save and quit.
            $this->_subscriptionResource->save($newSub);
            $sub->setData('subscription_status', 'renewed'); // Set old sub as renewed
            $sub->save();

            return true;

        } catch (SubscriptionException $se) {
                if (isset($newSub)) {
                    $this->_logger->error("Renewal Failed for $master_subscription_id");

                    $newSub->setData('subscription_status', 'renewal_failed')->save();
                    try {
                        $this->cancelFailedOrders($newSub, true);
                    } catch (Exception $e) {
                        $message = "Error Canceling Orders: ".$e->getMessage();
                        $this->_logger->error($master_subscription_id . ": " . $message);
                        $this->createRenewalError($master_subscription_id, $message);
                    }
                }


                $message = "SubscriptionException: ".$se->getMessage();
                $this->_logger->error($master_subscription_id . ": " . $message);
                $this->createRenewalError($master_subscription_id, $message);
                return $this->_responseHelper->error(
                    $message,
                    ['refresh' => false],
                    400
                );
        } catch (\Throwable $ge) {
                if (isset($newSub)) {
                    $this->_logger->error("Renewal Failed for $master_subscription_id");

                    $newSub->setData('subscription_status', 'renewal_failed')->save();
                    try {
                        $this->cancelFailedOrders($newSub, true);
                    } catch (Exception $e) {
                        $message = "Error Canceling Orders: ".$e->getMessage();
                        $this->_logger->error($master_subscription_id . ": " . $message);
                        $this->createRenewalError($master_subscription_id, $message);
                    }
                }
                $statusCode = 400;
                $retry = false;
                if (str_contains($ge->getMessage(), 'calculate tax')) {
                    $message = "AvaTax Exception: We got an error regarding avatax tax calculation. Please rerun the renewal subscription api. " . $ge->getMessage();
                    $statusCode = 504;
                    $retry = true;

                    $this->_logger->error($message . " for " . $master_subscription_id);
                }
                else{
                    $message = "General Exception: ".$ge->getMessage();
                }
                $this->createRenewalError($master_subscription_id, $message);

                $data = ['refresh' => false];
                if ($retry) {
                    $data['retry'] = true;
                }

                return $this->_responseHelper->error(
                    $message,
                    $data,
                    $statusCode
                );
            }

    }

    /**
     * Test the form key for CSRF form validation
     *
     * @param $key
     * @return bool
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function formValidation($key)
    {
        if ($this->_subscriptionHelper->useCsrf($this->_storeManager->getStore()->getId())) {
            return $this->_formKey->getFormKey() === $key;
        }

        return true;
    }

    /**
     * Delete customer addresses, because we don't want to store them in the address book,
     * so they will always need to enter their shipping/billing details on checkout
     *
     * @param Customer $customer
     */
    protected function clearCustomerAddresses(&$customer)
    {
        try {
            $addresses = $customer->getAddressesCollection();

            foreach ($addresses as $address) {
                $this->_addressRepository->deleteById($address->getId());
            }

            $customer->cleanAllAddresses();

            $customer->setDefaultBilling(null);
            $customer->setDefaultShipping(null);

            $this->_customerResource->save($customer);
        } catch (Exception $e) {
            $this->_logger->error($this->_loggerPrefix . 'Could not clear addresses - ' . $e->getMessage());
        }
    }

    /**
     * Cancels orders due to a failure during checkout.
     *
     * @param SubscriptionModel $subscription
     * @param bool $isFromRenewal
     */
    protected function cancelFailedOrders(SubscriptionModel $subscription, $isFromRenewal = false)
    {
        $this->_logger->info($this->_loggerPrefix . 'Failed to create subscription, so let\'s cancel any orders.');

        // Get the seasonal orders.
        try {
            $seasonalOrders = $subscription->getSubscriptionOrders()->getItems();
            $addOns = $subscription->getSubscriptionAddonOrders()->getItems();

            $subscriptionOrders = array_merge($seasonalOrders, $addOns);

            foreach ($subscriptionOrders as $subscriptionOrder) {
                /* @var SubscriptionOrder | SubscriptionAddonOrder $subscriptionOrder */
                $this->_logger->info($this->_loggerPrefix . "Loading order '{$subscriptionOrder->getData('sales_order_id')}' so it can be closed due to a checkout error...");
                $order = $subscriptionOrder->getOrder();

                if ($order) {
                    $orderID = $order->getId();

                    // Cancel the order and remove the subscription information.
                    $order->addData([
                        'master_subscription_id' => null,
                        'subscription_id' => null,
                    ])->setStatus('closed');
                    $this->_orderResource->save($order);
                    $this->_logger->info($this->_loggerPrefix . "Closed order: {$order->getId()} ({$order->getIncrementId()})");

                    $this->addOrderHistory($orderID, 'Failed to create subscription: rolling back orders.', 'closed');

                    // Mark the SAP batch records as not orders.
                    $sapOrderBatch = $this->_sapOrderBatchFactory->create();
                    $this->_sapOrderBatchResource->load($sapOrderBatch, $orderID, 'order_id');

                    if ($sapOrderBatch->getId()) {
                        $sapOrderBatch->setData('is_order', 0);
                        $this->_sapOrderBatchResource->save($sapOrderBatch);
                        $this->_logger->info($this->_loggerPrefix . "Set SAP is_order to 0 for order: {$orderID} ({$order->getIncrementId()})");
                    }
                }

                // Update status and remove order from subscription order.
                $subscriptionOrder->addData([
                    'subscription_order_status' => 'pending',
                    'sales_order_id' => null,
                ]);

                $this->_subscriptionOrderHelper->saveSubscriptionOrder($subscriptionOrder);
            }
        } catch (Exception $e) {
            $message = 'Failed to close orders on failed order creation with message: ' . $e->getMessage();
            $this->_logger->error($this->_loggerPrefix . $message);
            if ($isFromRenewal) {
                $this->createRenewalError($subscription->getData('subscription_id'), $message);
            }
        }
    }

    /**
     * This function will update the sales_order_status_history.
     * This table displays on the Order under the comments section.
     *
     * @param $orderId
     * @param $message
     * @param $status
     */
    private function addOrderHistory($orderId, $message, $status)
    {
        try {
            // get the date for today with time
            $today = date('Y-m-d H:i:s');

            // add the error to the history
            /**
             * @var History $orderHistory
             */
            $orderHistory = $this->_historyFactory->create();

            // set the desired values
            $orderHistory->setParentId($orderId);
            $orderHistory->setComment($message);
            $orderHistory->setStatus($status);
            $orderHistory->setCreatedAt($today);
            $orderHistory->setEntityName('order');

            // save the history for displaying on the order
            $this->_historyResource->save($orderHistory);
        } catch (Exception $e) {
            $errorMsg = "Could not add to the order history for order - " . $orderId . " - " . $e->getMessage();
            $this->_logger->error($this->_loggerPrefix . $errorMsg);
        }
    }

    /**
     * Clear the cart.
     *
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    protected function clearCart()
    {
        // Clear the cart.
        $mainQuote = $this->_checkoutSession->getQuote();
        $quoteItems = $mainQuote->getItemsCollection();

        // Remove items from the quote, because there will be duplicate orders created
        foreach ($quoteItems as $item) {
            $this->_cart->removeItem($item->getItemId())->save();
        }
    }

    /**
     * Get Subscription Data For Data Sync.
     *
     * @return array
     */
    public function getSubscriptionDataForSync()
    {
        return $this->_subscriptionHelper->getSubscriptionDataForSync();
    }

    /**
     * @param string $quiz_id
     * @return mixed
     */
    public function getSubscriptionByQuizId($quiz_id)
    {
        /** @var SubscriptionModel $sub */
        try {
            $sub = $this->_subscriptionResource->getSubscriptionByQuizId($quiz_id);
        } catch (LocalizedException $e) {
            $this->_logger->error($e->getMessage());
            return $this->_responseHelper->error(
                $e->getMessage(),
                ['refresh' => false],
                422
            );
        }

        if ($sub) {
            return $this->_responseHelper->success(
                'Subscription found.',
                [
                    'entity_id'=>$sub->getData('entity_id'),
                    'quiz_id'=>$sub->getData('quiz_id'),
                    'gigya_id'=>$sub->getData('gigya_id'),
                    'quiz_completed_at'=>$sub->getData('quiz_completed_at'),
                    'origin'=>$sub->getData('origin'),
                    'lawn_zip'=>$sub->getData('lawn_zip'),
                    'zone_name'=>$sub->getData('zone_name'),
                    'lawn_size'=>$sub->getData('lawn_size'),
                    'lawn_type'=>$sub->getData('lawn_type'),
                    'customer_id'=>$sub->getData('customer_id'),
                    'subscription_id'=>$sub->getData('subscription_id'),
                    'subscription_type'=>$sub->getData('subscription_type'),
                    'subscription_status'=>$sub->getData('subscription_status'),
                    'subscription_start_date'=>$sub->getData('subscription_start_date'),
                    'subscription_end_date'=>$sub->getData('subscription_end_date'),
                    'price'=>$sub->getData('price'),
                    'discount'=>$sub->getData('discount'),
                    'created_at'=>$sub->getData('created_at'),
                    'updated_at'=>$sub->getData('updated_at'),
                    'tax'=>$sub->getData('tax'),
                    'paid'=>$sub->getData('paid'),
                    'recurly_invoice'=>$sub->getData('recurly_invoice'),
                    'is_full_refund'=>$sub->getData('is_full_refund')
                ]
            );
        } else {
            $message = "Could not find subscription for quiz id: ".$quiz_id;
            $this->_logger->error($message);
            return $this->_responseHelper->error(
                $message,
                ['refresh' => false],
                422
            );
        }
    }

    public function createRenewalSubscriptionWithNewRec($oldSub, $newRec) {
        $newSub = $this->_subscriptionFactory->create();
        $oldData = $oldSub->getData();
        unset($oldData['entity_id']);
        unset($oldData['created_at']);
        unset($oldData['updated_at']);
        $oldData['is_full_refund'] = 0;


        $newData = $oldData;
        $newData['quiz_id'] = $newRec['quizId'];
        $newData['gigya_id'] = $newRec['gigyaId'];
        $newData['lawn_zip'] = $newRec['zipCode'];
        $newData['zone_name'] = 'Zone ' . $newRec['lawnZone'];
        $newData['lawn_size'] = $newRec['answerValues']['lawnArea'];
        $newData['lawn_type'] = $this->_recommendationHelper->getGrassTypeFromEnum($newRec['answerValues']['grassType']);
        $newData['subscription_status'] = SubscriptionModel::STATE_ACTIVE;
        $newData['subscription_start_date'] = null;
        $newData['subscription_end_date'] = null;

        $newSub->setData($oldData);
        return $newSub->save();
    }

    public function createRenewalSubscriptionOrderWithNewRec($parentId, $recOrder, $price) {
        $newSubOrder = $this->_subscriptionOrderFactory->create();
        $oldData = [];
        unset($oldData['entity_id']);
        unset($oldData['created_at']);
        unset($oldData['updated_at']);
        $oldData['subscription_entity_id'] = $parentId;
        $oldData['application_start_date'] = date('Y-m-d H:i:s', strtotime($recOrder['applicationWindow']['startDate']));
        $oldData['application_end_date'] = date('Y-m-d H:i:s', strtotime($recOrder['applicationWindow']['endDate']));
        $oldData['ship_start_date'] = date('Y-m-d H:i:s', strtotime($recOrder['applicationWindow']['shipStartDate']));
        $oldData['ship_end_date'] = date('Y-m-d H:i:s', strtotime($recOrder['applicationWindow']['shipEndDate']));
        $oldData['season_name'] = ucwords($recOrder['applicationWindow']['season']);
        $oldData['season_slug'] = $recOrder['applicationWindow']['seasonSlug'];
        $oldData['price'] = $price;
        $oldData['next_cron_date'] = null;
        $oldData['subscription_id'] = null;
        $oldData['sales_order_id'] = null;
        $oldData['subscription_order_status'] = 'pending';

        $newSubOrder->setData($oldData);

        return $newSubOrder->save();
    }

    public function createRenewalSubscription($oldSub) {
        $newSub = $this->_subscriptionFactory->create();
        $oldData = $oldSub->getData();
        unset($oldData['entity_id']);
        unset($oldData['created_at']);
        unset($oldData['updated_at']);
        $oldData['updated_at'] = null;
        $oldData['updated_at'] = null;
        $oldData['updated_at'] = null;
        $oldData['updated_at'] = null;
        $oldData['updated_at'] = null;
        $oldData['is_full_refund'] = 0;

        $newSub->setData($oldData);

        return $newSub->save();
    }

    public function createRenewalSubscriptionOrder($oldSubOrder, $parentId) {
        $newSubOrder = $this->_subscriptionOrderFactory->create();
        $oldData = $oldSubOrder->getData();
        unset($oldData['entity_id']);
        unset($oldData['created_at']);
        unset($oldData['updated_at']);
        $oldData['subscription_entity_id'] = $parentId;
        $oldData['application_start_date'] = date('Y-m-d H:i:s', strtotime('+1 year', strtotime($oldData['application_start_date'])));
        $oldData['application_end_date'] = date('Y-m-d H:i:s', strtotime('+1 year', strtotime($oldData['application_end_date'])));
        $oldData['ship_start_date'] = date('Y-m-d H:i:s', strtotime('+1 year', strtotime($oldData['ship_start_date'])));
        $oldData['ship_end_date'] = date('Y-m-d H:i:s', strtotime('+1 year', strtotime($oldData['ship_end_date'])));
        $oldData['subscription_id'] = null;
        $oldData['sales_order_id'] = null;
        $oldData['subscription_order_status'] = 'pending';

        $newSubOrder->setData($oldData);

        return $newSubOrder->save();
    }

    public function createRenewalSubscriptionOrderItem($oldSubOrderItem, $parentId) {
        $newSubOrderItem = $this->_subscriptionOrderItemFactory->create();
        $oldData = $oldSubOrderItem->getData();
        unset($oldData['entity_id']);
        unset($oldData['created_at']);
        unset($oldData['updated_at']);
        $oldData['subscription_order_entity_id'] = $parentId;
        $newSubOrderItem->setData($oldData);

        return $newSubOrderItem->save();
    }

    public function createRenewalError($masterSubscriptionId, $error) {
        $this->_logger->info("Creating Renewal Error record for " . $masterSubscriptionId);

        $newSubError = $this->_subscriptionRenewalErrorFactory->create();
        $data['master_subscription_id'] = $masterSubscriptionId;
        $data['error_message'] = $error;
        $newSubError->setData($data);

        return $newSubError->save();
    }

    public function formatAddressFromRecurlyInfo($address)
    {
        $region = $this->_regionCollectionFactory->create()
            ->addFieldToFilter('code', ['eq' => $address['state']])
            ->addFieldToFilter('country_id', ['eq' => $address['country']])
            ->getFirstItem()
            ->toArray();

        $return = [
            'firstname' => $address['first_name'],
            'lastname' => $address['last_name'],
            'street' => $address['address1'],
            'city' => $address['city'],
            'country_id' => $address['country'],
            'region' => $address['state'],
            'region_id' => intval($region['region_id']),
            'postcode' => substr($address['zip'], 0, 5),
            'telephone' => $address['phone'],
            'save_in_address_book' => 0,
            'same_as_billing' => 0
        ];

        return $return;
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
     * @param string $master_subscription_id
     * @return mixed
     */
    public function cancelSubscription($master_subscription_id) {

        $this->_logger->debug("Cancel master subscription id: " . $master_subscription_id);

        // Cancel Subscriptions
        try {
            $this->_cancelHelper->cancelSubscriptions($master_subscription_id,'','api');
        } catch (Exception $e) {
            $this->_logger->error($e->getMessage());

            return json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }

        return json_encode([
            'success' => true,
            'message' => 'Subscriptions successfully cancelled.'
        ]);
    }

    /**
     * @param string $subscription_entity_ids
     * @return mixed
     */
    public function updateSubscriptionIds($subscription_entity_ids) {

        $ids = explode(',', $subscription_entity_ids);

        $errors = [];

        foreach ($ids as $id) {
            try {
                /** @var SubscriptionModel $sub */
                $subscription = $this->_subscriptionFactory->create();
                $this->_subscriptionResource->load($subscription, $id, 'entity_id');
                $this->_recurlySubscription->updateSubscriptionIDs($subscription);
                $subscription->setData('subscription_status', 'active')->save();

            } catch (Exception $e) {
                $error = 'There was an issue saving the subscription information : ' . $id;
                $this->_logger->error($error . " : " . $e->getMessage());

                $errors[] =  [
                    'success' => false,
                    'message' => $error . " : " . $e->getMessage()
                ];
            }
        }

        if (empty($errors)) {
            return json_encode([
                'success' => true,
                'message' => 'Subscriptions successfully updated.'
            ]);
        } else {
            return json_encode([
                'success' => false,
                'message' => json_encode($errors)
            ]);
        }

    }

    /**
     * @param string $master_subscription_id
     * @param bool $force
     * @return mixed
     */
    public function renewSubscriptionLegacy($master_subscription_id, $force = false) {
        $this->_logger->info("Renewing master subscription id: " . $master_subscription_id);

        try {
            /** @var SubscriptionModel $sub */
            $sub = $this->_subscriptionResource->getSubscriptionByMasterSubscriptionId($master_subscription_id);

            if (empty($sub)) {
                $message = "Subscription not found for master subscription id: ".$master_subscription_id;
                $this->_logger->error($message);
                return $this->_responseHelper->error(
                    $message,
                    ['refresh' => false],
                    422
                );
            }

            // If force is true we don't care if it has been renewed recently
            if (!$force) {
                /** Return 409 if subscription has been renewed in the last 10 months. */
                $subCreatedAt = date('Y-m-d', strtotime($sub->getData('created_at')));
                $safetyNetDate = date('Y-m-d', strtotime("+10 months", strtotime($subCreatedAt)));
                $now = date('Y-m-d');

                if ($now < $safetyNetDate) {
                    $message = "Subscription has been renewed too recently for master subscription id: " . $master_subscription_id;
                    $this->_logger->error($message);
                    $this->createRenewalError($master_subscription_id, $message);
                    return $this->_responseHelper->error(
                        $message,
                        ['refresh' => false],
                        409
                    );
                }
            }

            $customer = $sub->getCustomer();
            $subOrders = $sub->getSubscriptionOrders();

            $isPreviousSubscriptionOkToRenew = true;

            /** @var SubscriptionOrder $order */
            foreach ($subOrders as $order) {
                $subscriptionOrderStatus = $order->getData('subscription_order_status');
                if (!empty($subscriptionOrderStatus) && in_array($subscriptionOrderStatus, array( 'canceled', 'failed'))) {
                    $isPreviousSubscriptionOkToRenew = false;
                }
            }

            if (!$isPreviousSubscriptionOkToRenew) {
                $message = "SubscriptionException: The past subscriptions has cancellations or failures.";
                $this->_logger->info($master_subscription_id . ": " . $message);
                $this->createRenewalError($master_subscription_id, $message);
                return $this->_responseHelper->error(
                    $message,
                    ['refresh' => false],
                    409
                );
            }

            $this->_logger->info("Create renewal subscription for " . $master_subscription_id . " from subscription id " . $sub->getId());

            $newSub = $this->createRenewalSubscription($sub);
            $newSubOrders = [];
            $newSubOrderItems = [];

            foreach ($subOrders as $order) {
                $this->_logger->info("Create renewal subscription order for " . $master_subscription_id . " from subscription order id " . $order->getId());

                $newOrder = $this->createRenewalSubscriptionOrder($order, $newSub->getData('entity_id'));
                foreach ($order->getOrderItems() as $item) {
                    $this->_logger->info("Create renewal subscription order item for " . $master_subscription_id . " from subscription order item id " . $item->getId());

                    $newSubOrderItems[] = $this->createRenewalSubscriptionOrderItem($item, $newOrder->getId());
                }
                $newSubOrders[] = $newOrder;
            }

            $account = $this->_recurlySubscription->getRecurlyAccount($sub->getData('gigya_id'));

            $recurlySubs = $account->subscriptions->get();
            $invoice = null;

            foreach ($recurlySubs as $recurlySub) {
                $planCode = $recurlySub->getValues()['plan']->getValues()['plan_code'];
                $state = $recurlySub->getValues()['state'];
                if (in_array($planCode, ['annual', 'seasonal']) and in_array($state, ['active', 'future']) and $recurlySub->invoice) {
                    $this->_logger->info("Get the recurly invoice for " . $master_subscription_id);
                    $invoice = $recurlySub->invoice->get();
                }
            }

            $billing = $invoice->getValues()['address']->getValues();
            $shipping = $invoice->getValues()['shipping_address']->getValues();

            if (empty($billing['phone'])) {
                $billing['phone'] = $shipping['phone'];
            }

            $this->_coreSession->setCheckoutShipping($this->formatAddressFromRecurlyInfo($shipping));
            $this->_coreSession->setCheckoutBilling($this->formatAddressFromRecurlyInfo($billing));


            foreach($newSubOrders as $order) {
                $this->_logger->info("Process the subscription order for " . $master_subscription_id);

                $this->_subscriptionOrderHelper->processOrder($customer, $order);
            }

            $this->_recurlySubscription->updateSubscriptionIDs($newSub);

            $newSub->addData([
                'recurly_invoice' => $invoice->invoice_number ?: '',
                'paid' => $this->convertAmountToDollars($invoice->total_in_cents),
                'price' => $this->convertAmountToDollars($invoice->subtotal_before_discount_in_cents),
                'discount' => $this->convertAmountToDollars(-$invoice->discount_in_cents),
                'tax' => $this->convertAmountToDollars($invoice->tax_in_cents)
            ]);

            $this->_subscriptionResource->save($newSub);

            $sub->setData('subscription_status', 'renewed'); // Set old sub as renewed
            $sub->save();
            return true;

        } catch (SubscriptionException $se) {
            if (isset($newSub)) {
                $this->_logger->error("Renewal Failed for $master_subscription_id");

                $newSub->setData('subscription_status', 'renewal_failed')->save();
                try {
                    $this->cancelFailedOrders($newSub, true);
                } catch (Exception $e) {
                    $message = "Error Canceling Orders: ".$e->getMessage();
                    $this->_logger->error($master_subscription_id . ": " . $message);
                    $this->createRenewalError($master_subscription_id, $message);
                }
            }

            $message = "SubscriptionException: ".$se->getMessage();
            $this->_logger->error($master_subscription_id . ": " . $message);
            $this->createRenewalError($master_subscription_id, $message);
            return $this->_responseHelper->error(
                $message,
                ['refresh' => false],
                400
            );
        } catch (\Throwable $ge) {
            if (isset($newSub)) {
                $this->_logger->error("Renewal Failed for $master_subscription_id");

                $newSub->setData('subscription_status', 'renewal_failed')->save();
                try {
                    $this->cancelFailedOrders($newSub, true);
                } catch (Exception $e) {
                    $message = "Error Canceling Orders: ".$e->getMessage();
                    $this->_logger->error($master_subscription_id . ": " . $message);
                    $this->createRenewalError($master_subscription_id, $message);
                }
            }
            $statusCode = 400;
            $retry = false;
            if (str_contains($ge->getMessage(), 'calculate tax')) {
                $message = "AvaTax Exception: We got an error regarding avatax tax calculation. Please rerun the renewal subscription api.";
                $statusCode = 504;
                $retry = true;

                $this->_logger->error($message . " for " . $master_subscription_id);
            }
            else{
                $message = "General Exception: ".$ge->getMessage();
            }
            $this->createRenewalError($master_subscription_id, $message);

            $data = ['refresh' => false];
            if ($retry) {
                $data['retry'] = true;
            }

            return $this->_responseHelper->error(
                $message,
                $data,
                $statusCode
            );
        }

    }

}
