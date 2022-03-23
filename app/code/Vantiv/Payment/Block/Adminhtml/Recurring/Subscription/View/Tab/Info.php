<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Block\Adminhtml\Recurring\Subscription\View\Tab;

class Info extends \Magento\Backend\Block\Widget implements \Magento\Backend\Block\Widget\Tab\TabInterface
{
    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    private $coreRegistry = null;

    /**
     * @var \Vantiv\Payment\Helper\Recurring
     */
    private $recurringHelper;

    /**
     * Constructor
     *
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Vantiv\Payment\Helper\Recurring $recurringHelper
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Vantiv\Payment\Helper\Recurring $recurringHelper,
        array $data = []
    ) {
        $this->coreRegistry = $registry;
        $this->recurringHelper = $recurringHelper;
        parent::__construct($context, $data);
    }

    /**
     * Retrieve subscription model object
     *
     * @return \Vantiv\Payment\Model\Recurring\Subscription
     */
    public function getSubscription()
    {
        if ($this->hasSubcription()) {
            return $this->getData('subscription');
        }
        return $this->coreRegistry->registry(\Vantiv\Payment\Model\Recurring\Subscription::REGISTRY_NAME);
    }

    /**
     * Get subscription store name
     *
     * @return null|string
     */
    public function getSubscriptionStoreName()
    {
        if ($this->getSubscription()) {
            $storeId = $this->getSubscription()->getStoreId();
            if ($storeId === null) {
                $deleted = __(' [deleted]');
                return nl2br($this->getSubscription()->getStoreName()) . $deleted;
            }
            $store = $this->_storeManager->getStore($storeId);
            $name = [$store->getWebsite()->getName(), $store->getGroup()->getName(), $store->getName()];
            return implode('<br/>', $name);
        }

        return null;
    }

    /**
     * Get URL to edit the customer.
     *
     * @return string
     */
    public function getCustomerViewUrl()
    {
        if (!$this->getSubscription()->getCustomerId()) {
            return '';
        }

        return $this->getUrl('customer/index/edit', ['id' => $this->getSubscription()->getCustomerId()]);
    }

    /**
     * Get object created at date affected with object store timezone
     *
     * @param mixed $store
     * @param string $createdAt
     * @return \DateTime
     */
    public function getCreatedAtStoreDate($store, $createdAt)
    {
        return $this->_localeDate->scopeDate($store, $createdAt, true);
    }

    /**
     * Get timezone for store
     *
     * @param mixed $store
     * @return string
     */
    public function getTimezoneForStore($store)
    {
        return $this->_localeDate->getConfigTimezone(
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store->getCode()
        );
    }

    /**
     * Get object created at date
     *
     * @param string $createdAt
     * @return \DateTime
     */
    public function getSubscriptionAdminDate($createdAt)
    {
        return $this->_localeDate->date(new \DateTime($createdAt));
    }

    /**
     * Retrieve subscription plan interval label
     *
     * @return string
     */
    public function getPlanIntervalLabel()
    {
        $label = '';
        if ($this->getSubscription()->getPlan()) {
            $label = $this->recurringHelper->getPlanIntervalLabel($this->getSubscription()->getPlan()->getInterval());
        }

        return $label;
    }

    /**
     * Retrieve subscription plan interval label
     *
     * @return string
     */
    public function getPlanTrialLabel()
    {
        $label = '';
        if ($this->getSubscription()->getPlan()) {
            $label = $this->recurringHelper->getPlanTrialLabel($this->getSubscription()->getPlan());
        }

        return $label;
    }

    /**
     * ######################## TAB settings #################################
     */

    /**
     * {@inheritdoc}
     */
    public function getTabLabel()
    {
        return __('Information');
    }

    /**
     * {@inheritdoc}
     */
    public function getTabTitle()
    {
        return __('Subscription Information');
    }

    /**
     * {@inheritdoc}
     */
    public function canShowTab()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function isHidden()
    {
        return false;
    }
}
