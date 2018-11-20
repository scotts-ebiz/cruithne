<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Vantiv\Payment\Model\Recurring\Source;

class TrialInterval extends AbstractArraySource
{
    const DAY = 'DAY';
    const MONTH = 'MONTH';

    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
        return [
            ['value' => self::DAY, 'label' => __('Day')],
            ['value' => self::MONTH, 'label' => __('Month')],
        ];
    }
}
