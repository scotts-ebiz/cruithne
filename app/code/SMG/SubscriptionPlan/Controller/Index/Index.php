<?php

namespace SMG\SubscriptionPlan\Controller\Index;

use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\View\Result\PageFactory;
use SMG\SubscriptionApi\Model\ResourceModel\Subscription\CollectionFactory as SubscriptionCollectionFactory;

class Index extends Action
{
    /**
     * @var PageFactory
     */
    protected $_pageFactory;

    /**
     * @var CustomerSession
     */
    protected $_customerSession;

    /**
     * @var RedirectFactory
     */
    protected $_redirectFactory;

    /**
     * @var SubscriptionCollectionFactory
     */
    protected $_subscriptionCollectionFactory;

    /**
     * Index constructor.
     *
     * @param Context $context
     * @param PageFactory $pageFactory
     * @param CustomerSession $customerSession
     * @param RedirectFactory $redirectFactory
     * @param SubscriptionCollectionFactory $subscriptionCollectionFactory
     */
    public function __construct(
        Context $context,
        PageFactory $pageFactory,
        CustomerSession $customerSession,
        RedirectFactory $redirectFactory,
        SubscriptionCollectionFactory $subscriptionCollectionFactory
    ) {
        parent::__construct($context);

        $this->_pageFactory = $pageFactory;
        $this->_customerSession = $customerSession;
        $this->_redirectFactory = $redirectFactory;
        $this->_subscriptionCollectionFactory = $subscriptionCollectionFactory;
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|\Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        // Customer is not logged in so redirect to the login page.
        if (! $this->_customerSession->isLoggedIn()) {
            $redirect = $this->_redirectFactory->create();
            $url = $this->_url->getUrl('customer/account/login');

            return $redirect->setPath($url);
        }

        if (! $this->getSubscription()) {
            $redirect = $this->_redirectFactory->create();
            $url = $this->_url->getUrl('quiz');

            return $redirect->setPath($url);
        }

        return $this->_pageFactory->create();
    }

    /**
     * Get the customer's active subscription.
     *
     * @return \Magento\Framework\DataObject|null
     */
    protected function getSubscription()
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
}
