<?php
namespace SMG\SubscriptionApi\Model;
 
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
    protected $_isOffline = true;
}