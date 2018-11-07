<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Vantiv\Payment\Controller\Adminhtml\Recurring;

abstract class Subscription extends \Magento\Backend\App\Action
{
    /**
     * @var \Vantiv\Payment\Model\Recurring\SubscriptionFactory
     */
    protected $subscriptionFactory;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $coreRegistry;

    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $resultPageFactory;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Vantiv\Payment\Model\Recurring\SubscriptionFactory $subscriptionFactory ,
     * @param \Magento\Framework\Registry $coreRegistry ,
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Vantiv\Payment\Model\Recurring\SubscriptionFactory $subscriptionFactory,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
        $this->subscriptionFactory = $subscriptionFactory;
        $this->coreRegistry = $coreRegistry;
        $this->resultPageFactory = $resultPageFactory;
    }

    /**
     * Init subscription model
     *
     * @return bool|\Vantiv\Payment\Model\Recurring\Subscription
     */
    protected function initSubscription()
    {
        $id = $this->getRequest()->getParam('subscription_id');
        if (!$id) {
            return false;
        }

        $subscription = $this->subscriptionFactory->create();
        $subscription->load($id);

        if (!$subscription->getId()) {
            return false;
        }

        $this->coreRegistry->register(\Vantiv\Payment\Model\Recurring\Subscription::REGISTRY_NAME, $subscription);

        return $subscription;
    }
}
