<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Gateway\Common\Renderer;

use XMLWriter;

/**
 * RecurringRequest Node renderer.
 */
class RecurringRequestRenderer extends AbstractRenderer
{
    /**
     * @var CreateDiscountRenderer
     */
    private $createDiscountRenderer;

    /**
     * @var CreateAddOnRenderer
     */
    private $createAddOnRenderer;

    /**
     * @param CreateDiscountRenderer $createDiscountRenderer
     * @param CreateAddOnRenderer $createAddOnRenderer
     */
    public function __construct(
        CreateDiscountRenderer $createDiscountRenderer,
        CreateAddOnRenderer $createAddOnRenderer
    ) {
        $this->createDiscountRenderer = $createDiscountRenderer;
        $this->createAddOnRenderer = $createAddOnRenderer;
    }

    /**
     * Build <recurringRequest> XML node.
     *
     *  <recurringRequest>
     *      <subscription>
     *          <planCode>Plan Code</planCode>
     *          <numberOfPayments>Number of Payments</numberOfPayments>
     *          <startDate>Start Date (YYYY-MM-DD)</startDate>
     *          <amount>Amount</amount>
     *          <createDiscount>
     *              <discountCode>Discount Reference Code</addOnCode>
     *              <name>Name of Discount</name>
     *              <amount>Amount of Discount</amount>
     *              <startDate>Start Date of Discount</startDate>
     *              <endDate>End Date of Discount</endDate>
     *          </createDiscount>
     *          <createAddOn>
     *              <addOnCode>Add On Reference Code</addOnCode>
     *              <name>Name of Add On</name>
     *              <amount>Amount of Add On</amount>
     *              <startDate>Start Date of Add On Charge</startDate>
     *              <endDate>End Date of Add On Charge</endDate>
     *          </createAddOn>
     *     </subscription>
     * </recurringRequest>
     *
     * @param array $subject
     * @return string
     */
    public function render(array $subject)
    {
        $writer = new XMLWriter();
        $writer->openMemory();
        $writer->setIndent(true);
        $writer->setIndentString(str_repeat(' ', 4));
        $writer->startElement('recurringRequest');
        $writer->startElement('subscription');

        $this->addSimpleNode($writer, 'planCode', $subject, true);
        $this->addSimpleNode($writer, 'numberOfPayments', $subject);
        $this->addSimpleNode($writer, 'startDate', $subject);
        $this->addSimpleNode($writer, 'amount', $subject);

        if (!empty($subject['createDiscountCollection']) && is_array($subject['createDiscountCollection'])) {
            foreach ($subject['createDiscountCollection'] as $item) {
                $writer->writeRaw($this->createDiscountRenderer->render($item));
            }
        }

        if (!empty($subject['createAddOnCollection']) && is_array($subject['createAddOnCollection'])) {
            foreach ($subject['createAddOnCollection'] as $item) {
                $writer->writeRaw($this->createAddOnRenderer->render($item));
            }
        }

        $writer->endElement();
        $writer->endElement();
        $xml = $writer->outputMemory();

        return $xml;
    }
}
