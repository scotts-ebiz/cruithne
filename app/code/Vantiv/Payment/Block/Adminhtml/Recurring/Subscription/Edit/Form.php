<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Vantiv\Payment\Block\Adminhtml\Recurring\Subscription\Edit;

use Vantiv\Payment\Model\Recurring\Subscription;

class Form extends \Magento\Backend\Block\Widget\Form\Generic
{
    /**
     * @var \Magento\Directory\Model\Config\Source\Country
     */
    private $country;

    /**
     * @var \Magento\Framework\Locale\CurrencyInterface
     */
    private $currencyInterface;

    /**
     * @var \Magento\Vault\Api\PaymentTokenManagementInterface
     */
    private $tokenManager;

    /**
     * @var \Vantiv\Payment\Model\Ui\CcConfigProvider
     */
    private $ccConfigProvider;

    /**
     * @var \Vantiv\Payment\Model\ResourceModel\Recurring\Plan\CollectionFactory
     */
    private $planCollectionFactory;

    /**
     * @var \Vantiv\Payment\Helper\Recurring
     */
    private $recurringHelper;

    /**
     * @var \Magento\Directory\Model\RegionFactory
     */
    private $regionFactory;

    /**
     * Form constructor.
     *
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param \Magento\Directory\Model\Config\Source\Country $country
     * @param \Magento\Directory\Model\RegionFactory $regionFactory
     * @param \Magento\Framework\Locale\CurrencyInterface $currencyInterface
     * @param \Magento\Vault\Api\PaymentTokenManagementInterface $tokenManager
     * @param \Vantiv\Payment\Model\Ui\CcConfigProvider $ccConfigProvider
     * @param \Vantiv\Payment\Model\ResourceModel\Recurring\Plan\CollectionFactory $planCollectionFactory
     * @param \Vantiv\Payment\Helper\Recurring $recurringHelper
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\Directory\Model\Config\Source\Country $country,
        \Magento\Directory\Model\RegionFactory $regionFactory,
        \Magento\Framework\Locale\CurrencyInterface $currencyInterface,
        \Magento\Vault\Api\PaymentTokenManagementInterface $tokenManager,
        \Vantiv\Payment\Model\Ui\CcConfigProvider $ccConfigProvider,
        \Vantiv\Payment\Model\ResourceModel\Recurring\Plan\CollectionFactory $planCollectionFactory,
        \Vantiv\Payment\Helper\Recurring $recurringHelper,
        array $data = []
    ) {
        $this->country = $country;
        $this->regionFactory = $regionFactory;
        $this->currencyInterface = $currencyInterface;
        $this->tokenManager = $tokenManager;
        $this->ccConfigProvider = $ccConfigProvider;
        $this->planCollectionFactory = $planCollectionFactory;
        $this->recurringHelper = $recurringHelper;

        parent::__construct($context, $registry, $formFactory, $data);
    }

    /**
     * @inheritdoc
     */
    protected function _prepareForm()
    {
        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create([
            'data' => [
                'id'     => 'edit_form',
                'method' => 'post',
                'action' => $this->getUrl('vantiv/recurring_subscription/save'),
            ],
        ]);

        /** @var Subscription $subscription */
        $subscription = $this->getSubscription();

        $fieldset = $form->addFieldset('base_fieldset', ['legend' => $subscription->getProductName()]);

        $fieldset->addField(
            'subscription_id',
            'hidden',
            [
                'name'  => 'subscription_id',
                'value' => $subscription->getId(),
            ]
        );

        if ($customerId = $this->getRequest()->getParam('customer_id')) {
            $fieldset->addField(
                'customer_id',
                'hidden',
                [
                    'name' => 'customer_id',
                    'value' => $customerId,
                ]
            );
        }

        if ($planOptions = $this->getPlanOptions()) {
            if ($subscription->getStatus() != \Vantiv\Payment\Model\Recurring\Source\SubscriptionStatus::CANCELLED) {
                $fieldset->addField(
                    'plan_id',
                    'select',
                    [
                        'name'   => 'plan_id',
                        'label'  => __('Payment Plan'),
                        'values' => $planOptions,
                        'value'  => $subscription->getPlanId(),
                        'note'   => $subscription->getDiscountCollection()->getSize()
                            ? __('If plan changed, all existing subscription discounts will be automatically removed')
                            : ''
                    ]
                );
            }
        }

        $billingFieldset = $fieldset->addFieldset('billing_information', ['legend' => __('Billing Information')]);

        $billingFieldset->addField(
            'firstname',
            'text',
            [
                'name'     => 'firstname',
                'label'    => __('First Name'),
                'required' => true,
                'value'    => $subscription->getBillingAddress()->getFirstname(),
                'class'    => 'validate-length maximum-length-25',
            ]
        );

        $billingFieldset->addField(
            'lastname',
            'text',
            [
                'name'     => 'lastname',
                'label'    => __('Last Name'),
                'required' => true,
                'value'    => $subscription->getBillingAddress()->getLastname(),
                'class'    => 'validate-length maximum-length-25',
            ]
        );

        $billingFieldset->addField(
            'street',
            'text',
            [
                'name'     => 'street',
                'label'    => __('Street Address'),
                'required' => true,
                'value'    => $subscription->getBillingAddress()->getStreet(),
                'class'    => 'validate-length maximum-length-35',
            ]
        );

        $billingFieldset->addField(
            'city',
            'text',
            [
                'name'     => 'city',
                'label'    => __('City'),
                'required' => true,
                'value'    => $subscription->getBillingAddress()->getCity(),
                'class'    => 'validate-length maximum-length-35',
            ]
        );

        $regionCollection = $this->regionFactory->create()->getCollection()->addCountryFilter(
            $subscription->getBillingAddress()->getCountryId()
        );

        $regions = $regionCollection->toOptionArray();
        unset($regions[0]);

        $billingFieldset->addField(
            'region_id',
            'select',
            [
                'name'   => 'region_id',
                'label'  => __('State'),
                'value'  => $subscription->getBillingAddress()->getRegionId(),
                'values' => $regions,
            ]
        );

        $billingFieldset->addField(
            'postcode',
            'text',
            [
                'name'     => 'postcode',
                'label'    => __('Zip/Postal Code'),
                'required' => true,
                'value'    => $subscription->getBillingAddress()->getPostcode(),
                'class'    => 'validate-length maximum-length-20',
            ]
        );

        $countries = $this->country->toOptionArray(true, $subscription->getBillingAddress()->getCountryId());

        $billingFieldset->addField(
            'country_id',
            'select',
            [
                'name'     => 'country_id',
                'label'    => __('Country'),
                'required' => true,
                'values'   => $countries,
                'value'    => $subscription->getBillingAddress()->getCountryId(),
            ]
        );

        if ($subscription->getStatus() != \Vantiv\Payment\Model\Recurring\Source\SubscriptionStatus::CANCELLED) {
            $paymentFieldset = $fieldset->addFieldset('payment_information', ['legend' => __('Payment Information')]);

            $paymentFieldset->addField(
                'vantiv_subscription_payment',
                'select',
                [
                    'name'               => 'vantiv_subscription_payment',
                    'label'              => 'Payment Method',
                    'values'             => $this->getPaymentOptions(),
                    'after_element_html' => $this->isVantivCcAvailable()
                        ? '<div id="payframe" data-mage-init=\'' . $this->getCcFormMageInitJson() . '\'></div>'
                        : '',
                ]
            );

            $paymentFieldset->addField(
                'vantiv-paypage-registration-id',
                'hidden',
                [
                    'name' => 'paypage_registration_id',
                ]
            );

            $paymentFieldset->addField(
                'vantiv-cc-type',
                'hidden',
                [
                    'name' => 'cc_type',
                ]
            );
        }

        $form->setUseContainer(true);
        $form->setMethod('post');

        $formData = ['subscription_id' => $this->getRequest()->getParam('subscription_id')];

        $this->setForm($form);

        return parent::_prepareForm();
    }

    /**
     * @return null|Subscription
     */
    public function getSubscription()
    {
        return $this->_coreRegistry->registry(Subscription::REGISTRY_NAME);
    }

    /**
     * Get formatted string of plan options
     *
     * @return array
     */
    public function getPlanOptions()
    {
        $planOptions = [];

        if ($this->getSubscription()->getProductId()) {
            /** @var \Magento\Store\Model\Store $store */
            $store = $this->getSubscription()->getStore();

            $currencyCode = $store->getBaseCurrencyCode();
            $currency = $this->currencyInterface->getCurrency($currencyCode);

            /** @var \Vantiv\Payment\Model\ResourceModel\Recurring\Plan\Collection $planCollection */
            $planCollection = $this->planCollectionFactory->create();
            $planCollection->addProductIdFilter($this->getSubscription()->getProductId())
                ->addWebsiteFilter($this->getSubscription()->getStore()->getWebsiteId())
                ->addActiveFilter();

            /** @var \Vantiv\Payment\Model\Recurring\Plan $plan */
            foreach ($planCollection as $plan) {
                $amount = $currency->toCurrency($plan->getIntervalAmount());
                $planOptions[$plan->getId()] = $this->recurringHelper->buildPlanOptionTitle($plan, $amount);
            }
        }

        return $planOptions;
    }

    /**
     * Get list of valid payment options
     *
     * @return array
     */
    public function getPaymentOptions()
    {
        $paymentOptions = [];

        $paymentOptions['-1'] = __('Do not change payment information');

        $savedPayments = $this->getSavedPayments();
        foreach ($savedPayments as $token) {
            $details = json_decode($token->getTokenDetails(), true);

            $paymentOptions[$token->getId()] = __('Existing ')
                . $details['ccType'] . ' ending in ' . $details['ccLast4'];
        }

        if ($this->isVantivCcAvailable()) {
            $paymentOptions['-2'] = __('Use new credit card');
        }

        return $paymentOptions;
    }

    /**
     * @return array
     */
    public function getSavedPayments()
    {
        $savedPayments = [];
        $validPaymentMethods = [];
        $allPaymentMethods = $this->getPaymentMethods();
        foreach ($allPaymentMethods as $code => $method) {
            if ($this->isMethodAvailable($method) && $this->isMethodActive($allPaymentMethods[$code . '_vault'])) {
                $validPaymentMethods[] = $code;
            }
        }

        $tokenList = $this->tokenManager->getListByCustomerId($this->getSubscription()->getCustomerId());

        /** @var \Magento\Vault\Model\PaymentToken $tokenData */
        foreach ($tokenList as $tokenData) {
            /** @var \Magento\Vault\Model\PaymentToken $token */
            $token = $this->tokenManager->getByPublicHash($tokenData->getPublicHash(), $tokenData->getCustomerId());

            if (in_array($token->getPaymentMethodCode(), $validPaymentMethods)
                && $token->getIsActive() && $token->getIsVisible()
            ) {
                $savedPayments[] = $token;
            }
        }

        return $savedPayments;
    }

    /**
     * @return bool
     */
    public function isVantivCcAvailable()
    {
        $methods = $this->getPaymentMethods();
        $methodCode = \Vantiv\Payment\Gateway\Cc\Config\VantivCcConfig::METHOD_CODE;

        $method = (isset($methods[$methodCode])) ? $methods[$methodCode] : [];

        return $this->isMethodAvailable($method);
    }

    /**
     * @return array
     */
    public function getPaymentMethods()
    {
        return $this->_scopeConfig->getValue(
            'payment',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $this->getSubscription()->getStore()
        );
    }

    /**
     * @param array $method
     * @return bool
     */
    public function isMethodAvailable($method)
    {
        if (isset($method['active']) &&
            isset($method['can_use_for_vantiv_subscription']) &&
            $method['active'] &&
            $method['can_use_for_vantiv_subscription']
        ) {
            return true;
        }

        return false;
    }

    /**
     * @param $method
     * @return bool
     */
    public function isMethodActive($method)
    {
        if (isset($method['active']) && $method['active']) {
            return true;
        }

        return false;
    }

    /**
     * @return string
     */
    public function getCcFormMageInitJson()
    {
        if (!$this->isVantivCcAvailable()) {
            return '{}';
        }

        $config = $this->ccConfigProvider->getEprotectConfig();
        $config['scriptUrl'] = $this->ccConfigProvider->getScriptUrl();

        $data = [
            'Vantiv_Payment/js/view/recurring/payment' => [
                'config' => $config,
            ],
        ];

        $json = json_encode($data);

        return $json;
    }
}
