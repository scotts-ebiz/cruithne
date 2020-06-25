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
     * @var LoggerInterface
     */
    protected $_logger;

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Customer\Model\Customer $customer,
        \SMG\SubscriptionApi\Helper\RecurlyHelper $helper,
        \Magento\Framework\UrlInterface $urlInterface,
        \Magento\Framework\Data\Form\FormKey $formKey,
        OrderFactory $orderFactory,
        OrderResource $orderResource,
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
        $this->_logger = $logger;
        parent::__construct($context, $data);
    }

    /**
     * Return form key
     *
     * @return bool
     */
    public function getSubscriptionOrderEntityIdTest()
    {
        $testing = 'This is asdf';

        return $testing;
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
     * Get order by Entity Id on Order Table
     *
     * @param $subscriptionId
     * @return mixed
     */
    public function getOrderEntityId($subscriptionId)
    {
        $orderModel = $this->_orderFactory->create();
        $this->_orderResource->load($orderModel, $subscriptionId, 'subscription_id');
        $orderEntityId = $orderModel->getData('entity_id');

        return $orderEntityId;
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
