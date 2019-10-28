<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace SMG\Subscriptions\Model;
 
/**
 * Pay In Store payment method model
 */
class RecurlyPayment extends \Magento\Payment\Model\Method\AbstractMethod
{
 
    /**
     * Payment code
     *
     * @var string
     */
    protected $_code = 'recurly';
 
    /**
     * Availability option
     *
     * @var bool
     */
    protected $_isOffline = false;
}