<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Model\Config\Source;

/**
 * PayPal Environment options Source Model.
 */
class PaypalEnvironment implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * PayPal Environment code for 'Sandbox' option.
     */
    const SANDBOX_CODE = 'sandbox';

    /**
     * PayPal Environment code for 'production' option.
     */
    const PRODUCTION_CODE = 'production';

    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
        return [
            ['value' => self::SANDBOX_CODE, 'label' => __('Sandbox')],
            ['value' => self::PRODUCTION_CODE, 'label' => __('Production')]
        ];
    }
}
