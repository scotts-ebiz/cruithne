<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Model\Config\Source;

/**
 * Advanced fraud results action options source model.
 */
class AdvancedFraudAction implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Action 'accept' option.
     *
     * @var string
     */
    const ACCEPT = 'accept';

    /**
     * Action 'reject' option.
     *
     * @var string
     */
    const REJECT = 'reject';

    /**
     * Action 'review' option.
     *
     * @var string
     */
    const REVIEW = 'review';

    /**
     * Return array of options as value-label pairs.
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => self::ACCEPT, 'label' => __('Accept')],
            ['value' => self::REVIEW, 'label' => __('Review')],
            ['value' => self::REJECT, 'label' => __('Reject')],
        ];
    }
}
