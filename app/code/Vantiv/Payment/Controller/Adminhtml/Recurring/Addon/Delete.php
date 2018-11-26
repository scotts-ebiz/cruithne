<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Controller\Adminhtml\Recurring\Addon;

use Magento\Backend\App\Action;
use Magento\Framework\App\ResponseInterface;
use Vantiv\Payment\Model\Recurring\Subscription\AddonFactory;

class Delete extends \Vantiv\Payment\Controller\Adminhtml\Recurring\Addon
{
    /**
     * @var AddonFactory
     */
    private $addonFactory;

    /**
     * Delete constructor.
     *
     * @param Action\Context $context
     * @param AddonFactory $addonFactory
     */
    public function __construct(
        Action\Context $context,
        AddonFactory $addonFactory
    ) {
        parent::__construct($context);
        $this->addonFactory = $addonFactory;
    }

    /**
     * Dispatch request
     *
     * @return \Magento\Framework\Controller\ResultInterface|ResponseInterface
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();

        $subscriptionId = false;

        $addon = $this->getAddon();
        if ($addon) {
            $subscriptionId = $addon->getSubscriptionId();
            try {
                $addon->delete();
                $this->messageManager->addSuccessMessage('Add-On successfully deleted.');
            } catch (\Exception $e) {
                $this->messageManager
                    ->addErrorMessage('An error occurred while deleting the add-on. ' . $e->getMessage());
            }
        }

        return $resultRedirect->setPath('vantiv/recurring_subscription/view', ['subscription_id' => $subscriptionId]);
    }

    /**
     * Get Add-On by ID
     * @return bool|\Vantiv\Payment\Model\Recurring\Subscription\Addon
     */
    private function getAddon()
    {
        $id = $this->getRequest()->getParam('addon_id');
        if (!$id) {
            return false;
        }

        $addon = $this->addonFactory->create();
        $addon->load($id);

        if (!$addon || $addon->getId() != $id || $addon->getIsSystem()) {
            return false;
        }

        return $addon;
    }
}
