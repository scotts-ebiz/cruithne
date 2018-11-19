<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Gateway\Common\Renderer;

use XMLWriter;

/**
 * Credit card authorization request renderer.
 */
class CcAuthorizationRenderer extends AbstractRenderer
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
     * Credit card data renderer.
     *
     * @var CcRenderer
     */
    private $ccRenderer = null;

    /**
     * Get advanced fraud checks renderer.
     *
     * @var AdvancedFraudChecksRenderer
     */
    private $advancedFraudChecksRenderer = null;

    /**
     * @var \Vantiv\Payment\Gateway\Common\Renderer\EnhancedDataRenderer
     */
    private $enhancedDataRenderer = null;

    /**
     * @var \Vantiv\Payment\Gateway\Common\Renderer\RecurringRequestRenderer
     */
    private $recurringRequestRenderer = null;

    /**
     * @param \Vantiv\Payment\Gateway\Common\Renderer\LitleOnlineRequestWrapper $litleOnlineRequestWrapper
     * @param \Vantiv\Payment\Gateway\Common\Renderer\BillToAddressRenderer $billToAddressRenderer
     * @param \Vantiv\Payment\Gateway\Common\Renderer\CcRenderer $ccRenderer
     * @param \Vantiv\Payment\Gateway\Common\Renderer\EnhancedDataRenderer $enhancedDataRenderer
     * @param \Vantiv\Payment\Gateway\Common\Renderer\RecurringRequestRenderer $recurringRequestRenderer
     * @param \Vantiv\Payment\Gateway\Common\Renderer\AdvancedFraudChecksRenderer $advancedFraudChecksRenderer
     */
    public function __construct(
        LitleOnlineRequestWrapper $litleOnlineRequestWrapper,
        BillToAddressRenderer $billToAddressRenderer,
        CcRenderer $ccRenderer,
        EnhancedDataRenderer $enhancedDataRenderer,
        RecurringRequestRenderer $recurringRequestRenderer,
        AdvancedFraudChecksRenderer $advancedFraudChecksRenderer
    ) {
        $this->litleOnlineRequestWrapper = $litleOnlineRequestWrapper;
        $this->billToAddressRenderer = $billToAddressRenderer;
        $this->ccRenderer = $ccRenderer;
        $this->enhancedDataRenderer = $enhancedDataRenderer;
        $this->recurringRequestRenderer = $recurringRequestRenderer;
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
     *     <CC_NODE/>
     * </authorization>
     *
     * @param array $subject
     * @return string
     */
    public function render(array $subject)
    {
        $reportGroup = $this->readDataOrNull($subject, 'reportGroup');
        $customerId = $this->readDataOrNull($subject, 'customerId');
        $id = $this->readDataOrNull($subject, 'id');

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

        $this->addSimpleNode($writer, 'orderId', $subject, true);
        $this->addSimpleNode($writer, 'amount', $subject, true);
        $this->addSimpleNode($writer, 'orderSource', $subject, true);

        if (isset($subject['billToAddress'])) {
            $writer->writeRaw(
                $this->billToAddressRenderer->render($subject['billToAddress'])
            );
        }
        $writer->writeRaw(
            $this->ccRenderer->render($subject)
        );

        if (isset($subject['enhancedData'])) {
            $writer->writeRaw(
                $this->enhancedDataRenderer->render($subject['enhancedData'])
            );
        }
        if (isset($subject['recurringRequest'])) {
            $writer->writeRaw(
                $this->recurringRequestRenderer->render($subject['recurringRequest'])
            );
        }
        $writer->writeRaw($this->advancedFraudChecksRenderer->render($subject));

        $writer->endElement();
        $xml = $writer->outputMemory();

        $merchantId = $this->readDataOrNull($subject, 'merchantId');
        $user = $this->readDataOrNull($subject, 'user');
        $password = $this->readDataOrNull($subject, 'password');
        $xml = $this->litleOnlineRequestWrapper->wrap($xml, $merchantId, $user, $password);

        return $xml;
    }
}
