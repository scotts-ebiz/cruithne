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
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Data\Form\FormKey;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\SecurityViolationException;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Framework\Webapi\Rest\Response;
use Magento\Sales\Model\Order\Status\History;
use Magento\Sales\Model\Order\Status\HistoryFactory;
use Magento\Sales\Model\ResourceModel\Order\Invoice\CollectionFactory as InvoiceCollectionFactory;
use Magento\Sales\Model\ResourceModel\Order\Status\History as HistoryResource;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Recurly_Client;
use SMG\RecommendationApi\Helper\RecommendationHelper;
use SMG\Sap\Model\ResourceModel\SapOrderBatch\CollectionFactory as SapOrderBatchCollectionFactory;
use SMG\SubscriptionApi\Api\Interfaces\SubscriptionInterface;
use SMG\SubscriptionApi\Exception\SubscriptionException;
use SMG\SubscriptionApi\Helper\RecurlyHelper;
use SMG\SubscriptionApi\Helper\ResponseHelper;
use SMG\SubscriptionApi\Helper\SubscriptionHelper;
use SMG\SubscriptionApi\Helper\SubscriptionOrderHelper;
use SMG\SubscriptionApi\Model\RecurlySubscription;
use SMG\SubscriptionApi\Model\ResourceModel\Subscription as SubscriptionResourceModel;
use SMG\SubscriptionApi\Model\ResourceModel\Subscription\CollectionFactory as SubscriptionResourceCollectionFactory;
use SMG\SubscriptionApi\Model\Subscription as SubscriptionModel;
use SMG\SubscriptionApi\Model\SubscriptionAddonOrder;
use SMG\SubscriptionApi\Model\SubscriptionOrder;

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

    /** @var SubscriptionResourceCollectionFactory */
    protected $_subscriptionCollectionFactory;

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
     * @var SapOrderBatchCollectionFactory
     */
    protected $_sapOrderBatchCollectionFactory;

    /**
     * @var InvoiceCollectionFactory
     */
    protected $_invoiceCollectionFactory;

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
     * @param AddressRepositoryInterface $addressRepository
     * @param Address $customerAddress
     * @param SubscriptionResourceModel $subscription
     * @param SubscriptionResourceCollectionFactory $subscriptionCollectionFactory
     * @param InvoiceCollectionFactory $invoiceCollectionFactory
     * @param SapOrderBatchCollectionFactory $sapInvoiceCollectionFactory
     * @param SessionManagerInterface $coreSession
     * @param AddressFactory $addressFactory
     * @param SubscriptionOrderHelper $subscriptionOrderHelper
     * @param RecurlySubscription $recurlySubscription
     * @param Response $response
     * @param ResponseHelper $responseHelper
     * @param GigyaMageHelper $gigyaMageHelper
     * @param HistoryFactory $historyFactory
     * @param HistoryResource $historyResource
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
        AddressRepositoryInterface $addressRepository,
        Address $customerAddress,
        SubscriptionResourceModel $subscription,
        SubscriptionResourceCollectionFactory $subscriptionCollectionFactory,
        InvoiceCollectionFactory $invoiceCollectionFactory,
        SapOrderBatchCollectionFactory $sapInvoiceCollectionFactory,
        SessionManagerInterface $coreSession,
        AddressFactory $addressFactory,
        SubscriptionOrderHelper $subscriptionOrderHelper,
        RecurlySubscription $recurlySubscription,
        Response $response,
        ResponseHelper $responseHelper,
        GigyaMageHelper $gigyaMageHelper,
        HistoryFactory $historyFactory,
        HistoryResource $historyResource
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
        $this->_recurlyHelper = $recurlyHelper;
        $this->_addressRepository = $addressRepository;
        $this->_customerAddress = $customerAddress;
        $this->_subscription = $subscription;
        $this->_subscriptionCollectionFactory = $subscriptionCollectionFactory;
        $this->_invoiceCollectionFactory = $invoiceCollectionFactory;
        $this->_sapOrderBatchCollectionFactory = $sapInvoiceCollectionFactory;
        $this->_coreSession = $coreSession;
        $this->_addressFactory = $addressFactory;
        $this->_subscriptionOrderHelper = $subscriptionOrderHelper;
        $this->_recurlySubscription = $recurlySubscription;
        $this->_response = $response;
        $this->_responseHelper = $responseHelper;
        $this->_gigyaHelper = $gigyaMageHelper;
        $this->_historyFactory = $historyFactory;
        $this->_historyResource = $historyResource;

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
     * @throws LocalizedException
     * @throws NoSuchEntityException
     *
     * @api
     */
    public function createSubscription($key, $token, $quiz_id, $billing_address, $billing_same_as_shipping)
    {
        try {
            // Get store and website information
            $store = $this->_storeManager->getStore();
            $websiteId = $store->getWebsiteId();

            // Get customer
            $this->_logger->info($this->_loggerPrefix . 'Loading the customer...');
            $customer = $this->_customerFactory->create();
            $customer->setWebsiteId($websiteId);
            $customer->loadByEmail($this->_checkoutSession->getQuote()->getCustomerEmail());
            $customerId = $customer->getId();

            // Make sure customer was found.
            if (! $customer->getData('entity_id')) {
                $error = 'Customer ' . $customerId . ' not found during checkout.';
                $this->_logger->error($this->_loggerPrefix . $error);

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

            return $this->_responseHelper->error('There was an error preparing your subscription, please try again.');
        }

        // Update the customer's name from the shipping address.
        try {
            // Update the customer's M2 account.
            $customer->addData([
                'firstname' => $customerShippingAddress['firstname'],
                'lastname' => $customerShippingAddress['lastname'],
            ])->save();

            // Update the customer's Gigya account.
            $gigyaData = [
                'profile' => [
                    'firstName' => $customerShippingAddress['firstname'],
                    'lastName' => $customerShippingAddress['lastname'],
                ],
            ];

            $this->_gigyaHelper->updateGigyaAccount($customer->getData('gigya_uid'), $gigyaData);
        } catch (Exception $e) {
            $this->_logger->error($this->_loggerPrefix . $e->getMessage());

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

                return $this->_responseHelper->error(
                    $error,
                    ['error_code' => 'Z1']
                );
            }

            // Get the subscription
            $this->_logger->info($this->_loggerPrefix . 'Getting the subscription object...');
            /** @var SubscriptionModel $subscription */
            $subscription = $this->_subscriptionCollectionFactory
                ->create()
                ->addFieldToFilter('quiz_id', $quiz_id)
                ->getFirstItem();

            if (! $subscription || ! $subscription->getId()) {
                $this->_response->setHttpResponseCode(404);
                $error = 'Subscription not found during checkout.';
                $this->_logger->error($this->_loggerPrefix . $error);

                return $this->_responseHelper->error($error, ['refresh' => true]);
            }
        } catch (Exception $e) {
            $this->_logger->error($this->_loggerPrefix . $e->getMessage());

            return $this->_responseHelper->error('There was an error finding your subscription information, please try again.', ['refresh' => true]);
        }

        // Add customer to subscription.
        $this->_logger->info($this->_loggerPrefix . 'Adding the customer to the subscription...');
        try {
            $subscription->setData('customer_id', $customer->getData('entity_id'));
            $subscription->setData('gigya_id', $customer->getData('gigya_uid'));
            $subscription->save();
        } catch (Exception $e) {
            $error = 'Your account could not be saved. Please try again.';
            $this->_logger->error($this->_loggerPrefix . $error . " : " . $e->getMessage());

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

                return $this->_responseHelper->error($e->getMessage(), ['refresh' => true]);
            }

            // Reload the subscription
            $this->_logger->info($this->_loggerPrefix . 'Reloading the subscription...');
            $subscription = $subscription->load($subscription->getData('entity_id'));

            // Clear the cart.
            $this->_logger->info($this->_loggerPrefix . 'Clearing the cart...');
            $this->clearCart();

            // Process the seasonal orders.
            $this->_logger->info($this->_loggerPrefix . 'Processing the seasonal orders...');
            $subscriptionOrders = $subscription->getSubscriptionOrders();

            foreach ($subscriptionOrders as $subscriptionOrder) {
                try {
                    $this->clearCustomerAddresses($customer);
                    $this->_subscriptionOrderHelper->processInvoiceWithSubscriptionId($subscriptionOrder);
                } catch (SubscriptionException $e) {
                    $this->_logger->error($this->_loggerPrefix . $e->getMessage());

                    // We failed to create orders, lets remove any created orders.
                    $this->clearCustomerAddresses($customer);
                    $this->cancelFailedOrders($subscription);

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

                    $this->_subscriptionOrderHelper->processInvoiceWithSubscriptionId($subscriptionAddonOrder);
                } catch (SubscriptionException $e) {
                    $this->_logger->error($this->_loggerPrefix . $e->getMessage());

                    // We failed to create orders, lets remove any created orders.
                    $this->clearCustomerAddresses($customer);
                    $this->cancelFailedOrders($subscription);

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
                    $subscription
                );
            } catch (LocalizedException $e) {
                $this->_logger->error($this->_loggerPrefix . $e->getMessage());

                // We failed to invoice the Recurly subscription, so lets remove any
                // created orders.
                $this->clearCustomerAddresses($customer);
                $this->cancelFailedOrders($subscription);

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
            $subscription->save();

            $this->_logger->info($this->_loggerPrefix . 'Done...');
            return $this->_responseHelper->success(
                'Subscription created.',
                [
                    'subscription_id' => $subscription->getData('subscription_id'),
                ]
            );
        } catch (Exception $e) {
            $this->_logger->error($this->_loggerPrefix . $e->getMessage());

            if (isset($subscription)) {
                $this->cancelFailedOrders($subscription);
            }

            return $this->_responseHelper->error(
                'There was an error processing your subscription, please try again.',
                ['refresh' => true],
                400
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

            $customer->save();
        } catch (Exception $e) {
            $this->_logger->error($this->_loggerPrefix . 'Could not clear addresses - ' . $e->getMessage());
        }
    }

    /**
     * Cancels orders due to a failure during checkout.
     *
     * @param SubscriptionModel $subscription
     */
    protected function cancelFailedOrders(SubscriptionModel $subscription)
    {
        $this->_logger->info($this->_loggerPrefix . 'Failed to create subscription, so let\'s cancel any orders.');

        // Get the seasonal orders.
        try {
            $seasonalOrders = $subscription->getSubscriptionOrders()->getItems();
            $addOns = $subscription->getSubscriptionAddonOrders()->getItems();

            $subscriptionOrders = array_merge($seasonalOrders, $addOns);

            foreach ($subscriptionOrders as $subscriptionOrder) {
                /* @var SubscriptionOrder | SubscriptionAddonOrder $subscriptionOrder */
                $order = $subscriptionOrder->getOrder();

                if ($order) {
                    $orderID = $order->getEntityId();

                    // Cancel the order and remove the subscription information.
                    $order->addData([
                        'master_subscription_id' => null,
                        'subscription_id' => null,
                    ])->setStatus('closed')->save();

                    $this->addOrderHistory($orderID, 'Failed to create subscription: rolling back orders.', 'closed');

                    // Mark the SAP batch records as not orders.
                    $sapOrderBatchCollection = $this->_sapOrderBatchCollectionFactory->create();
                    $sapOrderBatchCollection
                        ->addFieldToFilter('order_id', $orderID)
                        ->walk(function ($sapOrderBatch) {
                            $sapOrderBatch->setData('is_order', 0)->save();
                        });
                }

                // Update status and remove order from subscription order.
                $subscriptionOrder->addData([
                    'subscription_order_status' => 'pending',
                    'sales_order_id' => null,
                ])->save();
            }
        } catch (Exception $e) {
            $this->_logger->error($this->_loggerPrefix . 'Failed to close orders on failed order creation with message: ' . $e->getMessage());
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
}
