<?php

namespace SMG\SubscriptionPlan\Block;

use Magento\Customer\Model\Session as CustomerSession;
use SMG\SubscriptionApi\Model\ResourceModel\Subscription\CollectionFactory as SubscriptionCollectionFactory;

class YourPlan extends \Magento\Framework\View\Element\Template
{
    /**
     * @var CustomerSession
     */
    protected $_customerSession;

    /**
     * @var SubscriptionCollectionFactory
     */
    protected $_subscriptionCollectionFactory;

    /**
     * YourPlan constructor.
     *
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param CustomerSession $customerSession
     * @param SubscriptionCollectionFactory $subscriptionCollectionFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        CustomerSession $customerSession,
        SubscriptionCollectionFactory $subscriptionCollectionFactory,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->_customerSession = $customerSession;
        $this->_subscriptionCollectionFactory = $subscriptionCollectionFactory;
    }

    /**
     * Get the customer's active subscription.
     *
     * @return \Magento\Framework\DataObject|null
     */
    public function getSubscription()
    {
        $customerID = $this->_customerSession->getCustomerId();
        $subscriptionCollection = $this->_subscriptionCollectionFactory->create();
        $subscription = $subscriptionCollection
            ->addFilter('customer_id', $customerID)
            ->addFilter('subscription_status', 'active')
            ->addOrder('quiz_completed_at', 'desc')
            ->getFirstItem();

        if (! $subscription->getId()) {
            return null;
        }

        return $subscription;
    }

    public function getFirstName()
    {
        return $this->_customerSession->getCustomer()->getData('firstname');
    }
}
