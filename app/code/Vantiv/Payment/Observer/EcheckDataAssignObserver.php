<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Observer;

use Magento\Framework\Event\Observer;
use Magento\Payment\Observer\AbstractDataAssignObserver;
use Magento\Quote\Api\Data\PaymentInterface;

/**
 * Class EcheckDataAssignObserver
 */
class EcheckDataAssignObserver extends AbstractDataAssignObserver
{
    const ECHECK_ACCOUNT_TYPE = 'echeck_account_type';
    const ECHECK_ACCOUNT_NAME = 'echeck_account_name';
    const ECHECK_ROUTING_NUMBER = 'echeck_routing_number';

    /**
     * Available payment information fields.
     *
     * @var array
     */
    protected $echeckDataKeys = [
        self::ECHECK_ACCOUNT_TYPE,
        self::ECHECK_ACCOUNT_NAME,
        self::ECHECK_ROUTING_NUMBER,
    ];

    /**
     * Transfer additional data into payment info fields.
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        $data = $this->readDataArgument($observer);

        $additionalData = $data->getData(PaymentInterface::KEY_ADDITIONAL_DATA);
        if (!is_array($additionalData)) {
            return;
        }

        $payment = $this->readPaymentModelArgument($observer);

        foreach ($this->echeckDataKeys as $key) {
            if (isset($additionalData[$key])) {
                $payment->setData($key, $additionalData[$key]);
            }
        }
    }
}
