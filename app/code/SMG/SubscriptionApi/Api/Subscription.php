<?php

namespace SMG\SubscriptionApi\Api;

use Magento\Customer\Model\Address;
use Magento\Customer\Model\AddressFactory;
use Magento\Customer\Model\Customer;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\SecurityViolationException;
use Magento\Framework\Webapi\Rest\Response;
use Psr\Log\LoggerInterface;
use Recurly_Client;
use Recurly_NotFoundError;
use Recurly_SubscriptionList;
use SMG\SubscriptionApi\Api\Interfaces\SubscriptionInterface;
use SMG\SubscriptionApi\Exception\SubscriptionException;
use SMG\SubscriptionApi\Helper\SubscriptionOrderHelper;

/**
 * Class Subscription
 * @package SMG\SubscriptionApi\Api
 */
class Subscription implements SubscriptionInterface
{
    /** @var LoggerInterface */
    protected $_logger;

    /** @var \SMG\RecommendationApi\Helper\RecommendationHelper */
    protected $_recommendationHelper;

    /** @var \SMG\SubscriptionApi\Helper\RecurlyHelper */
    protected $_recurlyHelper;

    /** @var \SMG\SubscriptionApi\Helper\SubscriptionHelper */
    protected $_subscriptionHelper;

    /** @var \Magento\Checkout\Model\Session */
    protected $_customerSession;

    /** @var \Magento\Framework\Data\Form\FormKey */
    protected $_formKey;

    /** @var \Magento\Checkout\Model\Cart */
    protected $_cart;

    /** @var \Magento\Catalog\Model\Product */
    protected $_product;

    /** @var \Magento\Catalog\Api\ProductRepositoryInterface */
    protected $_productRepository;

    /** @var \Magento\Checkout\Model\Session */
    protected $_checkoutSession;

    /** @var \Magento\Store\Model\StoreManagerInterface */
    protected $_storeManager;

    /** @var \Magento\Quote\Api\CartRepositoryInterface */
    protected $_cartRepositoryInterface;

    /** @var \Magento\Quote\Api\CartManagementInterface */
    protected $_cartManagementInterface;

    /** @var \Magento\Customer\Model\CustomerFactory */
    protected $_customerFactory;

    /** @var \Magento\Customer\Api\CustomerRepositoryInterface */
    protected $_customerRepository;

    /** @var \Magento\Sales\Model\Order */
    protected $_order;

    /** @var \Magento\Sales\Model\ResourceModel\Order\CollectionFactory */
    protected $_orderCollectionFactory;

    /** @var \Magento\Customer\Api\AddressRepositoryInterface */
    protected $_addressRepository;

    /**
     * @var \Magento\Customer\Model\Address
     */
    protected $_customerAddress;

    /**  @var \SMG\SubscriptionApi\Model\ResourceModel\Subscription */
    protected $_subscription;

    /** @var \SMG\SubscriptionApi\Model\ResourceModel\Subscription\CollectionFactory */
    protected $_subscriptionCollectionFactory;

    /** @var \Magento\Framework\Session\SessionManagerInterface */
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
     * Subscription constructor.
     * @param LoggerInterface $logger
     * @param \SMG\RecommendationApi\Helper\RecommendationHelper $recommendationHelper
     * @param \SMG\SubscriptionApi\Helper\RecurlyHelper $recurlyHelper
     * @param \SMG\SubscriptionApi\Helper\SubscriptionHelper $subscriptionHelper
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Framework\Data\Form\FormKey $formKey
     * @param \Magento\Checkout\Model\Cart $cart
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Catalog\Model\Product $product
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Quote\Api\CartRepositoryInterface $cartRepositoryInterface
     * @param \Magento\Quote\Api\CartManagementInterface $cartManagementInterface
     * @param \Magento\Customer\Model\CustomerFactory $customerFactory
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     * @param \Magento\Sales\Model\Order $order
     * @param \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory
     * @param \Magento\Customer\Api\AddressRepositoryInterface $addressRepository
     * @param \Magento\Customer\Model\Address $customerAddress
     * @param \SMG\SubscriptionApi\Model\ResourceModel\Subscription $subscription
     * @param \SMG\SubscriptionApi\Model\ResourceModel\Subscription\CollectionFactory $subscriptionCollectionFactory
     * @param \Magento\Framework\Session\SessionManagerInterface $coreSession
     * @param AddressFactory $addressFactory
     * @param SubscriptionOrderHelper $subscriptionOrderHelper
     * @param Response $response
     */
    public function __construct(
        LoggerInterface $logger,
        \SMG\RecommendationApi\Helper\RecommendationHelper $recommendationHelper,
        \SMG\SubscriptionApi\Helper\RecurlyHelper $recurlyHelper,
        \SMG\SubscriptionApi\Helper\SubscriptionHelper $subscriptionHelper,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\Data\Form\FormKey $formKey,
        \Magento\Checkout\Model\Cart $cart,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Catalog\Model\Product $product,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Quote\Api\CartRepositoryInterface $cartRepositoryInterface,
        \Magento\Quote\Api\CartManagementInterface $cartManagementInterface,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\Sales\Model\Order $order,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory,
        \Magento\Customer\Api\AddressRepositoryInterface $addressRepository,
        \Magento\Customer\Model\Address $customerAddress,
        \SMG\SubscriptionApi\Model\ResourceModel\Subscription $subscription,
        \SMG\SubscriptionApi\Model\ResourceModel\Subscription\CollectionFactory $subscriptionCollectionFactory,
        \Magento\Framework\Session\SessionManagerInterface $coreSession,
        AddressFactory $addressFactory,
        SubscriptionOrderHelper $subscriptionOrderHelper,
        Response $response
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
        $this->_cartRepositoryInterface = $cartRepositoryInterface;
        $this->_cartManagementInterface = $cartManagementInterface;
        $this->_customerFactory = $customerFactory;
        $this->_customerRepository = $customerRepository;
        $this->_order = $order;
        $this->_orderCollectionFactory = $orderCollectionFactory;
        $this->_recurlyHelper = $recurlyHelper;
        $this->_addressRepository = $addressRepository;
        $this->_customerAddress = $customerAddress;
        $this->_subscription = $subscription;
        $this->_subscriptionCollectionFactory = $subscriptionCollectionFactory;
        $this->_coreSession = $coreSession;
        $this->_addressFactory = $addressFactory;
        $this->_subscriptionOrderHelper = $subscriptionOrderHelper;
        $this->_response = $response;
    }

    /**
     * Process quiz data, build order object and send customer to checkout. Note that we are hijacking the cart for
     * the addition of subscriptions and to make the display easier.
     * @param string $key
     * @param string $subscription_plan
     * @param mixed $data
     * @param mixed $addons
     * @return array|false|string
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     *
     * @api
     */
    public function addSubscriptionToCart($key, $subscription_plan, $data, $addons = [])
    {
        // Test the form key
        if (! $this->formValidation($key)) {
            throw new SecurityViolationException(__('Unauthorized'));
        }

        // Add subscription to cart
        try {
            /** @var \SMG\SubscriptionApi\Model\Subscription $subscription */
            $subscription = $this->_subscription->getSubscriptionByQuizId($this->_coreSession->getQuizId());

            if ($subscription->getSubscriptionStatus() != 'pending') {
                // Subscription is already active or has been canceled, so return.
                $this->_logger->error("Subscription with quiz ID '{$subscription->getQuizId()}' cannot be added to cart since it is active or canceled.");

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
        } catch (\Exception $e) {
            $this->_logger->error($e->getMessage());
            $response = ['success' => false, 'message' => $e->getMessage()];

            return json_encode($response);
        }
    }

    /**
     * Clean out the quote
     *
     * @param string $key
     * @return false|string
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     *
     * @api
     */
    public function clean($key)
    {
        $quote = $this->_checkoutSession->getQuote();
        $quoteItems = $quote->getItemsCollection();
        foreach ($quoteItems as $item) {
            $this->_cart->removeItem($item->getItemId());
        }
        return json_encode([
            'success' => true,
            'message' => 'Clean slate.'
        ]);
    }

    /**
     * Process cart products and create multiple orders
     *
     * @param string $key
     * @param string $quiz_id
     * @param mixed $billing_address
     * @param bool $billing_same_as_shipping
     * @return array|false|string
     *
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @api
     */
    public function createOrders($key, $quiz_id, $billing_address, $billing_same_as_shipping)
    {
        // Get store and website information
        $store = $this->_storeManager->getStore();
        $websiteId = $store->getWebsiteId();

        // Get customer
        $customer = $this->_customerFactory->create();
        $customer->setWebsiteId($websiteId);
        $customer->loadByEmail($this->_checkoutSession->getQuote()->getCustomerEmail());
        $customerId = $customer->getId();
        $customer = $this->_customerFactory->create()->load($customerId);

        // Get all items in the cart
        $mainQuote = $this->_checkoutSession->getQuote();
        $quoteItems = $mainQuote->getItemsCollection();

        // Remove items from the quote, because there will be duplicate orders create
        foreach ($quoteItems as $item) {
            $this->_cart->removeItem($item->getItemId())->save();
        }

        // Get customer shipping and billing address
        $orderShippingAddress = $mainQuote->getShippingAddress()->getData();

        // Save the customer addresses.
        $this->clearCustomerAddresses($customer);

        /** @var Address $customerShippingAddress */
        $customerShippingAddress = $this->_addressFactory
            ->create()
            ->addData($orderShippingAddress)
            ->setCustomerId($customerId)
            ->save();
        $customer->setDefaultShipping($customerShippingAddress->getId());

        if ($billing_same_as_shipping) {
            $customer->setDefaultBilling($customerShippingAddress->getId());
        } else {
            /** @var Address $customerBillingAddress */
            $customerBillingAddress = $this->_addressFactory
                ->create()
                ->addData($billing_address)
                ->setCustomerId($customerId)
                ->save();
            $customer->setDefaultBilling($customerBillingAddress->getId());
        }

        $customer->save();

        // Get the subscription
        /** @var \SMG\SubscriptionApi\Model\Subscription $subscription */
        $subscription = $this->_subscriptionCollectionFactory->create()->getItemByColumnValue('quiz_id', $quiz_id);

        if (! $subscription) {
            http_response_code(404);

            return [['error' => 'Subscription not found.', 'success' => false]];
        }

        // Process the seasonal orders.
        foreach ($subscription->getSubscriptionOrders() as $subscriptionOrder) {
            try {
                $this->_subscriptionOrderHelper->processInvoiceWithSubscriptionId($subscriptionOrder);
            } catch (SubscriptionException $ex) {
                $this->_logger->error($ex->getMessage());

                return [['success' => false, 'error' => $ex->getMessage()]];
            }
        }

        // Process the add-on orders.
        foreach ($subscription->getSubscriptionAddonOrders() as $subscriptionAddonOrder) {
            try {
                if (! $subscriptionAddonOrder->getSubscriptionId()) {
                    continue;
                }

                $this->_subscriptionOrderHelper->processInvoiceWithSubscriptionId($subscriptionAddonOrder);
            } catch (SubscriptionException $ex) {
                $this->_logger->error($ex->getMessage());

                return [['success' => false, 'error' => $ex->getMessage()]];
            }
        }

        $subscription->setSubscriptionStatus('active');
        $subscription->save();

        return [
            [
                'success' => true,
                'subscription_id' => $subscription->getSubscriptionId(),
                'message' => 'Magento orders created',
            ],
        ];
    }

    /**
     * Return all customer's subscriptions
     *
     * @param string $account_code
     * @return array
     */
    private function getAccountSubscriptions($account_code, $quiz_id)
    {
        Recurly_Client::$apiKey = $this->_recurlyHelper->getRecurlyPrivateApiKey();
        Recurly_Client::$subdomain = $this->_recurlyHelper->getRecurlySubdomain();

        $activeSubscriptions = [];

        try {
            $subscriptions = Recurly_SubscriptionList::getForAccount($account_code, ['state' => 'live']);
            foreach ($subscriptions as $subscription) {
                // If subscription quiz_id is the same as the current quiz_id
                if (isset($subscription->custom_fields['quiz_id'])) {
                    if ($quiz_id == $subscription->custom_fields['quiz_id']->value) {
                        $activeSubscriptions[$subscription->plan->plan_code]['subscription_id'] = $subscription->uuid;
                        $activeSubscriptions[$subscription->plan->plan_code]['starts_at'] = $subscription->current_term_started_at;
                    }
                }
            }

            return $activeSubscriptions;
        } catch (Recurly_NotFoundError $e) {
            $this->_logger->error($e->getMessage());

            return [];
        }
    }

    /**
     * Test the form key for CSRF form validation
     *
     * @param $key
     * @return bool
     * @throws \Magento\Framework\Exception\NoSuchEntityException
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
    private function clearCustomerAddresses($customer)
    {
        $customer->setDefaultBilling(null);
        $customer->setDefaultShipping(null);

        try {
            foreach ($customer->getAddresses() as $address) {
                $this->_addressRepository->deleteById($address->getId());
            }

            $customer->cleanAllAddresses();
            $customer->save();
        } catch (NoSuchEntityException $ex) {
            $this->_logger->error($ex->getMessage());
            return;
        } catch (LocalizedException $ex) {
            $this->_logger->error($ex->getMessage());
            return;
        } catch (\Exception $ex) {
            $this->_logger->error($ex->getMessage());
            return;
        }
    }
}
