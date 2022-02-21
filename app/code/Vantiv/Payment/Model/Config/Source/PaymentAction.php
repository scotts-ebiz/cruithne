<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Model\Config\Source;

use Magento\Payment\Model\Method\AbstractMethod;

/**
 * Payment Action options Source Model.
 */
class PaymentAction implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
        return [
            ['value' => AbstractMethod::ACTION_AUTHORIZE, 'label' => __('Authorize')],
            ['value' => AbstractMethod::ACTION_AUTHORIZE_CAPTURE, 'label' => __('Sale')]
        ];
    }
}
