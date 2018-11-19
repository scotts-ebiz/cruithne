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
 * Class DataAssignObserver
 */
class CcDataAssignObserver extends AbstractDataAssignObserver
{
    /**
     * Request param key.
     *
     * @var string
     */
    const PAYPAGE_KEY = 'paypage_registration_id';

    /**
     * Requested CC type key.
     *
     * @var string
     */
    const CCTYPE_KEY = 'type';

    /**
     * Requested CC last 4 key.
     *
     * @var string
     */
    const CCLASTFOUR_KEY = 'last_four';

    /**
     * Requested CC expiration month key.
     *
     * @var string
     */
    const CCEXPMONTH_KEY = 'exp_month';

    /**
     * Requested CC expiration year key.
     *
     * @var string
     */
    const CCEXPYEAR_KEY = 'exp_year';

    /**
     * Assign credit card data to payment info.
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        $data = $this->readDataArgument($observer);

        /** @var \Magento\Quote\Model\Quote\Payment $payment */
        $payment = $this->readPaymentModelArgument($observer);
        $additionalData = $data->getData(PaymentInterface::KEY_ADDITIONAL_DATA);

        if (!is_array($additionalData) || !isset($additionalData[self::PAYPAGE_KEY])) {
            return;
        }

        $payment->setAdditionalInformation(self::PAYPAGE_KEY, $additionalData[self::PAYPAGE_KEY]);

        if (isset($additionalData[self::CCTYPE_KEY])) {
            $payment->setCcType($additionalData[self::CCTYPE_KEY]);
            $payment->setAdditionalInformation(
                self::CCTYPE_KEY,
                $additionalData[self::CCTYPE_KEY]
            );
        }

        if (isset($additionalData[self::CCLASTFOUR_KEY])) {
            $payment->setAdditionalInformation(
                self::CCLASTFOUR_KEY,
                $additionalData[self::CCLASTFOUR_KEY]
            );
        }

        if (isset($additionalData[self::CCEXPMONTH_KEY])
            && isset($additionalData[self::CCEXPYEAR_KEY])
        ) {
            $payment->setAdditionalInformation(
                self::CCEXPMONTH_KEY,
                $additionalData[self::CCEXPMONTH_KEY]
            );

            $payment->setAdditionalInformation(
                self::CCEXPYEAR_KEY,
                $additionalData[self::CCEXPYEAR_KEY]
            );
        }
    }
}
