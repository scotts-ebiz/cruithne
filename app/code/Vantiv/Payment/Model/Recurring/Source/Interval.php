<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Vantiv\Payment\Model\Recurring\Source;

class Interval extends AbstractArraySource
{
    const ANNUAL = 'ANNUAL';
    const SEMIANNUAL = 'SEMIANNUAL';
    const QUARTERLY = 'QUARTERLY';
    const MONTHLY = 'MONTHLY';
    const WEEKLY = 'WEEKLY';

    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
        return [
            ['value' => self::WEEKLY, 'label' => __('Weekly')],
            ['value' => self::MONTHLY, 'label' => __('Monthly')],
            ['value' => self::QUARTERLY, 'label' => __('Quarterly')],
            ['value' => self::SEMIANNUAL, 'label' => __('Semiannually')],
            ['value' => self::ANNUAL, 'label' => __('Annually')],
        ];
    }
}
