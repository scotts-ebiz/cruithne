<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Controller\Adminhtml\Recurring\Subscription;

use Vantiv\Payment\Model\Recurring\Source\SubscriptionStatus;

class Cancel extends \Vantiv\Payment\Controller\Adminhtml\Recurring\Subscription
{
    const ADMIN_RESOURCE = 'Vantiv_Payment::subscriptions_actions_edit';

    /**
     * Execute action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();

        $subscription = $this->initSubscription();
        if ($subscription) {
            try {
                if ($subscription->getStatus() != SubscriptionStatus::CANCELLED) {
                    $subscription->setStatus(SubscriptionStatus::CANCELLED)
                        ->save();
                    $this->messageManager->addSuccessMessage(__('You\'ve successfully canceled the subscription.'));
                }
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            }
        } else {
            $this->messageManager->addError(__('We can\'t find a subscription to cancel.'));
        }

        if ($this->getRequest()->isAjax()) {
            /** @var \Magento\Framework\Controller\Result\Json $resultJson */
            $resultJson = $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_JSON);
            return $resultJson;
        }

        if ($this->getRequest()->getParam('from_grid')) {
            return $resultRedirect->setPath('*/*/');
        } else {
            return $resultRedirect->setPath('*/*/view', ['_current' => true]);
        }
    }
}
