<?php

namespace SMG\SubscriptionApi\Block\Adminhtml\Edit\Tab;

use Psr\Log\LoggerInterface;
use Magento\Customer\Controller\RegistryConstants;
use Magento\Ui\Component\Layout\Tabs\TabInterface;
use Recurly_Client;
use Recurly_NotFoundError;
use Recurly_SubscriptionList;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\ResourceModel\Order as OrderResource;
use SMG\SubscriptionApi\Model\ResourceModel\SubscriptionOrder as SubscriptionOrderResource;
use SMG\Sap\Model\SapOrderFactory;
use SMG\Sap\Model\ResourceModel\SapOrder as SapOrderResource;
use SMG\Sap\Model\SapOrderStatusFactory;
use SMG\Sap\Model\ResourceModel\SapOrderStatus as SapOrderStatusResource;


class CustomerSubscriptions extends \Magento\Framework\View\Element\Template implements TabInterface
{
    /**
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry;


    /**
     * @var \Magento\Customer\Model\Customer
     */
    protected $_customer;


    /**
     * @var \SMG\SubscriptionApi\Helper\RecurlyHelper
     */
    protected $_helper;


    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $_urlInterface;


    /**
     * @var \Magento\Framework\Data\Form\FormKey
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
     * @var SubscriptionOrderResource
     */
    protected $_subscriptionOrderResource;


    /**
     * @var searchCriteriaBuilder
     */
    protected $_searchCriteriaBuilder;


    /**
     * @var LoggerInterface
     */
    protected $_logger;


    /**
     * @var InvoiceRepositoryInterface
     */
    protected $_invoiceItemRepository;


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
     * @param InvoiceRepositoryInterface $_invoiceRepository
     * @param $invoiceItemRepository
     * @param $orderCollectionFactory
     * @param $sapOrderFactory
     * @param SapOrderStatusFactory $sapOrderStatusFactory
     */


    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Customer\Model\Customer $customer,
        \SMG\SubscriptionApi\Helper\RecurlyHelper $helper,
        \Magento\Framework\UrlInterface $urlInterface,
        \Magento\Framework\Data\Form\FormKey $formKey,
        OrderFactory $orderFactory,
        OrderResource $orderResource,
        SapOrderFactory $sapOrderFactory,
        SapOrderResource $SapOrderResource,
        SapOrderStatusFactory $sapOrderStatusFactory,
        SapOrderStatusResource $sapOrderStatusResource,
        SubscriptionOrderResource $subscriptionOrderResource,
        \Magento\Sales\Api\InvoiceItemRepositoryInterface $invoiceItemRepository,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory,
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
        $this->_subscriptionOrderResource = $subscriptionOrderResource;
        $this->_invoiceItemRepository = $invoiceItemRepository;
        $this->_searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->_orderCollectionFactory = $orderCollectionFactory;
        $this->_sapOrderFactory = $sapOrderFactory;
        $this->_sapOrderResource = $SapOrderResource;
        $this->_sapOrderStatusFactory = $sapOrderStatusFactory;
        $this->_sapOrderStatusResource = $sapOrderStatusResource;
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
     * Return customer's Recurly account code
     *
     * @return string|bool
     */
    public function getCustomerRecurlyAccountCode()
    {
        $customer = $this->_customer->load($this->getCustomerId());

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

        // Create empty array so we can merge active and future subscriptions
        $subscriptions = [];

        // Store refund amount
        $totalAmount = false;

        try {
            $activeSubscriptions = Recurly_SubscriptionList::getForAccount($this->getCustomerRecurlyAccountCode(), ['state' => 'active']);
            $futureSubscriptions = Recurly_SubscriptionList::getForAccount($this->getCustomerRecurlyAccountCode(), ['state' => 'future']);

            foreach ($activeSubscriptions as $subscription) {
                array_push($subscriptions, $subscription);
                $totalAmount += $subscription->unit_amount_in_cents;
            }

            foreach ($futureSubscriptions as $subscription) {
                array_push($subscriptions, $subscription);
                $totalAmount += $subscription->unit_amount_in_cents;
            }

            return ['success' => true, 'subscriptions' => $subscriptions, 'total_amount' => $this->convertAmountToDollars($totalAmount)];
        } catch (Recurly_NotFoundError $e) {
            $this->_logger->error($e->getMessage());
            return ['success' => false, 'error_message' => $e->getMessage()];
        }
    }



    /**
     * Get Invoice Order Product Name
     *
     * @param $subscription_uuid
     * @return mixed
     */
    public function getProductName($subscription_uuid)
    {
        // From Order Collection - Select all attributs based on Subscription_Id
        $collection = $this->_orderCollectionFactory->create()
            ->addAttributeToSelect('*')
            ->addFieldToFilter('subscription_id', $subscription_uuid);

        // Select Order Entity_Id from Order Collection results   
        foreach ($collection as $order) {
            $orderEntityId = $order->getData('entity_id');

            // From Invoice Item Repository - Select data set based on Order_Item_Id (Order Entity Id) 
            $invoiceOrders = $this->_invoiceItemRepository->getList(
                $this->_searchCriteriaBuilder
                    ->addFilter('order_item_id', $orderEntityId, 'eq')
                    ->create()
            );

            // From InvoiceOrders results - Return attribute 'name' 
            foreach ($invoiceOrders as $invoiceOrder) {
                return $invoiceOrder->getData('name');
            }
            return false;
        }
        return false;
    }



    /**
     * Get Product Qty from Subscription ID
     *
     * @param $subscription_uuid
     * @return mixed
     */
    public function getProductQty($subscription_uuid)
    {
        // From Order Collection - Select all attributs based on Subscription_Id
        $collection = $this->_orderCollectionFactory->create()
            ->addAttributeToSelect('*')
            ->addFieldToFilter('subscription_id', $subscription_uuid);

        // Select Order Entity_Id from Order Collection results   
        foreach ($collection as $order) {
            $orderEntityId = $order->getData('entity_id');

            // From Invoice Item Repository - Select data set based on Order_Item_Id (Order Entity Id) 
            $invoiceOrders = $this->_invoiceItemRepository->getList(
                $this->_searchCriteriaBuilder
                    ->addFilter('order_item_id', $orderEntityId, 'eq')
                    ->create()
            );

            // From InvoiceOrders results - Return attribute 'qty' 
            foreach ($invoiceOrders as $invoiceOrder) {
                return $invoiceOrder->getData('qty');
            }
            return false;
        }
        return false;
    }



    /**
     * Get Product Magento Status from Subscription ID
     *
     * @param $subscription_uuid
     * @return mixed
     */
    public function getMagentoStatus($subscription_uuid)
    {
        // From Order Collection - Select all attributs based on Subscription_Id
        $collection = $this->_orderCollectionFactory->create()
            ->addAttributeToSelect('*')
            ->addFieldToFilter('subscription_id', $subscription_uuid);

        // Select Order Status from Order Collection results   
        foreach ($collection as $order) {
            return $order->getData('status');
        }
        return false;
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
     * Get Product SKU from Subscription ID
     *
     * @param $subscription_uuid
     * @return mixed
     */
    public function getProductSku($subscription_uuid)
    {
        // From Order Collection - Select all attributs based on Subscription_Id
        $collection = $this->_orderCollectionFactory->create()
            ->addAttributeToSelect('*')
            ->addFieldToFilter('subscription_id', $subscription_uuid);

        // Select Order Entity_Id from Order Collection results   
        foreach ($collection as $order) {
            $orderEntityId = $order->getData('entity_id');

            // From Invoice Item Repository - Select data set based on Order_Item_Id (Order Entity Id) 
            $invoiceOrders = $this->_invoiceItemRepository->getList(
                $this->_searchCriteriaBuilder
                    ->addFilter('order_item_id', $orderEntityId, 'eq')
                    ->create()
            );

            // From InvoiceOrders results - Return attribute 'sku' 
            foreach ($invoiceOrders as $invoiceOrder) {
                return $invoiceOrder->getData('sku');
            }
            return false;
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

        return $orderId;
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
