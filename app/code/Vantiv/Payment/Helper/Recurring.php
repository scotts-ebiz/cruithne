<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Vantiv\Payment\Helper;

use Vantiv\Payment\Model\Recurring\Source\Interval;
use Vantiv\Payment\Model\Recurring\Source\TrialInterval;
use Magento\Catalog\Model\Product;

class Recurring extends \Magento\Framework\App\Helper\AbstractHelper
{
    const PENDING_RECURRING_PAYMENT_ORDER_STATUS = 'pending_recurring_payment';

    /**
     * Product type ids supported to act as subscriptions
     *
     * @var array
     */
    private $allowedProductTypeIds = [
        \Magento\Catalog\Model\Product\Type::TYPE_SIMPLE,
        \Magento\Catalog\Model\Product\Type::TYPE_VIRTUAL
    ];

    /**
     * @var \Vantiv\Payment\Model\Recurring\PlanFactory
     */
    private $planFactory;

    /**
     * @var \Vantiv\Payment\Model\ResourceModel\Recurring\Plan\CollectionFactory
     */
    private $plansCollectionFactory;

    /**
     * @var Interval
     */
    private $intervalSource;

    /**
     * @var TrialInterval
     */
    private $trialIntervalSource;

    /**
     * @var array
     */
    private $intervals;

    /**
     * @var array
     */
    private $trialIntervals;

    /**
     * @var \Magento\Framework\Escaper
     */
    private $escaper;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    private $localeDate;

    /**
     * Recurring constructor.
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Vantiv\Payment\Model\Recurring\PlanFactory $planFactory
     * @param \Vantiv\Payment\Model\ResourceModel\Recurring\Plan\CollectionFactory $plansCollectionFactory
     * @param Interval $intervalSource
     * @param TrialInterval $trialIntervalSource
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Vantiv\Payment\Model\Recurring\PlanFactory $planFactory,
        \Vantiv\Payment\Model\ResourceModel\Recurring\Plan\CollectionFactory $plansCollectionFactory,
        Interval $intervalSource,
        TrialInterval $trialIntervalSource,
        \Magento\Framework\Escaper $escaper,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate
    ) {
        parent::__construct($context);
        $this->plansCollectionFactory = $plansCollectionFactory;
        $this->planFactory = $planFactory;
        $this->intervalSource = $intervalSource;
        $this->trialIntervalSource = $trialIntervalSource;
        $this->escaper = $escaper;
        $this->localeDate = $localeDate;
    }

    /**
     * Get supported product type ids
     *
     * @return array
     */
    public function getAllowedProductTypeIds()
    {
        return $this->allowedProductTypeIds;
    }

    /**
     * Plan intervals getter
     *
     * @return array
     */
    private function getIntervals()
    {
        if ($this->intervals === null) {
            $this->intervals = $this->intervalSource->toOptionHash();
        }

        return $this->intervals;
    }

    /**
     * Plan trial intervals getter
     *
     * @return array
     */
    private function getTrialIntervals()
    {
        if ($this->trialIntervals === null) {
            $this->trialIntervals = $this->trialIntervalSource->toOptionHash();
        }

        return $this->trialIntervals;
    }

    /**
     * Retrieve product subscription plans
     *
     * @param Product $product
     * @return array|\Vantiv\Payment\Model\ResourceModel\Recurring\Plan\CollectionFactory
     */
    public function getProductSubscriptionPlans(Product $product)
    {
        if ($product->getVantivSubscriptionPlans() === null) {
            $collection = $this->plansCollectionFactory->create()->addProductFilter($product)->addActiveFilter();
            foreach ($collection as $plan) {
                $plan->setProduct($product);
            }
            $product->setVantivSubscriptionPlans($collection);
        }

        return $product->getVantivSubscriptionPlans();
    }

    /**
     * Get plan interval label
     *
     * @param $intervalCode
     * @return string
     */
    public function getPlanIntervalLabel($intervalCode)
    {
        $intervals = $this->getIntervals();
        return isset($intervals[$intervalCode]) ? $intervals[$intervalCode] : '';
    }

    /**
     * Get trial interval label
     *
     * @param $trialIntervalCode
     * @return string
     */
    public function getPlanTrialIntervalLabel($trialIntervalCode)
    {
        $trialIntervals = $this->getTrialIntervals();
        return isset($trialIntervals[$trialIntervalCode]) ? $trialIntervals[$trialIntervalCode] : '';
    }

    /**
     * Build plan option title
     *
     * @param \Vantiv\Payment\Model\Recurring\Plan $plan
     * @param string $renderedPrice
     * @return string
     */
    public function buildPlanOptionTitle(\Vantiv\Payment\Model\Recurring\Plan $plan, $renderedPrice = '')
    {
        $planInterval = strtolower($this->getPlanIntervalLabel($plan->getInterval()));
        $trialInfo = '';
        if ($plan->getNumberOfTrialIntervals() && $plan->getTrialInterval()) {
            $trialInfo = ', ' . $plan->getNumberOfTrialIntervals()
                . ' ' . strtolower($this->getPlanTrialIntervalLabel($plan->getTrialInterval())) . __('(s)')
                . ' ' . __('trial');
        }
        $maxPaymentsInfo = '';
        if ($plan->getNumberOfPayments()) {
            $maxPaymentsInfo = ', ' . $plan->getNumberOfPayments() . ' ' . __(' payments max');
        }

        return ($renderedPrice ? $renderedPrice . ' ' : '')
        . $this->escaper->escapeHtml(__('paid') . ' ' . $planInterval . $trialInfo . $maxPaymentsInfo);
    }

    /**
     * Retrieve selected plan for product
     *
     * @param Product $product
     * @return \Vantiv\Payment\Model\Recurring\Plan|false
     */
    public function getSelectedPlan(Product $product)
    {
        $planOption = $product->getCustomOption('vantiv_subscription_plan_id');
        if (!($planOption && $planOption->getValue())) {
            return false;
        }

        $planId = $planOption->getValue();

        if ($product->getVantivSubscriptionPlansCache($planId) === null) {
            $plan = $this->planFactory->create()->load($planId);
            $plansCache = $product->getVantivSubscriptionPlansCache() ?: [];
            $plansCache[$planId] = $plan->getId() ? $plan : false;
            $product->setVantivSubscriptionPlansCache($plansCache);
        }

        return $product->getVantivSubscriptionPlansCache($planId);
    }

    /**
     * Get array with selected plan information
     *
     * @param Product $product
     * @return array
     */
    public function getSelectedPlanOptionInfo(Product $product)
    {
        $planInfo = [];
        $plan = $this->getSelectedPlan($product);
        if ($plan) {
            $planInfo = [
                [
                    'label'   => 'Subscription Details',
                    'value'   => $this->buildPlanOptionTitle($plan),
                    'plan_id' => $plan->getId(),
                ]
            ];
        }
        return $planInfo;
    }

    /**
     * Get array with selected plan start date information
     *
     * @param Product $product
     * @return array|bool
     */
    public function getSelectedPlanStartDateOptionInfo(Product $product)
    {
        $startDateInfo = [];
        $startDateOption = $product->getCustomOption('vantiv_subscription_start_date');
        if ($startDateOption && $startDateOption->getValue() && $product->getVantivRecurringAllowStart()) {
            $today = $this->localeDate->date()->setTime(0, 0, 0);
            try {
                $startDate = $this->localeDate->date($startDateOption->getValue(), null, false);
            } catch (\Exception $e) {
                // intentionally suppressing any exceptions during date object creation
            }
            if (isset($startDate) && $startDate->setTime(0, 0, 0)->getTimestamp() > $today->getTimestamp()) {
                $startDateInfo = [
                    [
                        'label' => 'Subscription Start Date',
                        'value' => $this->localeDate->formatDateTime(
                            $startDate,
                            \IntlDateFormatter::SHORT,
                            \IntlDateFormatter::NONE,
                            null,
                            date_default_timezone_get()
                        )
                    ]
                ];
            }
        }

        return $startDateInfo;
    }

    /**
     * Get trial info in one label
     *
     * @param \Vantiv\Payment\Model\Recurring\Subscription $subscription
     * @return string
     */
    public function getSubscriptionTrialLabel(\Vantiv\Payment\Model\Recurring\Subscription $subscription)
    {
        return $this->getTrialLabel($subscription->getNumberOfTrialIntervals(), $subscription->getTrialInterval());
    }

    /**
     * Get trial info in one label
     *
     * @param \Vantiv\Payment\Model\Recurring\Plan
     * @return string
     */
    public function getPlanTrialLabel(\Vantiv\Payment\Model\Recurring\Plan $plan)
    {
        return $this->getTrialLabel($plan->getNumberOfTrialIntervals(), $plan->getTrialInterval());
    }

    /**
     * Build trial label (number of trial intervals and interval label combined)
     *
     * @param $numberOfTrialIntervals
     * @param $trialInterval
     * @return string
     */
    private function getTrialLabel($numberOfTrialIntervals, $trialInterval)
    {
        $label = '';
        if ($numberOfTrialIntervals && $trialInterval) {
            $label = $numberOfTrialIntervals . ' ' . $this->getPlanTrialIntervalLabel($trialInterval) . __('(s)');
        }
        return $label;
    }

    /**
     * Check is quote contains subscription item
     *
     * @param \Magento\Quote\Model\Quote $quote
     * @return bool
     */
    public function quoteContainsSubscription(\Magento\Quote\Model\Quote $quote)
    {
        foreach ($quote->getAllItems() as $item) {
            if (($product = $item->getProduct())
                && $this->getSelectedPlan($product)
            ) {
                return true;
            }
        }
        return false;
    }

    /**
     * Retrieve selected subscription plan id from order item
     *
     * @param \Magento\Sales\Model\Order\Item $item
     * @return int|false
     */
    public function getOrderItemPlanId(\Magento\Sales\Model\Order\Item $item)
    {
        $planId = false;
        $productOption = $item->getProductOptionByCode('vantiv_subscription_options');
        if (is_array($productOption) && isset($productOption['plan_id'])) {
            $planId = $productOption['plan_id'];
        }
        return $planId;
    }

    /**
     * Retrieve selected subscription plan from order item
     *
     * @param \Magento\Sales\Model\Order\Item $item
     * @return \Vantiv\Payment\Model\Recurring\Plan
     */
    public function getOrderItemPlan(\Magento\Sales\Model\Order\Item $item)
    {
        if ($item->getVantivSubscriptionPlan() === null) {
            $item->setVantivSubscriptionPlan(false);
            $planId = $this->getOrderItemPlanId($item);
            if ($planId) {
                $plan = $this->planFactory->create()->load($planId);
                if ($plan->getId()) {
                    $item->setVantivSubscriptionPlan($plan);
                }
            }
        }

        return $item->getVantivSubscriptionPlan();
    }

    /**
     * Retrieve selected subscription start date from order item
     *
     * @param \Magento\Sales\Model\Order\Item $item
     * @return string
     */
    public function getOrderItemSubscriptionStartDate(\Magento\Sales\Model\Order\Item $item)
    {
        $startDate = '';
        $productOption = $item->getProductOptionByCode('vantiv_subscription_options');
        if (is_array($productOption) && isset($productOption['start_date'])) {
            $startDate = $productOption['start_date'];
        }

        return $startDate;
    }

    /**
     * Calculate subscription end date
     *
     * @param \Vantiv\Payment\Model\Recurring\Subscription $subscription
     * @return false|string
     */
    public function calculateEndDate(
        \Vantiv\Payment\Model\Recurring\Subscription $subscription
    ) {
        $interval = '+100 years';

        if ($subscription->getStartDate()) {
            $endDate = strtotime(sprintf('%s %s', $subscription->getStartDate(), $interval));
        } else {
            $endDate = strtotime($interval);
        }

        return date('Y-m-d', $endDate);
    }

    /**
     * Estimates next billing date
     *
     * @param $createdAt
     * @param $startDate
     * @param $interval
     * @param $numTrialIntervals
     * @param $trialInterval
     * @return string
     */
    public function estimateNextPaymentDate($createdAt, $startDate, $interval, $numTrialIntervals, $trialInterval)
    {
        $firstPaymentDate = $this->getFirstPaymentDate($createdAt, $startDate, $numTrialIntervals, $trialInterval);

        $currentDate = new \DateTime('now', new \DateTimeZone('UTC'));

        $nextPaymentDate = clone $firstPaymentDate;

        if ($currentDate < $firstPaymentDate) {
            return $firstPaymentDate->format('Y-m-d');
        }

        switch ($interval) {
            case Interval::WEEKLY:
                $diff = $currentDate->diff($firstPaymentDate);
                $weeks = ceil($diff->days / 7);
                if ($weeks == 0) {
                    $weeks++;
                }

                $nextPaymentDate->add(new \DateInterval('P' . $weeks . 'W'));
                break;
            case Interval::SEMIANNUAL:
                $semiAnnuals = ($currentDate->format('Y') - $firstPaymentDate->format('Y')) / 2;
                if ($currentDate > $firstPaymentDate) {
                    $semiAnnuals++;
                }

                $nextPaymentDate->add(new \DateInterval('P' . ($semiAnnuals * 6). 'M'));
                break;
            case Interval::QUARTERLY:
                $quarters = ($currentDate->format('Y') - $firstPaymentDate->format('Y')) / 4;
                if ($currentDate > $firstPaymentDate) {
                    $quarters++;
                }

                $nextPaymentDate->add(new \DateInterval('P' . ($quarters * 3). 'M'));
                break;
            case Interval::MONTHLY:
                $diff = $currentDate->diff($firstPaymentDate);
                $months = ($diff->y * 12) + ($diff->m) + 1;

                $nextPaymentDate->add(new \DateInterval('P' . $months . 'M'));
                break;
            case Interval::ANNUAL:
                $years = $currentDate->format('Y') - $firstPaymentDate->format('Y');
                if ($currentDate > $firstPaymentDate) {
                    $years++;
                }

                $nextPaymentDate->add(new \DateInterval('P' . $years . 'Y'));
                break;
        }

        return $nextPaymentDate->format('Y-m-d');
    }

    /**
     * @param $createdAt
     * @param $startDate
     * @param $numTrialIntervals
     * @param $trialInterval
     * @return \DateTime
     */
    public function getFirstPaymentDate($createdAt, $startDate, $numTrialIntervals, $trialInterval)
    {
        $date = $startDate ?: $createdAt;

        $firstPaymentDate = new \DateTime($date, new \DateTimeZone('UTC'));

        if ($numTrialIntervals && $trialInterval) {
            $interval = null;
            switch ($trialInterval) {
                case TrialInterval::DAY:
                    $interval = 'D';
                    break;
                case TrialInterval::MONTH:
                    $interval = 'M';
                    break;
            }

            if ($interval) {
                $firstPaymentDate->add(new \DateInterval('P' . $numTrialIntervals . $interval));
            }
        }

        return $firstPaymentDate;
    }

    /**
     * Retrieve subscription options for order item
     *
     * @param \Magento\Sales\Model\Order\Item $item
     * @return array
     */
    public function prepareOrderItemOptions(\Magento\Sales\Model\Order\Item $item)
    {
        $result = [];
        if (($options = $item->getProductOptions())
            && isset($options['vantiv_subscription_options']['options_to_display'])
        ) {
            $optionsToDisplay = $options['vantiv_subscription_options']['options_to_display'];
            if (isset($optionsToDisplay['subscription_details'])) {
                $result = array_merge($result, $optionsToDisplay['subscription_details']);
            }
            if (isset($optionsToDisplay['start_date'])) {
                $result = array_merge($result, $optionsToDisplay['start_date']);
            }
        }

        return $result;
    }
}
