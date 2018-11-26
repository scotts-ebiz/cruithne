<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Vantiv\Payment\Model;

class ProductPlugin
{
    /**
     * @var \Vantiv\Payment\Helper\Recurring
     */
    private $recurringHelper;

    /**
     * @var \Vantiv\Payment\Gateway\Recurring\Config\VantivSubscriptionConfig
     */
    private $recurringConfig;

    /**
     * @param \Vantiv\Payment\Helper\Recurring $recurringHelper
     * @param \Vantiv\Payment\Gateway\Recurring\Config\VantivSubscriptionConfig $recurringConfig
     */
    public function __construct(
        \Vantiv\Payment\Helper\Recurring $recurringHelper,
        \Vantiv\Payment\Gateway\Recurring\Config\VantivSubscriptionConfig $recurringConfig
    ) {
        $this->recurringHelper = $recurringHelper;
        $this->recurringConfig = $recurringConfig;
    }

    /**
     * Plugin for:
     * Get product price
     *
     * @param \Magento\Catalog\Model\Product $subject
     * @param $result
     * @return float
     */
    public function afterGetPrice(\Magento\Catalog\Model\Product $subject, $result)
    {
        if (in_array($subject->getTypeId(), $this->recurringHelper->getAllowedProductTypeIds())
            && $this->recurringConfig->getValue('active')
            && $subject->getVantivRecurringEnabled()
            && ($plan = $this->recurringHelper->getSelectedPlan($subject))
        ) {
            $result = $plan->getIntervalAmount();
        }
        return $result;
    }
}
