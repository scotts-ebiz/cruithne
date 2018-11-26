<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Gateway\Common\Renderer;

use XMLWriter;

/**
 * EnhancedData Node renderer.
 */
class EnhancedDataRenderer extends AbstractRenderer
{
    /**
     * @var DetailTaxRenderer
     */
    private $detailTaxRenderer = null;

    /**
     * @param DetailTaxRenderer $detailTaxRenderer
     */
    public function __construct(
        DetailTaxRenderer $detailTaxRenderer
    ) {
        $this->detailTaxRenderer = $detailTaxRenderer;
    }

    /**
     * Build <enhancedData> XML node.
     *
     * <enhancedData>
     *     <customerReference>Customer Reference</customerReference>
     *     <salesTax>Amount of Sales Tax Included in Transaction</salesTax>
     *     <deliveryType>TBD</deliveryType>
     *     <taxExempt>true or false</taxExempt>
     *     <discountAmount>Discount Amount Applied to Order</discountAmount>
     *     <shippingAmount>Amount to Transport Order</shippingAmount>
     *     <dutyAmount>Duty on Total Purchase Amount</dutyAmount>
     *     <shipFromPostalCode>Ship From Postal Code</shipFromPostalCode>
     *     <destinationPostalCode>Ship To Postal Code</destinationPostalCode>
     *     <destinationCountryCode>Ship To ISO Country Code</destinationCountryCode>
     *     <invoiceReferenceNumber>Merchant Invoice Number</invoiceReferenceNumber>
     *     <orderDate>Date Order Placed</orderDate>
     *     <detailTax>
     *         <taxIncludedInTotal>true or false</taxIncludedInTotal>
     *         <taxAmount>Additional Tax Amount</taxAmount>
     *         <taxRate>Tax Rate of This Tax Amount</taxRate>
     *         <taxTypeIdentifier>Tax Type Enum</taxTypeIdentifier>
     *         <cardAcceptorTaxId>Tax ID of Card Acceptor</cardAcceptorTaxId>
     *     </detailTax>
     *     <lineItemData>
     *         <itemSequenceNumber>Line Item Number within Order</itemSequenceNumber>
     *         <itemDescription>Description of Item</itemDescription>
     *         <productCode>Product Code of Item</productCode>
     *         <quantity>Quantity of Item</quantity>
     *         <unitOfMeasure>Unit of Measurement Code</unitOfMeasure>
     *         <taxAmount>Sales Tax or VAT of Item</taxAmount>
     *         <lineItemTotal>Total Amount of Line Item</lineItemTotal>
     *         <lineItemTotalWithTax>taxAmount + lineItemTotal</lineItemTotalWithTax>
     *         <itemDiscountAmount>Discount Amount</itemDiscountAmount>
     *         <commodityCode>Card Acceptor Commodity Code for Item</commodityCode>
     *         <unitCost>Price for One Unit of Item</unitCost>
     *     </lineItemData>
     * </enhancedData>
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
        $writer->startElement('enhancedData');

        /**
         * Common data
         */
        $this->addSimpleNode($writer, 'customerReference', $subject);
        $this->addSimpleNode($writer, 'salesTax', $subject);
        $this->addSimpleNode($writer, 'deliveryType', $subject);
        $this->addSimpleNode($writer, 'taxExempt', $subject);
        $this->addSimpleNode($writer, 'discountAmount', $subject);
        $this->addSimpleNode($writer, 'shippingAmount', $subject);
        $this->addSimpleNode($writer, 'dutyAmount', $subject);
        $this->addSimpleNode($writer, 'shipFromPostalCode', $subject);
        $this->addSimpleNode($writer, 'destinationPostalCode', $subject);
        $this->addSimpleNode($writer, 'destinationCountryCode', $subject);
        $this->addSimpleNode($writer, 'invoiceReferenceNumber', $subject);
        $this->addSimpleNode($writer, 'orderDate', $subject);

        if (!empty($subject['detailTax']) && is_array($subject['detailTax'])) {
            $writer->writeRaw($this->detailTaxRenderer->render($subject['detailTax']));
        }

        /**
         * Items data
         */
        if (!empty($subject['lineItemData']) && is_array($subject['lineItemData'])) {
            foreach ($subject['lineItemData'] as $row) {
                $writer->startElement('lineItemData');

                $this->addSimpleNode($writer, 'itemSequenceNumber', $row);
                $this->addSimpleNode($writer, 'itemDescription', $row, true);
                $this->addSimpleNode($writer, 'productCode', $row);
                $this->addSimpleNode($writer, 'quantity', $row);
                $this->addSimpleNode($writer, 'unitOfMeasure', $row);
                $this->addSimpleNode($writer, 'taxAmount', $row);
                $this->addSimpleNode($writer, 'lineItemTotal', $row);
                $this->addSimpleNode($writer, 'lineItemTotalWithTax', $row);
                $this->addSimpleNode($writer, 'itemDiscountAmount', $row);
                $this->addSimpleNode($writer, 'commodityCode', $row);
                $this->addSimpleNode($writer, 'unitCost', $row);

                $writer->endElement();
            }
        }

        $writer->endElement();
        $xml = $writer->outputMemory();

        return $xml;
    }
}
