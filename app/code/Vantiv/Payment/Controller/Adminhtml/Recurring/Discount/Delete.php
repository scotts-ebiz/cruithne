<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Controller\Adminhtml\Recurring\Discount;

use Magento\Backend\App\Action;
use Magento\Framework\App\ResponseInterface;
use Vantiv\Payment\Model\Recurring\Subscription\DiscountFactory;

class Delete extends \Vantiv\Payment\Controller\Adminhtml\Recurring\Discount
{
    /**
     * @var DiscountFactory
     */
    private $discountFactory;

    /**
     * Delete constructor.
     *
     * @param Action\Context $context
     * @param DiscountFactory $discountFactory
     */
    public function __construct(
        Action\Context $context,
        DiscountFactory $discountFactory
    ) {
        parent::__construct($context);
        $this->discountFactory = $discountFactory;
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

        $discount = $this->getDiscount();
        if ($discount) {
            $subscriptionId = $discount->getSubscriptionId();
            try {
                $discount->delete();
                $this->messageManager->addSuccessMessage('Discount successfully deleted.');
            } catch (\Exception $e) {
                $this->messageManager
                    ->addErrorMessage('An error occurred while deleting the discount. ' . $e->getMessage());
            }
        }

        return $resultRedirect->setPath('vantiv/recurring_subscription/view', ['subscription_id' => $subscriptionId]);
    }

    /**
     * Get Discount by ID
     * @return bool|\Vantiv\Payment\Model\Recurring\Subscription\Discount
     */
    private function getDiscount()
    {
        $id = $this->getRequest()->getParam('discount_id');
        if (!$id) {
            return false;
        }

        $discount = $this->discountFactory->create();
        $discount->load($id);

        if (!$discount || $discount->getId() != $id || $discount->getIsSystem()) {
            return false;
        }

        return $discount;
    }
}
