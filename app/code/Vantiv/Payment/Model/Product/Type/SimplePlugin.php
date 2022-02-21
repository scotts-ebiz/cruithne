<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Vantiv\Payment\Model\Product\Type;

class SimplePlugin
{
    /**
     * @var \Magento\Checkout\Model\Session
     */
    private $checkoutSession;

    /**
     * @var \Vantiv\Payment\Gateway\Recurring\Config\VantivSubscriptionConfig
     */
    private $recurringConfig;

    /**
     * @var \Vantiv\Payment\Helper\Recurring
     */
    private $recurringHelper;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    private $localeDate;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    /**
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Vantiv\Payment\Gateway\Recurring\Config\VantivSubscriptionConfig $recurringConfig
     * @param \Vantiv\Payment\Helper\Recurring $recurringHelper
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Magento\Checkout\Model\Session $checkoutSession,
        \Vantiv\Payment\Gateway\Recurring\Config\VantivSubscriptionConfig $recurringConfig,
        \Vantiv\Payment\Helper\Recurring $recurringHelper,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->recurringConfig = $recurringConfig;
        $this->recurringHelper = $recurringHelper;
        $this->storeManager = $storeManager;
        $this->localeDate = $localeDate;
        $this->logger = $logger;
    }

    /**
     * Plugin for:
     * Initialize product(s) for add to cart process.
     * Advanced version of func to prepare product for cart - processMode can be specified there.
     *
     * @param \Magento\Catalog\Model\Product\Type\AbstractType $subject
     * @param \Closure $proceed
     * @param \Magento\Framework\DataObject $buyRequest
     * @param \Magento\Catalog\Model\Product $product
     * @param null|string $processMode
     * @return array|string
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundPrepareForCartAdvanced(
        \Magento\Catalog\Model\Product\Type\AbstractType $subject,
        \Closure $proceed,
        \Magento\Framework\DataObject $buyRequest,
        $product,
        $processMode = null
    ) {
        if (!(in_array($product->getTypeId(), $this->recurringHelper->getAllowedProductTypeIds())
            && $this->recurringConfig->getValue('active')
            && $product->getVantivRecurringEnabled()
            && ($planId = $buyRequest->getVantivSubscriptionPlan()))
        ) {
            return $proceed($buyRequest, $product, $processMode);
        }

        $product->addCustomOption('vantiv_subscription_plan_id', $planId);

        if ($product->getVantivRecurringAllowStart() && $buyRequest->getVantivSubscriptionStartDate()) {
            $startDate = is_array($buyRequest->getVantivSubscriptionStartDate())
                ? $buyRequest->getVantivSubscriptionStartDate() : [];
            $today = $this->localeDate->date();
            $today->setTime(0, 0, 0);
            $startDay = isset($startDate['day']) && intval($startDate['day'])
                ? intval($startDate['day']) : $today->format('j');
            $startMonth = isset($startDate['month']) && intval($startDate['month'])
                ? intval($startDate['month']) : $today->format('n');
            $startYear = isset($startDate['year']) && intval($startDate['year'])
                ? intval($startDate['year']) : $today->format('Y');
            $startDate = $this->localeDate->date()
                ->setTime(0, 0, 0)
                ->setDate($startYear, $startMonth, $startDay);
            if (!$startDate || $startDate->getTimestamp() < $today->getTimestamp()) {
                $startDate = $today;
            }

            if ($startDate->getTimestamp() > $today->getTimestamp()) {
                $product->addCustomOption(
                    'vantiv_subscription_start_date',
                    $startDate->format(\Magento\Framework\Stdlib\DateTime::DATE_PHP_FORMAT)
                );
            }
        }

        $result = $proceed($buyRequest, $product, $processMode);

        if (!$buyRequest->getResetCount() && ($item = $this->checkoutSession->getQuote()->getItemByProduct($product))) {
            return __('Subscription already in the cart');
        } else {
            if ($processMode == \Magento\Catalog\Model\Product\Type\AbstractType::PROCESS_MODE_FULL) {
                $product->setCartQty(1);
            }
            $product->setQty(1);
            $buyRequest->setQty(1);
        }

        return $result;
    }

    /**
     * Plugin for:
     * Check if product can be bought
     *
     * @param \Magento\Catalog\Model\Product\Type\AbstractType $subject
     * @param $product
     * @return \Magento\Catalog\Model\Product\Type\AbstractType
     * @throws \Magento\Framework\Exception\LocalizedException
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeCheckProductBuyState(
        \Magento\Catalog\Model\Product\Type\AbstractType $subject,
        $product
    ) {
        $planOption = $product->getCustomOption('vantiv_subscription_plan_id');
        if (!($planOption && $planOption->getValue())) {
            return;
        }

        $plan = $this->recurringHelper->getSelectedPlan($product);
        if (!$plan
            || !$this->recurringConfig->getValue('active')
            || !$product->getVantivRecurringEnabled()
            || !$plan->getActive()
            || $plan->getProductId() != $product->getId()
            || !in_array($plan->getWebsiteId(), [0, $this->storeManager->getStore()->getWebsiteId()])
        ) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('Selected subscription plan is not available.')
            );
        }

        $quoteItem = $planOption->getItem();
        if ($quoteItem->getQuote()->getItemsQty() > 1) {
            $quoteItem->setHasError(true)->setMessage(
                __('Subscriptions can be bought only separately, one subscription at a time.')
            );
            $quoteItem->getQuote()->setHasError(true);
        }
    }

    /**
     * Plugin for:
     * Prepare selected options for product
     *
     * @param \Magento\Catalog\Model\Product\Type\AbstractType $subject
     * @param \Closure $proceed
     * @param $product
     * @param $buyRequest
     * @return array
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundProcessBuyRequest(
        \Magento\Catalog\Model\Product\Type\AbstractType $subject,
        \Closure $proceed,
        $product,
        $buyRequest
    ) {
        $result = $proceed($product, $buyRequest);

        $subscriptionOptions = [];
        $planId = $buyRequest->getVantivSubscriptionPlan();
        if ($planId) {
            $subscriptionOptions['vantiv_subscription_plan'] = $planId;
        }
        $planStartDate = $buyRequest->getVantivSubscriptionStartDate();
        if ($planStartDate) {
            $subscriptionOptions['vantiv_subscription_start_date'] = $planStartDate;
        }

        return array_merge($subscriptionOptions, $result);
    }

    /**
     * Plugin for:
     * Prepare additional options/information for order item which will be
     * created from this product
     *
     * @param \Magento\Catalog\Model\Product\Type\AbstractType $subject
     * @param \Closure $proceed
     * @param \Magento\Catalog\Model\Product $product
     * @return array
     */
    public function aroundGetOrderOptions(
        \Magento\Catalog\Model\Product\Type\AbstractType $subject,
        \Closure $proceed,
        $product
    ) {
        $result = $proceed($product);

        $planOption = $product->getCustomOption('vantiv_subscription_plan_id');
        if (!($planOption && $planOption->getValue())) {
            return $result;
        }

        $subscriptionOptions = [];
        $plan = $this->recurringHelper->getSelectedPlan($product);
        if ($plan && $plan->getId()) {
            $subscriptionOptions['vantiv_subscription_options'] = [
                'plan_id' => $plan->getId(),
                'options_to_display' => [
                    'subscription_details' => $this->recurringHelper->getSelectedPlanOptionInfo($product)
                ]
            ];
            $planStartDateOption = $product->getCustomOption('vantiv_subscription_start_date');
            if ($planStartDateOption && $planStartDateOption->getValue() && $product->getVantivRecurringAllowStart()) {
                $today = $this->localeDate->date()->setTime(0, 0, 0);
                try {
                    $startDate = $this->localeDate->date($planStartDateOption->getValue(), null, false);
                } catch (\Exception $e) {
                    // intentionally suppressing any exceptions during date object creation
                    $this->logger->error($e);
                }
                if (isset($startDate)
                    && $startDate->setTime(0, 0, 0)->getTimestamp() > $today->getTimestamp()
                ) {
                    $subscriptionOptions['vantiv_subscription_options']['start_date']
                        = $planStartDateOption->getValue();
                    $subscriptionOptions['vantiv_subscription_options']['options_to_display']['start_date']
                        = $this->recurringHelper->getSelectedPlanStartDateOptionInfo($product);
                }
            }
        }

        return array_merge($result, $subscriptionOptions);
    }

    /**
     * Plugin for:
     * Check if product can be configured
     *
     * @param \Magento\Catalog\Model\Product\Type\AbstractType $subject
     * @param \Closure $proceed
     * @param $product
     * @return bool
     */
    public function aroundCanConfigure(
        \Magento\Catalog\Model\Product\Type\AbstractType $subject,
        \Closure $proceed,
        $product
    ) {
        $result = $proceed($product);

        if ($result
            || !$this->recurringConfig->getValue('active')
            || !in_array($product->getTypeId(), $this->recurringHelper->getAllowedProductTypeIds())
        ) {
            return $result;
        }

        return $product->getVantivRecurringEnabled() && $this->recurringHelper->getProductSubscriptionPlans($product);
    }
}
