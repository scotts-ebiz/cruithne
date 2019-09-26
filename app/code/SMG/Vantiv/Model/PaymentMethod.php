<?php
namespace SMG\Vantiv\Model;

class PaymentMethod extends \Magento\Payment\Model\Method\AbstractMethod
{
 
    /**
     * Payment code
     *
     * @var string
     */
    protected $_code = 'vantiv_keypadpayment';
 
    /**
     * Availability option
     *
     * @var bool
     */
    protected $_isOffline = true;
}
