<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vantiv\Payment\Model\Paypal\Express;

use Vantiv\Payment\Model\Paypal\Config as PaypalConfig;

/**
 * Wrapper that performs Paypal Express and Checkout communication
 * Use current Paypal Express method instance
 */
class Checkout extends \Magento\Paypal\Model\Express\Checkout
{
    /**
     * Api Model Type
     *
     * @var string
     */
    protected $_apiType = 'Vantiv\Payment\Model\Paypal\Api\Nvp';

    /**
     * Config instance
     *
     * @var PaypalConfig
     */
    protected $_config;

    /**
     * Payment method type
     *
     * @var string
     */
    protected $_methodType = PaypalConfig::METHOD_CODE;
}
