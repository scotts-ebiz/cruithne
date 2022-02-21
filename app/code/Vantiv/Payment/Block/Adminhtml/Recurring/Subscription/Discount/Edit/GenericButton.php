<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Vantiv\Payment\Block\Adminhtml\Recurring\Subscription\Discount\Edit;

use Magento\Backend\Block\Widget\Context;
use Magento\Framework\Registry;
use Vantiv\Payment\Model\Recurring\Subscription\Discount;
use Vantiv\Payment\Model\Recurring\Subscription\DiscountFactory;

class GenericButton
{
    /**
     * @var Context
     */
    private $context;
    /**
     * @var Registry
     */
    private $registry;

    /**
     * GenericButton constructor.
     *
     * @param Context $context
     * @param Registry $registry
     */
    public function __construct(
        Context $context,
        Registry $registry
    ) {
        $this->context = $context;
        $this->registry = $registry;
    }

    /**
     * Generate url by route and parameters
     *
     * @param   string $route
     * @param   array $params
     * @return  string
     */
    public function getUrl($route = '', $params = [])
    {
        return $this->context->getUrlBuilder()->getUrl($route, $params);
    }

    /**
     * @return int|null
     */
    public function getDiscountId()
    {
        $discount = $this->registry->registry(Discount::REGISTRY_NAME);

        if ($discount && $discount->getId()) {
            return $discount->getId();
        }

        return null;
    }

    /**
     * @return int|null
     */
    public function getSubscriptionId()
    {
        /** @var \Vantiv\Payment\Model\Recurring\Subscription\Discount $discount */
        $discount = $this->registry->registry(Discount::REGISTRY_NAME);

        if ($discount && $discount->getId()) {
            return $discount->getSubscriptionId();
        } elseif ($subscriptionId = $this->context->getRequest()->getParam('subscription_id')) {
            return $subscriptionId;
        }

        return null;
    }
}
