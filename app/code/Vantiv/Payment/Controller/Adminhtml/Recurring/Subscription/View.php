<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Controller\Adminhtml\Recurring\Subscription;

class View extends \Vantiv\Payment\Controller\Adminhtml\Recurring\Subscription
{
    const ADMIN_RESOURCE = 'Vantiv_Payment::subscriptions_actions_view';

    /**
     * View subscription details
     *
     * @return \Magento\Backend\Model\View\Result\Page|\Magento\Backend\Model\View\Result\Redirect
     */
    public function execute()
    {
        $subscription = $this->initSubscription();

        if (!$subscription) {
            $this->messageManager->addErrorMessage(__('Selected subscription no longer exists.'));
            return $this->resultRedirectFactory->create()->setPath('*/*/');
        }

        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Vantiv_Payment::subscriptions');
        $resultPage->addBreadcrumb(__('Sales'), __('Sales'));
        $resultPage->addBreadcrumb(__('Subscriptions'), __('Subscriptions'));
        $resultPage->getConfig()->getTitle()->prepend(__('Subscriptions'));
        $resultPage->getConfig()->getTitle()->prepend(
            sprintf("Subscription #%s", $subscription->getVantivSubscriptionId())
        );
        return $resultPage;
    }
}
