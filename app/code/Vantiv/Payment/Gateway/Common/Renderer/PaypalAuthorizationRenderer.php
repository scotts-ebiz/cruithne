<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Gateway\Common\Renderer;

use XMLWriter;

/**
 * PayPal authorization request renderer.
 */
class PaypalAuthorizationRenderer extends AbstractRenderer
{
    /**
     * Request wrapper.
     *
     * @var LitleOnlineRequestWrapper
     */
    private $litleOnlineRequestWrapper = null;

    /**
     * Billing address renderer.
     *
     * @var BillToAddressRenderer
     */
    private $billToAddressRenderer = null;

    /**
     * PayPal data renderer.
     *
     * @var PaypalRenderer
     */
    private $paypalRenderer = null;

    /**
     * Get advanced fraud checks renderer.
     *
     * @var AdvancedFraudChecksRenderer
     */
    private $advancedFraudChecksRenderer = null;

    /**
     * Constructor.
     *
     * @param LitleOnlineRequestWrapper $litleOnlineRequestWrapper
     * @param BillToAddressRenderer $billToAddressRenderer
     * @param PaypalRenderer $paypalRenderer
     * @param AdvancedFraudChecksRenderer $advancedFraudChecksRenderer
     */
    public function __construct(
        LitleOnlineRequestWrapper $litleOnlineRequestWrapper,
        BillToAddressRenderer $billToAddressRenderer,
        PaypalRenderer $paypalRenderer,
        AdvancedFraudChecksRenderer $advancedFraudChecksRenderer
    ) {
        $this->litleOnlineRequestWrapper = $litleOnlineRequestWrapper;
        $this->billToAddressRenderer = $billToAddressRenderer;
        $this->paypalRenderer = $paypalRenderer;
        $this->advancedFraudChecksRenderer = $advancedFraudChecksRenderer;
    }

    /**
     * Build <authorization> XML node.
     *
     * <authorization reportGroup="REPORT_GROUP" customerId="CUSTOMER_ID">
     *     <orderId>ORDER_INCREMENT_ID</orderId>
     *     <amount>AMOUNT</amount>
     *     <orderSource>ecommerce</orderSource>
     *     <BILLING_NODE/>
     *     <PAYPAL_NODE/>
     * </authorization>
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
        $amount = $this->readDataOrNull($subject, 'amount');
        $orderSource = $this->readDataOrNull($subject, 'orderSource');

        /*
         * Prepare children documents.
         */
        $billToAddress = $this->billToAddressRenderer->render($this->readDataOrNull($subject, 'billToAddress'));
        $paypal = $this->paypalRenderer->render($subject);
        $advancedFraudChecks = $this->advancedFraudChecksRenderer->render($subject);

        /*
         * Generate XML document.
         */
        $writer = new XMLWriter();
        $writer->openMemory();
        $writer->setIndent(true);
        $writer->setIndentString(str_repeat(' ', 4));
        $writer->startElement('authorization');
        $writer->writeAttribute('reportGroup', $reportGroup);
        if ($customerId) {
            $writer->writeAttribute('customerId', $customerId);
        }
        if ($id) {
            $writer->writeAttribute('id', $id);
        }
        {
            $writer->writeElement('orderId', $orderId);
            $writer->writeElement('amount', $amount);
            $writer->writeElement('orderSource', $orderSource);

            $writer->writeRaw($billToAddress);
            $writer->writeRaw($paypal);
            $writer->writeRaw($advancedFraudChecks);
        }
        $writer->endElement();
        $xml = $writer->outputMemory();

        $xml = $this->litleOnlineRequestWrapper->wrap($xml, $merchantId, $user, $password);

        return $xml;
    }
}
