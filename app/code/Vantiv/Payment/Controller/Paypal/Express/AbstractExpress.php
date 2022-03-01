<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vantiv\Payment\Controller\Paypal\Express;

abstract class AbstractExpress extends \Magento\Paypal\Controller\Express\AbstractExpress
{
    /**
     * @var \Vantiv\Payment\Model\Paypal\Express\Checkout
     */
    protected $_checkout;

    /**
     * @var \Vantiv\Payment\Model\Paypal\Config
     */
    protected $_config;

    /**
     * Config mode type
     *
     * @var string
     */
    protected $_configType = 'Vantiv\Payment\Model\Paypal\Config';

    /**
     * Config method type
     *
     * @var string
     */
    protected $_configMethod = \Vantiv\Payment\Model\Paypal\Config::METHOD_CODE;

    /**
     * Checkout mode type
     *
     * @var string
     */
    protected $_checkoutType = 'Vantiv\Payment\Model\Paypal\Express\Checkout';
}
