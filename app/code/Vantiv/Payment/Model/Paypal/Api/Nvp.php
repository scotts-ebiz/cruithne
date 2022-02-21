<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Vantiv\Payment\Model\Paypal\Api;

/**
 * NVP API wrappers model
 */
class Nvp extends \Magento\Paypal\Model\Api\Nvp
{
    /**
     * Default payment action
     */
    const PAYMENTACTION = 'ORDER';

    /**
     * Config instance
     *
     * @var \Vantiv\Payment\Model\Paypal\Config
     */
    protected $_config;

    /**
     * Payment action getter
     *
     * @return string
     */
    public function getPaymentAction()
    {
        return self::PAYMENTACTION;
    }
}
