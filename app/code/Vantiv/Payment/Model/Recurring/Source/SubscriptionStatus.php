<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Vantiv\Payment\Model\Recurring\Source;

class SubscriptionStatus extends AbstractArraySource
{
    const ACTIVE = 'active';
    const SUSPENDED = 'suspended';
    const CANCELLED = 'cancelled';

    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
        return [
            ['value' => self::ACTIVE, 'label' => __('Active')],
            ['value' => self::SUSPENDED, 'label' => __('Suspended')],
            ['value' => self::CANCELLED, 'label' => __('Cancelled')]
        ];
    }
}
