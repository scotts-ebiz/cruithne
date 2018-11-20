<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Plugin\Model\Sales;

use Magento\Sales\Model\Order as OriginalOrder;
use Vantiv\Payment\Gateway\Echeck\Config\VantivEcheckConfig;

/**
 * Class Order
 */
class Order
{
    /**
     * Additional checks for shipment possibility
     *
     * @param OriginalOrder $subject
     * @param bool $result
     * @return bool
     */
    public function afterCanShip(
        OriginalOrder $subject,
        $result
    ) {
        $payment = $subject->getPayment();
        $method = $payment->getMethodInstance();

        if ($method->getCode() == VantivEcheckConfig::METHOD_CODE) {
            if ($subject->canInvoice()) {
                $result = false;
            }
        }

        return $result;
    }
}
