<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Block\Adminhtml\Recurring\Subscription;

class View extends \Magento\Backend\Block\Widget\Form\Container
{
    /**
     * Block group
     *
     * @var string
     */
    protected $_blockGroup = 'Vantiv_Payment';

    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    private $coreRegistry = null;

    /**
     * @param \Magento\Backend\Block\Widget\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Widget\Context $context,
        \Magento\Framework\Registry $registry,
        array $data = []
    ) {
        $this->coreRegistry = $registry;
        parent::__construct($context, $data);
    }

    /**
     * Constructor
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_objectId = 'subscription_id';
        $this->_controller = 'adminhtml_recurring_subscription';
        $this->_mode = 'view';

        parent::_construct();

        $this->buttonList->remove('delete');
        $this->buttonList->remove('reset');
        $this->buttonList->remove('save');
        $this->setId('vantiv_recurring_subscription_view');
        $subscription = $this->getSubscription();

        if (!$subscription) {
            return;
        }

        if ($this->_isAllowedAction('Vantiv_Payment::subscriptions_actions_edit')
            && $subscription->getStatus() != \Vantiv\Payment\Model\Recurring\Source\SubscriptionStatus::CANCELLED
        ) {
            $message = __('Are you sure you want to cancel subscription?');
            $this->buttonList->add(
                'subscription_cancel',
                [
                    'label' => __('Cancel'),
                    'class' => 'cancel',
                    'onclick' => "confirmSetLocation('{$message}', '{$this->getCancelUrl()}')"
                ]
            );

            $this->buttonList->add(
                'subscription_add_on',
                [
                    'label' => __('New Add-On'),
                    'class' => 'action-secondary',
                    'onclick' => "setLocation('" . $this->getNewAddonUrl() . "')",
                ]
            );

            $this->buttonList->add(
                'subscription_discount',
                [
                    'label' => __('New Discount'),
                    'class' => 'action-secondary',
                    'onclick' => "setLocation('" . $this->getNewDiscountUrl() . "')",
                ]
            );
        }
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
     * Retrieve subscription id
     *
     * @return int
     */
    public function getSubscriptionId()
    {
        return $this->getSubscription() ? $this->getSubscription()->getId() : null;
    }

    /**
     * Get header text
     *
     * @return \Magento\Framework\Phrase
     */
    public function getHeaderText()
    {
        return __(
            'Subscription #%1',
            $this->getSubscription() ? $this->getSubscription()->getVantivSubscriptionId() : ''
        );
    }

    /**
     * URL getter
     *
     * @param string $params
     * @param array $params2
     * @return string
     */
    public function getUrl($params = '', $params2 = [])
    {
        $params2['subscription_id'] = $this->getSubscriptionId();
        if (!isset($params2['referrer']) && $this->getRequest()->getParam('referrer')) {
            $params2['referrer'] = $this->getRequest()->getParam('referrer');
        }
        return parent::getUrl($params, $params2);
    }

    /**
     * Cancel URL getter
     *
     * @return string
     */
    public function getCancelUrl()
    {
        return $this->getUrl('*/*/cancel');
    }

    /**
     * Get URL of New Add-On Controller
     *
     * @return string
     */
    public function getNewAddonUrl()
    {
        return $this->getUrl('*/recurring_addon/new');
    }

    /**
     * Get URL of New Discount Controller
     *
     * @return string
     */
    public function getNewDiscountUrl()
    {
        return $this->getUrl('*/recurring_discount/new');
    }

    /**
     * Check permission for passed action
     *
     * @param string $resourceId
     * @return bool
     */
    protected function _isAllowedAction($resourceId)
    {
        return $this->_authorization->isAllowed($resourceId);
    }

    /**
     * Return back url for view grid
     *
     * @return string
     */
    public function getBackUrl()
    {
        switch ($this->getRequest()->getParam('referrer')) {
            case 'customer':
                $subscription = $this->getSubscription();
                return $subscription ? parent::getUrl('customer/index/edit', ['id' => $subscription->getCustomerId()])
                    : parent::getUrl('*/*/');
            default:
                return parent::getUrl('*/*/');
        }
    }
}
