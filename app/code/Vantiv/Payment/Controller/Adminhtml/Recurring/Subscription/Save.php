<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Vantiv\Payment\Controller\Adminhtml\Recurring\Subscription;

use Magento\Framework\Controller\ResultFactory;

class Save extends \Vantiv\Payment\Controller\Adminhtml\Recurring\Subscription
{
    const ADMIN_RESOURCE = 'Vantiv_Payment::subscriptions_actions_edit';

    /**
     * @var \Vantiv\Payment\Controller\Recurring\FormPost
     */
    private $formPost;

    /**
     * Save constructor.
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Vantiv\Payment\Model\Recurring\SubscriptionFactory $subscriptionFactory
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     * @param \Vantiv\Payment\Controller\Recurring\FormPost $formPost
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Vantiv\Payment\Model\Recurring\SubscriptionFactory $subscriptionFactory,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Vantiv\Payment\Controller\Recurring\FormPost $formPost
    ) {
        parent::__construct($context, $subscriptionFactory, $coreRegistry, $resultPageFactory);
        $this->formPost = $formPost;
    }

    /**
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();

        if ($customerId = $this->getRequest()->getParam('customer_id')) {
            $resultRedirect->setPath('customer/index/edit', ['id' => $customerId]);
        } else {
            $resultRedirect->setPath('*/*');
        }

        $subscription = $this->initSubscription();
        if (!$subscription) {
            $this->messageManager->addErrorMessage(__('Selected subscription no longer exists.'));

            return $resultRedirect;
        }

        $data = $this->getRequest()->getParams();

        try {
            if ($data['plan_id'] != $subscription->getPlanId()) {
                $subscription->changePlan((int)$data['plan_id'], true);
            }

            if ($subscription->getBillingAddress()) {
                $this->formPost->updateBillingAddress($subscription, $data);
                if ($subscription->getBillingAddress()->hasDataChanges()) {
                    $subscription->setHasDataChanges(true);
                }
            }
            $this->formPost->updatePaymentInfo($subscription, $data);

            /* Only update if changes exist */
            if ($subscription->hasDataChanges()) {
                $subscription->save();
            }

            $this->messageManager->addSuccessMessage(__('Subscription has been saved.'));
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('Subscription could not be saved: %1', $e->getMessage()));
            $redirectParams = ['subscription_id' => $subscription->getId()];
            if ($customerId) {
                $redirectParams['customer_id'] = $customerId;
            }
            $resultRedirect->setPath('*/*/edit', $redirectParams);
        }

        return $resultRedirect;
    }
}
