<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Vantiv\Payment\Controller\Adminhtml\Recurring\Addon;

use Magento\Backend\App\Action;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory;
use Vantiv\Payment\Model\Recurring\Subscription\Addon;
use Vantiv\Payment\Model\Recurring\Subscription\AddonFactory;

class Edit extends \Vantiv\Payment\Controller\Adminhtml\Recurring\Addon
{
    /**
     * @var PageFactory
     */
    private $resultPageFactory;

    /**
     * @var AddonFactory
     */
    private $addonFactory;

    /**
     * @var Registry
     */
    private $registry;

    /**
     * NewAction constructor.
     *
     * @param Action\Context $context
     * @param AddonFactory $addonFactory
     * @param Registry $registry
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        Action\Context $context,
        AddonFactory $addonFactory,
        Registry $registry,
        PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->addonFactory = $addonFactory;
        $this->registry = $registry;
    }

    /**
     * Edit Subscription Add-On
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $addonId = $this->getRequest()->getParam('addon_id');
        $subscriptionId = $this->getRequest()->getParam('subscription_id');

        $addonModel = $this->addonFactory->create();

        if ($addonId) {
            $addonModel->load($addonId);
            if (!$addonModel->getId() || $addonModel->getId() != $addonId) {
                $this->messageManager->addErrorMessage(__('Add-on no longer exists.'));

                /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
                $resultRedirect = $this->resultRedirectFactory->create();

                return $resultRedirect->setPath(
                    '*/recurring_subscription/view',
                    ['subscription_id' => $subscriptionId]
                );
            }
        }

        $this->registry->register(Addon::REGISTRY_NAME, $addonModel);

        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();

        $this->initPage($resultPage)->addBreadcrumb(
            $addonId ? __('Edit Add-On') : __('New Add-On'),
            $addonId ? __('Edit Add-On') : __('New Add-On')
        );

        $resultPage->getConfig()->getTitle()->prepend(__('Add-Ons'));
        $resultPage->getConfig()->getTitle()->prepend($addonModel->getId() ? $addonModel->getName() : __('New Add-On'));

        return $resultPage;
    }
}
