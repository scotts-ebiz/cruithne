<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Vantiv\Payment\Controller\Adminhtml\Recurring\Subscription;

use Magento\Framework\Controller\ResultFactory;
use Vantiv\Payment\Model\Recurring\Subscription;

class Edit extends \Vantiv\Payment\Controller\Adminhtml\Recurring\Subscription
{
    const ADMIN_RESOURCE = 'Vantiv_Payment::subscriptions_actions_edit';

    /**
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $subscription = $this->initSubscription();

        if (!$subscription) {
            $this->messageManager->addErrorMessage(__('Selected subscription no longer exists.'));
            return $this->resultRedirectFactory->create()->setPath('*/*/');
        }

        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();

        $resultPage->setActiveMenu('Vantiv_Payment::subscriptions');

        $resultPage->getConfig()->getTitle()->prepend(__('Subscriptions'));
        $resultPage->getConfig()->getTitle()->prepend(
            sprintf("Subscription #%s", $subscription->getVantivSubscriptionId())
        );

        return $resultPage;
    }
}
