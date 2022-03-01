<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Vantiv\Payment\Controller\Adminhtml\Recurring\Discount;

use Magento\Backend\App\Action;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory;
use Vantiv\Payment\Model\Recurring\Subscription\Discount;
use Vantiv\Payment\Model\Recurring\Subscription\DiscountFactory;

class Edit extends \Vantiv\Payment\Controller\Adminhtml\Recurring\Discount
{
    /**
     * @var PageFactory
     */
    private $resultPageFactory;

    /**
     * @var DiscountFactory
     */
    private $discountFactory;

    /**
     * @var Registry
     */
    private $registry;

    /**
     * NewAction constructor.
     *
     * @param Action\Context $context
     * @param DiscountFactory $discountFactory
     * @param Registry $registry
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        Action\Context $context,
        DiscountFactory $discountFactory,
        Registry $registry,
        PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->discountFactory = $discountFactory;
        $this->registry = $registry;
    }

    /**
     * Edit Subscription Discount
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $discountId = $this->getRequest()->getParam('discount_id');
        $subscriptionId = $this->getRequest()->getParam('subscription_id');

        $discountModel = $this->discountFactory->create();

        if ($discountId) {
            $discountModel->load($discountId);
            if (!$discountModel->getId() || $discountModel->getId() != $discountId) {
                $this->messageManager->addErrorMessage(__('Discount no longer exists.'));

                /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
                $resultRedirect = $this->resultRedirectFactory->create();

                return $resultRedirect->setPath(
                    '*/recurring_subscription/view',
                    ['subscription_id' => $subscriptionId]
                );
            }
        }

        $this->registry->register(Discount::REGISTRY_NAME, $discountModel);

        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();

        $this->initPage($resultPage)->addBreadcrumb(
            $discountId ? __('Edit Discount') : __('New Discount'),
            $discountId ? __('Edit Discount') : __('New Discount')
        );

        $resultPage->getConfig()->getTitle()->prepend(__('Discounts'));
        $resultPage->getConfig()->getTitle()
            ->prepend($discountModel->getId() ? $discountModel->getName() : __('New Discount'));

        return $resultPage;
    }
}
