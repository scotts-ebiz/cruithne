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
    protected $_formBlockType = \SMG\Vantiv\Block\Form\Keypad::class;
    /**
     * Availability option
     *
     * @var bool
     */
    protected $_isOffline = true;
}
