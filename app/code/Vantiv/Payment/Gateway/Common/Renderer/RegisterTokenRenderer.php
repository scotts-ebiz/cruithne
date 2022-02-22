<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Gateway\Common\Renderer;

use XMLWriter;

/**
 * RegisterToken request renderer.
 */
class RegisterTokenRenderer extends AbstractRenderer
{
    /**
     * Request wrapper.
     *
     * @var LitleOnlineRequestWrapper
     */
    private $litleOnlineRequestWrapper = null;

    /**
     * Constructor.
     *
     * @param LitleOnlineRequestWrapper $litleOnlineRequestWrapper
     */
    public function __construct(
        LitleOnlineRequestWrapper $litleOnlineRequestWrapper
    ) {
        $this->litleOnlineRequestWrapper = $litleOnlineRequestWrapper;
    }

    /**
     * Build <registerTokenRequest> XML node.
     *
     * <registerTokenRequest reportGroup="REPORT_GROUP" customerId="CUSTOMER_ID">
     *     <orderId>androidpay_2</orderId>
     *     <paypageRegistrationId>LOW_VALUE_TOKEN</paypageRegistrationId>
     * </registerTokenRequest>
     *
     * @param array $subject
     * @return string
     */
    public function render(array $subject)
    {
        /*
         * Prepare document variables.
         */
        $merchantId = $this->readDataOrNull($subject, 'merchantId');
        $user = $this->readDataOrNull($subject, 'user');
        $password = $this->readDataOrNull($subject, 'password');

        $reportGroup = $this->readDataOrNull($subject, 'reportGroup');
        $customerId = $this->readDataOrNull($subject, 'customerId');
        $id = $this->readDataOrNull($subject, 'id');
        $orderId = $this->readDataOrNull($subject, 'orderId');

        /*
         * Prepare eProtect data.
         */
        $paypageRegistrationId = $this->readDataOrNull($subject, 'paypageRegistrationId');

        /*
         * Prepare credit card data.
         */
        $accountNumber = $this->readDataOrNull($subject, 'accountNumber');
        $cardValidationNum = $this->readDataOrNull($subject, 'cardValidationNum');

        /*
         * Prepare eCheck data.
         */
        $accNum = $this->readDataOrNull($subject, 'accNum');
        $routingNum = $this->readDataOrNull($subject, 'routingNum');

        /*
         * Generate XML document.
         */
        $writer = new XMLWriter();
        $writer->openMemory();
        $writer->setIndent(true);
        $writer->setIndentString(str_repeat(' ', 4));
        $writer->startElement('registerTokenRequest');
        $writer->writeAttribute('reportGroup', $reportGroup);
        if ($customerId) {
            $writer->writeAttribute('customerId', $customerId);
        }
        if ($id) {
            $writer->writeAttribute('id', $id);
        }

        if ($orderId !== null) {
            $writer->writeElement('orderId', $orderId);
        }

        if ($paypageRegistrationId !== null) {
            $writer->writeElement('paypageRegistrationId', $paypageRegistrationId);
        } elseif ($accountNumber !== null) {
            $writer->writeElement('accountNumber', $accountNumber);
            if ($cardValidationNum !== null) {
                $writer->writeElement('cardValidationNum', $cardValidationNum);
            }
        } elseif ($accNum !== null) {
            $writer->startElement('echeckForToken');

            $writer->writeElement('accNum', $accNum);
            $writer->writeElement('routingNum', $routingNum);

            $writer->endElement();
        }

        $writer->endElement();
        $xml = $writer->outputMemory();

        $xml = $this->litleOnlineRequestWrapper->wrap($xml, $merchantId, $user, $password);

        return $xml;
    }
}
