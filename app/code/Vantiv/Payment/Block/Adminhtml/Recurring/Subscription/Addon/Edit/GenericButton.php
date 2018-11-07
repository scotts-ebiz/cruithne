<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Vantiv\Payment\Block\Adminhtml\Recurring\Subscription\Addon\Edit;

use Magento\Backend\Block\Widget\Context;
use Magento\Framework\Registry;
use Vantiv\Payment\Model\Recurring\Subscription\Addon;
use Vantiv\Payment\Model\Recurring\Subscription\AddonFactory;

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
    public function getAddonId()
    {
        $addon = $this->registry->registry(Addon::REGISTRY_NAME);

        if ($addon && $addon->getId()) {
            return $addon->getId();
        }

        return null;
    }

    /**
     * @return int|null
     */
    public function getSubscriptionId()
    {
        /** @var \Vantiv\Payment\Model\Recurring\Subscription\Addon $addon */
        $addon = $this->registry->registry(Addon::REGISTRY_NAME);

        if ($addon && $addon->getId()) {
            return $addon->getSubscriptionId();
        } elseif ($subscriptionId = $this->context->getRequest()->getParam('subscription_id')) {
            return $subscriptionId;
        }

        return null;
    }
}
