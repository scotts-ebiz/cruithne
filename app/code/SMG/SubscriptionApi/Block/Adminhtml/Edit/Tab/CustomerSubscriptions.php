<?php

namespace SMG\SubscriptionApi\Block\Adminhtml\Edit\Tab;

use Magento\Customer\Model\Customer;
use Magento\Framework\Data\Form\FormKey;
use Magento\Framework\Registry;
use Magento\Framework\UrlInterface;
use Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory;
use Psr\Log\LoggerInterface;
use Magento\Customer\Controller\RegistryConstants;
use Magento\Ui\Component\Layout\Tabs\TabInterface;
use Recurly_Client;
use Recurly_NotFoundError;
use Recurly_SubscriptionList;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\ResourceModel\Order as OrderResource;
use SMG\SubscriptionApi\Helper\RecurlyHelper;
use SMG\SubscriptionApi\Model\ResourceModel\Subscription;
use SMG\SubscriptionApi\Model\ResourceModel\Subscription\CollectionFactory as SubscriptionCollectionFactory;
use SMG\Sap\Model\SapOrderFactory;
use SMG\Sap\Model\ResourceModel\SapOrder as SapOrderResource;
use SMG\Sap\Model\SapOrderStatusFactory;
use SMG\Sap\Model\ResourceModel\SapOrderStatus as SapOrderStatusResource;
use Recurly_Subscription;

class CustomerSubscriptions extends \Magento\Framework\View\Element\Template implements TabInterface
{
    /**
     * @var Registry
     */
    protected $_coreRegistry;


    /**
     * @var Customer
     */
    protected $_customer;

    /**
     * @var RecurlyHelper
     */
    protected $_helper;

    /**
     * @var UrlInterface
     */
    protected $_urlInterface;

    /**
     * @var FormKey
     */
    protected $_formKey;

    /**
     * @var OrderFactory
     */
    protected $_orderFactory;

    /**
     * @var OrderResource
     */
    protected $_orderResource;

    /**
     * @var SubscriptionCollectionFactory
     */
    protected $_subscriptionCollectionFactory;

    /**
     * @var searchCriteriaBuilder
     */
    protected $_searchCriteriaBuilder;

    /**
     * @var LoggerInterface
     */
    protected $_logger;

    /**
     * @var orderCollectionFactory
     */
    protected $_orderCollectionFactory;

    /**
     * @var SapOrderFactory
     */
    protected $_sapOrderFactory;

    /**
     * @var SapOrderResource
     */
    protected $_sapOrderResource;

    /**
     * @var SapOrderStatusFactory
     */
    protected $_sapOrderStatusFactory;

    /**
     * @var SapOrderStatusResource
     */
    protected $_sapOrderStatusResource;

    /**
     * @var CollectionFactory
     */
    protected $_orderItemCollectionFactory;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param Registry $registry
     * @param Customer $customer
     * @param RecurlyHelper $helper
     * @param UrlInterface $urlInterface
     * @param FormKey $formKey
     * @param OrderFactory $orderFactory
     * @param OrderResource $orderResource
     * @param SapOrderFactory $sapOrderFactory
     * @param SapOrderResource $SapOrderResource
     * @param SapOrderStatusFactory $sapOrderStatusFactory
     * @param SapOrderStatusResource $sapOrderStatusResource
     * @param SubscriptionOrderResource $subscriptionOrderResource
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     * @param OrderResource\CollectionFactory $orderCollectionFactory
     * @param CollectionFactory $orderItemCollectionFactory
     * @param LoggerInterface $logger
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        Registry $registry,
        Customer $customer,
        RecurlyHelper $helper,
        UrlInterface $urlInterface,
        FormKey $formKey,
        OrderFactory $orderFactory,
        OrderResource $orderResource,
        SapOrderFactory $sapOrderFactory,
        SapOrderResource $SapOrderResource,
        SapOrderStatusFactory $sapOrderStatusFactory,
        SapOrderStatusResource $sapOrderStatusResource,
        SubscriptionCollectionFactory $subscriptionCollectionFactory,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory,
        CollectionFactory $orderItemCollectionFactory,
        LoggerInterface $logger,
        array $data = []
    ) {
        $this->_coreRegistry = $registry;
        $this->_customer = $customer;
        $this->_helper = $helper;
        $this->_urlInterface = $urlInterface;
        $this->_formKey = $formKey;
        $this->_orderFactory = $orderFactory;
        $this->_orderResource = $orderResource;
        $this->_subscriptionCollectionFactory = $subscriptionCollectionFactory;
        $this->_searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->_orderCollectionFactory = $orderCollectionFactory;
        $this->_sapOrderFactory = $sapOrderFactory;
        $this->_sapOrderResource = $SapOrderResource;
        $this->_sapOrderStatusFactory = $sapOrderStatusFactory;
        $this->_sapOrderStatusResource = $sapOrderStatusResource;
        $this->_orderItemCollectionFactory = $orderItemCollectionFactory;
        $this->_logger = $logger;
        parent::__construct($context, $data);
    }

    /**
     * Return form key
     *
     * @return string
     */
    public function getFormKey()
    {
        return $this->_formKey->getFormKey();
    }

    /**
     * Return customer id
     *
     * @return string
     */
    public function getCustomerId()
    {
        return $this->_coreRegistry->registry(RegistryConstants::CURRENT_CUSTOMER_ID);
    }

    /**
     * Return customer id
     *
     * @return Customer
     */
    public function getCustomer()
    {
        return $this->_customer->load($this->getCustomerId());
    }

    /**
     * Return customer's Recurly account code
     *
     * @param Customer $customer
     * @return string|bool
     */
    public function getCustomerRecurlyAccountCode(Customer $customer)
    {

        if ($customer->getGigyaUid()) {
            return $customer->getGigyaUid();
        }

        $this->_logger->error('Could not find customer Gigya ID');
        return false;
    }

    /**
     * Return active and future subscriptions of the customer
     *
     * @return array
     * @throws Recurly_NotFoundError if Recurly account doesn't exist
     */
    public function getCustomerSubscriptions()
    {
        Recurly_Client::$apiKey = $this->_helper->getRecurlyPrivateApiKey();
        Recurly_Client::$subdomain = $this->_helper->getRecurlySubdomain();

        // Create empty array so we can merge active and future active parent subscriptions
        $recurlySubscriptions = [];
        $recurlyChildSubscriptions = [];

        try {
            $customerSubscriptionInfo = [];
            $customer = $this->getCustomer();
            $customerSubscriptionInfo['customer'] = $customer->getData();
            $activeSubscriptions = Recurly_SubscriptionList::getForAccount($this->getCustomerRecurlyAccountCode($customer), ['state' => 'active']);
            $futureSubscriptions = Recurly_SubscriptionList::getForAccount($this->getCustomerRecurlyAccountCode($customer), ['state' => 'future']);

            /** @var Recurly_Subscription $subscription */
            foreach ($activeSubscriptions as $subscription) {
                if ($subscription->plan->name === 'Annual' || $subscription->plan->name === 'Seasonal') {
                    $recurlySubscriptions[] =  $subscription;
                } else {
                    $recurlyChildSubscriptions[$subscription->uuid] = $subscription;
                }
            }

            /** @var Recurly_Subscription $subscription */
            foreach ($futureSubscriptions as $subscription) {
                if ($subscription->plan->name === 'Annual' || $subscription->plan->name === 'Seasonal') {
                    $recurlySubscriptions[] =  $subscription;
                } else {
                    $recurlyChildSubscriptions[$subscription->uuid] = $subscription;
                }
            }

            // Active and past renewed subscription list with all info
            $parentActiveSubscriptions = [];
            $parentRenewedSubscriptions = [];

            // Should only have one! but if the data is bad let's show the bad data
            foreach ($recurlySubscriptions as $recurlySubscription) {

                $renewedSubscriptions = $this->_subscriptionCollectionFactory->create()
                    ->addFieldToFilter('subscription_id', $recurlySubscription->uuid)
                    ->addFieldToFilter('subscription_status', array('eq' => 'renewed'));

                $activeSubscriptions = $this->_subscriptionCollectionFactory->create()
                    ->addFieldToFilter('subscription_id', $recurlySubscription->uuid)
                    ->addFieldToFilter('subscription_status', array('eq' => 'active'));

                /** @var Subscription $renewedSubscription */
                foreach ($renewedSubscriptions as $subscription) {
                    $renewedSubscription = [];
                    $renewedSubscription['recurlySubscription'] = $recurlySubscription;
                    $renewedSubscription['parentSubscription'] = $subscription->getData();
                    $subscriptionOrders = [];

                    foreach ($subscription->getSubscriptionOrders() as $subscriptionOrder) {
                        $subscriptionOrderItem = [];
                        $subscriptionId = $subscriptionOrder->getData('subscription_id');
                        $subscriptionOrderItem['recurlySubscription'] = $recurlyChildSubscriptions[$subscriptionId];
                        $subscriptionOrderItem['subscriptionOrder'] = $subscriptionOrder->getData();
                        $salesOrder = $this->getOrderBySubscriptionId($subscriptionId);
                        $subscriptionOrderItem['salesOrder'] = $salesOrder->getData();
                        $subscriptionOrderItem['product'] = array_first($salesOrder->getItems())->getData();
                        $subscriptionOrders[] = $subscriptionOrderItem;
                    }

                    $renewedSubscription['subscriptionOrders'] = $subscriptionOrders;
                    $parentRenewedSubscriptions[] = $renewedSubscription;
                }

                foreach ($activeSubscriptions as $subscription) {
                    $activeSubscription = [];
                    $activeSubscription['recurlySubscription'] = $recurlySubscription;
                    $activeSubscription['parentSubscription'] = $subscription->getData();
                    $subscriptionOrders = [];

                    foreach ($subscription->getSubscriptionOrders() as $subscriptionOrder) {
                        $subscriptionOrderItem = [];
                        $subscriptionId = $subscriptionOrder->getData('subscription_id');
                        $subscriptionOrderItem['recurlySubscription'] = $recurlyChildSubscriptions[$subscriptionId];
                        $subscriptionOrderItem['subscriptionOrder'] = $subscriptionOrder->getData();
                        $salesOrder = $this->getOrderBySubscriptionId($subscriptionId);
                        $subscriptionOrderItem['salesOrder'] = $salesOrder->getData();
                        $subscriptionOrderItem['product'] = array_first($salesOrder->getItems())->getData();
                        $subscriptionOrders[] = $subscriptionOrderItem;
                    }

                    $activeSubscription['subscriptionOrders'] = $subscriptionOrders;
                    $parentActiveSubscriptions[] = $activeSubscription;
                }
            }

            $customerSubscriptionInfo['activeSubscriptions'] = $parentActiveSubscriptions;
            $customerSubscriptionInfo['renewedSubscriptions'] = $parentRenewedSubscriptions;

            return ['success' => true, 'subscriptionInfo' => $customerSubscriptionInfo];
        } catch (Recurly_NotFoundError $e) {
            $this->_logger->error($e->getMessage());
            return ['success' => false, 'error_message' => $e->getMessage()];
        }
    }

    /**
     * Get SAP Subscription Order Status from Subscription ID
     *
     * @param $subscription_uuid
     * @return mixed
     */
    public function getSapOrderStatus($subscription_uuid)
    {
        // From Order Collection - Select all attributs based on Subscription_Id
        $collection = $this->_orderCollectionFactory->create()
            ->addAttributeToSelect('*')
            ->addFieldToFilter('subscription_id', $subscription_uuid);

        // Create instance of SAP Order Factory
        $sapOrderObject = $this->_sapOrderFactory->create();

        // Create instance of SAP Order Status Factory
        $sapOrderStatusObject = $this->_sapOrderStatusFactory->create();

        // Select Order Entity_Id from Order Collection results
        foreach ($collection as $order) {
            $orderEntityId = $order->getData('entity_id');

            // Load SAP Order object based on order_id
            $this->_sapOrderResource->load($sapOrderObject, $orderEntityId, 'order_id');

            // Retrieve order_status from SAP Order Object
            $sapOrderStatus = $sapOrderObject->getData('order_status');

            // Load SAP Order Status object based on status
            $this->_sapOrderStatusResource->load($sapOrderStatusObject, $sapOrderStatus, 'status');

            // Retrieve clean version of the SAP Status label from SAP Order Status object
            $sapOrderStatusLabel = $sapOrderStatusObject->getData('label');

            return $sapOrderStatusLabel;
        }
        return false;
    }


    /**
     * Get order by subscription Id
     *
     * @param $subscriptionId
     * @return mixed
     */
    public function getOrderBySubscriptionId($subscriptionId)
    {
        $order = $this->_orderFactory->create();
        $this->_orderResource->load($order, $subscriptionId, 'subscription_id');
        $orderId = $order->getId();

        if (!$orderId) {
            $this->_logger->error("Could not find an order for subscription with ID: {$subscriptionId}");
        }

        return $order;
    }

    /**
     * Get Admin URL path
     *
     * @return array
     */
    public function getAdminURLPath()
    {
        $urlParts = parse_url($this->getRequest()->getUriString());
        $path = explode('/', $urlParts['path']);
        return $path[1];
    }

    /**
     * Convert cents to dollars
     *
     */
    public function convertAmountToDollars($amount)
    {
        return number_format(($amount / 100), 2, '.', ' ');
    }

    public function getCancelUrl()
    {
        echo $this->_urlInterface->getUrl('customersubscriptions/cancel/index');
    }

    public function getTabLabel()
    {
        return 'Scott\'s Subscriptions';
    }

    public function getTabTitle()
    {
        return 'Scott\'s Subscriptions';
    }

    public function canShowTab()
    {
        if ($this->getCustomerId()) {
            return true;
        }
        return false;
    }

    public function isHidden()
    {
        if ($this->getCustomerId()) {
            return false;
        }
        return true;
    }

    public function getTabClass()
    {
        return '';
    }

    public function getTabUrl()
    {
        return $this->getUrl('customersubscriptions/*/customersubscriptions', ['_current' => true]);
    }

    public function isAjaxLoaded()
    {
        return true;
    }
}
