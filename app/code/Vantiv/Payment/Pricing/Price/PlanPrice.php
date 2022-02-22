<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Vantiv\Payment\Pricing\Price;

use Magento\Catalog\Pricing\Price\RegularPrice;
use Vantiv\Payment\Model\Recurring\Plan;

class PlanPrice extends RegularPrice implements PlanPriceInterface
{
    /**
     * @param Plan $plan
     * @return \Magento\Framework\Pricing\Amount\AmountInterface
     */
    public function getPlanAmount(Plan $plan)
    {
        $price = $plan->getIntervalAmount();
        $convertedPrice = $this->priceCurrency->convertAndRound($price);
        return $this->calculator->getAmount($convertedPrice, $plan->getProduct());
    }
}
