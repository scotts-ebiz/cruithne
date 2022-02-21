<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Gateway\Common\Builder;

use XMLWriter;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\Stdlib\DateTime;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Vault\Api\PaymentTokenManagementInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Vantiv\Payment\Gateway\Common\SubjectReader;

/**
 * Vantiv <enhancedData> XML document builder
 *
 * @api
 */
class EnhancedDataBuilder implements RequestBuilderInterface
{
    /**
     * Max items count
     *
     * @var int
     */
    const MAX_ITEMS_COUNT = 99;

    /**
     * Max product code length
     *
     * @var int
     */
    const MAX_PRODUCT_CODE_LENGTH = 12;

    /**
     * Max product name length
     *
     * @var int
     */
    const MAX_PRODUCT_NAME_LENGTH = 26;

    /**
     * Delivery types
     *
     * CNC - Cash and Carry
     * DIG - Digital Delivery
     * PHY - Physical Delivery
     * SVC - Service Delivery
     * TBD - To be determined (default)
     *
     * @var array
     */
    const DELIVERY_TYPES = ['CNC' => 'CNC', 'DIG' => 'DIG', 'PHY' => 'PHY', 'SVC' => 'SVC', 'TBD' => 'TBD'];

    /**
     * Unit of measure
     *
     * @var string
     */
    const UNIT_OF_MEASURE = 'EACH';

    /**
     * Allowed credit card types
     *
     * @var array
     */
    const ALLOWED_CREDIT_CARD_TYPES = ['VI', 'MC'];

    /**
     * Subject reader
     *
     * @var SubjectReader
     */
    private $reader = null;

    /**
     * Scope config
     *
     * @var ScopeConfigInterface
     */
    private $scopeConfig = null;

    /**
     * Date object
     *
     * @var DateTime
     */
    private $date = null;

    /**
     * Timezone date object
     *
     * @var TimezoneInterface
     */
    private $localeDate = null;

    /**
     * Token manager.
     *
     * @var PaymentTokenManagementInterface
     */
    private $tokenManager = null;

    /**
     * Json decoder
     *
     * @var Json
     */
    private $json;

    /**
     * Constructor
     *
     * @param SubjectReader $reader
     * @param ScopeConfigInterface $scopeConfig
     * @param DateTime $date
     * @param TimezoneInterface $localeDate
     * @param PaymentTokenManagementInterface $tokenManager
     * @param Json $json
     */
    public function __construct(
        SubjectReader $reader,
        ScopeConfigInterface $scopeConfig,
        DateTime $date,
        TimezoneInterface $localeDate,
        PaymentTokenManagementInterface $tokenManager,
        Json $json
    ) {
        $this->reader = $reader;
        $this->scopeConfig = $scopeConfig;
        $this->date = $date;
        $this->localeDate = $localeDate;
        $this->tokenManager = $tokenManager;
        $this->json = $json;
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
    public function build(array $subject)
    {
        $xml = '';
        $payment = $this->reader->readPayment($subject);
        $method = $payment->getMethodInstance();
        $order = $payment->getOrder();
        $shippingAddress = $this->reader->readOrderAdapter($subject)->getShippingAddress();
        $shipFromPostalCode = $this->scopeConfig->getValue(
            'general/store_information/postcode',
            ScopeInterface::SCOPE_STORE,
            $order->getStoreId()
        );
        $customerId = $order->getCustomerId();

        $publicHash = $payment->getAdditionalInformation(PaymentTokenInterface::PUBLIC_HASH);
        $paymentToken = $this->tokenManager->getByPublicHash($publicHash, $customerId);

        if ($paymentToken) {
            $paymentTokenDetails = $this->json->unserialize($paymentToken->getTokenDetails());
            $ccType = $paymentTokenDetails['ccType'];
        } else {
            $ccType = $method->getInfoInstance()->getCcType();
        }

        if (in_array($ccType, self::ALLOWED_CREDIT_CARD_TYPES) && $order->getTaxAmount() > 0) {
            $writer = new XMLWriter();
            $writer->openMemory();
            $writer->setIndent(true);
            $writer->setIndentString(str_repeat(' ', 4));
            $writer->startElement('enhancedData');

            /**
             * Common data
             */
            if ($order->getCustomerId()) {
                $writer->startElement('customerReference');
                $writer->text($order->getCustomerId());
                $writer->endElement();
            }

            $writer->startElement('salesTax');
            $writer->text($order->getBaseTaxAmount() * 100);
            $writer->endElement();

            $writer->startElement('deliveryType');
            $writer->text(self::DELIVERY_TYPES['TBD']);
            $writer->endElement();

            $writer->startElement('taxExempt');
            $writer->text('false');
            $writer->endElement();

            $writer->startElement('discountAmount');
            $writer->text($order->getBaseDiscountAmount() * 100);
            $writer->endElement();

            $writer->startElement('shippingAmount');
            $writer->text($order->getBaseShippingAmount() * 100);
            $writer->endElement();

            $writer->startElement('dutyAmount');
            $writer->text(0);
            $writer->endElement();

            if ($shipFromPostalCode) {
                $writer->startElement('shipFromPostalCode');
                $writer->text($shipFromPostalCode);
                $writer->endElement();
            }

            if ($shippingAddress) {
                $writer->startElement('destinationPostalCode');
                $writer->text($shippingAddress->getPostcode());
                $writer->endElement();

                $writer->startElement('destinationCountryCode');
                $writer->text($shippingAddress->getCountryId());
                $writer->endElement();
            }

            if ($order->getCreatedAt()) {
                $orderDate = $this->date->formatDate(
                    $this->localeDate->scopeDate(
                        $order->getStore(),
                        $order->getCreatedAt(),
                        true
                    ),
                    false
                );
                $writer->startElement('orderDate');
                $writer->text($orderDate);
                $writer->endElement();
            }

            /**
             * Items data
             */
            if (count($order->getItems()) <= self::MAX_ITEMS_COUNT) {
                $i = 1;
                foreach ($order->getItems() as $item) {
                    $writer->startElement('lineItemData');
                    {
                        $writer->startElement('itemSequenceNumber');
                        $writer->text($i);
                        $writer->endElement();

                        $writer->startElement('itemDescription');
                        $writer->text(substr($item->getName(), 0, self::MAX_PRODUCT_NAME_LENGTH));
                        $writer->endElement();

                        $writer->startElement('productCode');
                        $writer->text(substr($item->getSku(), 0, self::MAX_PRODUCT_CODE_LENGTH));
                        $writer->endElement();

                        $writer->startElement('quantity');
                        $writer->text($item->getQtyOrdered());
                        $writer->endElement();

                        $writer->startElement('unitOfMeasure');
                        $writer->text(self::UNIT_OF_MEASURE);
                        $writer->endElement();

                        $writer->startElement('taxAmount');
                        $writer->text($item->getBaseTaxAmount() * 100);
                        $writer->endElement();

                        $writer->startElement('lineItemTotal');
                        $writer->text($item->getBaseRowTotal() * 100);
                        $writer->endElement();

                        $writer->startElement('lineItemTotalWithTax');
                        $writer->text($item->getBaseRowTotalInclTax() * 100);
                        $writer->endElement();

                        $writer->startElement('itemDiscountAmount');
                        $writer->text($item->getBaseDiscountAmount() * 100);
                        $writer->endElement();

                        $writer->startElement('unitCost');
                        $writer->text($item->getPrice());
                        $writer->endElement();
                    }
                    $writer->endElement();
                    $i++;
                }
            }
            $writer->endElement();
            $xml = $writer->outputMemory();
        }

        return $xml;
    }

    public function extract(array $subject)
    {
        $payment = $this->reader->readPayment($subject);
        $method = $payment->getMethodInstance();
        $order = $payment->getOrder();
        $shippingAddress = $this->reader->readOrderAdapter($subject)->getShippingAddress();
        $shipFromPostalCode = $this->scopeConfig->getValue(
            'general/store_information/postcode',
            ScopeInterface::SCOPE_STORE,
            $order->getStoreId()
        );
        $customerId = $order->getCustomerId();

        $publicHash = $payment->getAdditionalInformation(PaymentTokenInterface::PUBLIC_HASH);
        $paymentToken = $this->tokenManager->getByPublicHash($publicHash, $customerId);

        if ($paymentToken) {
            $paymentTokenDetails = $this->json->unserialize($paymentToken->getTokenDetails());
            $ccType = $paymentTokenDetails['ccType'];
        } else {
            $ccType = $method->getInfoInstance()->getCcType();
        }

        if (in_array($ccType, self::ALLOWED_CREDIT_CARD_TYPES) && $order->getTaxAmount() > 0) {
            $data = [
                'customerReference' => $order->getCustomerId(),
                'salesTax' => $order->getBaseTaxAmount() * 100,
                'deliveryType' => self::DELIVERY_TYPES['TBD'],
                'taxExempt' => 'false',
                'discountAmount' => $order->getBaseDiscountAmount() * 100,
                'shippingAmount' => $order->getBaseShippingAmount() * 100,
                'dutyAmount' => 0,
            ];

            if ($shipFromPostalCode) {
                $data['shipFromPostalCode'] = $shipFromPostalCode;
            }
            if ($shippingAddress) {
                $data['destinationPostalCode'] = $shippingAddress->getPostcode();
                $data['destinationCountryCode'] = $shippingAddress->getCountryId();
            }

            if ($order->getCreatedAt()) {
                $orderDate = $this->date->formatDate(
                    $this->localeDate->scopeDate(
                        $order->getStore(),
                        $order->getCreatedAt(),
                        true
                    ),
                    false
                );
                $data['orderDate'] = $orderDate;
            }

            /**
             * Items data
             */
            $lineItemData = [];
            if (count($order->getItems()) <= self::MAX_ITEMS_COUNT) {
                $i = 1;
                $itemData = [];
                foreach ($order->getItems() as $item) {
                    $itemData['itemSequenceNumber'] = $i;
                    $itemData['itemDescription'] = substr(
                        $item->getName(),
                        0,
                        self::MAX_PRODUCT_NAME_LENGTH
                    );
                    $itemData['productCode'] = substr(
                        $item->getSku(),
                        0,
                        self::MAX_PRODUCT_CODE_LENGTH
                    );
                    $itemData['quantity'] = $item->getQtyOrdered();
                    $itemData['unitOfMeasure'] = self::UNIT_OF_MEASURE;
                    $itemData['taxAmount'] = $item->getBaseTaxAmount() * 100;
                    $itemData['lineItemTotal'] = $item->getBaseRowTotal() * 100;
                    $itemData['lineItemTotalWithTax'] = $item->getBaseRowTotalInclTax() * 100;
                    $itemData['itemDiscountAmount'] = $item->getBaseDiscountAmount() * 100;
                    $itemData['unitCost'] = $item->getPrice();
                    $lineItemData[] = $itemData;
                    $i++;
                }
                $data['lineItemData'] = $lineItemData;
            }

            return ['enhancedData' => $data];
        }

        return [];
    }
}
