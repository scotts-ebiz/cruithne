<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Vantiv\Payment\Pricing\Price;

use Vantiv\Payment\Model\Recurring\Plan;

interface PlanPriceInterface
{
    /**
     * @param Plan $plan
     * @return \Magento\Framework\Pricing\Amount\AmountInterface
     */
    public function getPlanAmount(Plan $plan);
}
