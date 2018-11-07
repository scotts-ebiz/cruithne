<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Vantiv\Payment\Model\Recurring\Source;

class RecoveryTransactionStatus extends AbstractArraySource
{
    const APPROVED = 'approved';
    const DECLINED = 'declined';
    const CANCELLED = 'cancelled';

    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
        return [
            ['value' => self::APPROVED, 'label' => __('Approved')],
            ['value' => self::DECLINED, 'label' => __('Declined')],
            ['value' => self::CANCELLED, 'label' => __('Cancelled')]
        ];
    }
}
