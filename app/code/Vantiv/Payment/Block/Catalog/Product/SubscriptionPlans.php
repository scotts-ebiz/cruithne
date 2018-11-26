<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Vantiv\Payment\Block\Catalog\Product;

use Vantiv\Payment\Model\Recurring\Plan;
use Magento\Framework\Serialize\Serializer\Json;

class SubscriptionPlans extends \Magento\Catalog\Block\Product\AbstractProduct
{
    /**
     * @var \Vantiv\Payment\Gateway\Recurring\Config\VantivSubscriptionConfig
     */
    private $recurringConfig;

    /**
     * @var \Vantiv\Payment\Helper\Recurring
     */
    private $recurringHelper;

    /**
     * @var Json
     */
    private $encoder;

    /**
     * @param \Magento\Catalog\Block\Product\Context $context
     * @param \Vantiv\Payment\Gateway\Recurring\Config\VantivSubscriptionConfig $recurringConfig
     * @param \Vantiv\Payment\Helper\Recurring $recurringHelper
     * @param Json $encoder
     * @param array $data
     */
    public function __construct(
        \Magento\Catalog\Block\Product\Context $context,
        \Vantiv\Payment\Gateway\Recurring\Config\VantivSubscriptionConfig $recurringConfig,
        \Vantiv\Payment\Helper\Recurring $recurringHelper,
        Json $encoder,
        array $data = []
    ) {
        $this->recurringConfig = $recurringConfig;
        $this->recurringHelper = $recurringHelper;
        $this->encoder = $encoder;
        parent::__construct($context, $data);
    }

    /**
     * Check is subscriptions functionality enabled globally and product is of supported type
     *
     * @return bool
     */
    public function isSubscriptionsEnabled()
    {
        return $this->recurringConfig->getValue('active')
            && in_array($this->getProduct()->getTypeId(), $this->recurringHelper->getAllowedProductTypeIds());
    }

    /**
     * Retrieve product subscription plans
     *
     * @return array|\Vantiv\Payment\Model\ResourceModel\Recurring\Plan\CollectionFactory
     */
    public function getPlans()
    {
        return $this->recurringHelper->getProductSubscriptionPlans($this->getProduct());
    }

    /**
     * @param \Vantiv\Payment\Model\Recurring\Plan $plan
     * @return bool
     */
    public function isPlanSelected(\Vantiv\Payment\Model\Recurring\Plan $plan)
    {
        return $plan->getId() == $this->getProduct()->getPreconfiguredValues()->getVantivSubscriptionPlan();
    }

    /**
     * Check if product has active subscription plans
     *
     * @return bool
     */
    public function hasPlans()
    {
        return count($this->getPlans());
    }

    /**
     * Date (dd/mm/yyyy) html drop-downs
     *
     * @return string Formatted Html
     */
    public function getStartDateHtml()
    {
        $fieldsSeparator = '&nbsp;';
        $fieldsOrder = $this->_scopeConfig->getValue(
            'catalog/custom_options/date_fields_order',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        $fieldsOrder = str_replace(',', $fieldsSeparator, $fieldsOrder);

        $todaysDate = $selectedDate = $this->_localeDate->date()->setTime(0, 0, 0);

        $selectedDateArr = $this->getProduct()->getPreconfiguredValues()->getVantivSubscriptionStartDate();
        $selectedDateArr = is_array($selectedDateArr) ? $selectedDateArr : [];
        if (isset($selectedDateArr['day']) && isset($selectedDateArr['month']) && isset($selectedDateArr['year'])) {
            $selectedDate = $this->_localeDate->date()->setTime(0, 0, 0)
                ->setDate($selectedDateArr['year'], $selectedDateArr['month'], $selectedDateArr['day']);
            if (!$selectedDate || $selectedDate->getTimestamp() < $todaysDate->getTimestamp()) {
                $selectedDate = $todaysDate;
            }
        }

        $monthsHtml = $this->getSelectFromToHtml('month', 1, 12, $selectedDate->format('n'));
        $daysHtml = $this->getSelectFromToHtml('day', 1, 31, $selectedDate->format('j'));

        $yearStart = $todaysDate->format('Y');
        $yearEnd = $yearStart + 3;
        $yearsHtml = $this->getSelectFromToHtml('year', $yearStart, $yearEnd, $selectedDate);

        $translations = ['d' => $daysHtml, 'm' => $monthsHtml, 'y' => $yearsHtml];
        return strtr($fieldsOrder, $translations);
    }

    /**
     * Return drop-down html with range of values
     *
     * @param string $name Id/name of html select element
     * @param int $from Start position
     * @param int $to End position
     * @param int|null $value Value selected
     * @return string Formatted Html
     */
    private function getSelectFromToHtml($name, $from, $to, $value = null)
    {
        $options = [];
        for ($i = $from; $i <= $to; $i++) {
            $options[] = ['value' => $i, 'label' => ($i < 10 ? '0' . $i : $i)];
        }
        return $this->getHtmlSelect($name, $value)->setOptions($options)->getHtml();
    }

    /**
     * HTML select element
     *
     * @param string $name Id/name of html select element
     * @param int|null $value
     * @return mixed
     */
    private function getHtmlSelect($name, $value = null)
    {
        $require = '';
        $select = $this->getLayout()->createBlock('Magento\Framework\View\Element\Html\Select')
            ->setId('vantiv_subscription_start_date_' . $name)
            ->setClass('admin__control-select datetime-picker' . $require)
            ->setName('vantiv_subscription_start_date[' . $name . ']');

        $extraParams = 'style="width:auto"';
        $extraParams .= ' data-role="calendar-dropdown" data-calendar-role="' . $name . '"';
        $extraParams .= ' data-selector="' . $select->getName() . '"';

        $select->setExtraParams($extraParams);

        if ($value !== null) {
            $select->setValue($value);
        }

        return $select;
    }

    /**
     * Generate payment plan option title
     *
     * @param Plan $plan
     * @return string
     */
    public function getPlanTitle(Plan $plan)
    {
        return $this->recurringHelper->buildPlanOptionTitle($plan, $this->getPlanPrice($plan));
    }

    /**
     * @param Plan $plan
     * @return \Magento\Framework\Pricing\Amount\AmountInterface
     */
    private function getPlanAmount(Plan $plan)
    {
        return $this->getPriceType()->getPlanAmount($plan);
    }

    /**
     * @param Plan $plan
     * @return string
     */
    public function getPlanPrice(Plan $plan)
    {
        return $this->getLayout()->getBlock('product.price.render.default')->renderAmount(
            $this->getPlanAmount($plan),
            $this->getPriceType(),
            $this->getProduct()
        );
    }

    /**
     * Get LinkPrice Type
     *
     * @return \Magento\Framework\Pricing\Price\PriceInterface
     */
    private function getPriceType()
    {
        return $this->getProduct()->getPriceInfo()->getPrice('vantiv_subscription_price');
    }

    /**
     * @return string
     */
    public function getJsonConfig()
    {
        $priceInfo = $this->getProduct()->getPriceInfo();

        $finalPrice = $priceInfo->getPrice(\Magento\Catalog\Pricing\Price\FinalPrice::PRICE_CODE);
        $basePrice = $priceInfo->getPrice(\Magento\Catalog\Pricing\Price\BasePrice::PRICE_CODE);

        $defaultConfig = [
            'finalPrice' => $finalPrice->getAmount()->getValue(),
            'basePrice' => $basePrice->getAmount()->getValue(),
        ];

        $plansConfig = [];
        foreach ($this->getPlans() as $plan) {
            $amount = $finalPrice->getCustomAmount($plan->getIntervalAmount());
            $plansConfig[$plan->getId()] = [
                'finalPrice' => $amount->getValue(),
                'basePrice' => $amount->getBaseAmount()
            ];
        }

        return $this->encoder->serialize(['plans' => $plansConfig, 'defaults' => $defaultConfig]);
    }

    /**
     * Render block HTML
     *
     * @return string
     */
    protected function _toHtml()
    {
        if (!($this->getProduct()->isSaleable()
            && $this->isSubscriptionsEnabled()
            && $this->getProduct()->getVantivRecurringEnabled()
            && $this->hasPlans())
        ) {
            return '';
        }

        return parent::_toHtml();
    }
}
