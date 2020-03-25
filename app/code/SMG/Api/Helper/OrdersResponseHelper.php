<?php
/**
 * User: cnixon
 * Date: 5/14/19
 */
namespace SMG\Api\Helper;

use SMG\Api\Model\OrderResponse;

class OrdersResponseHelper
{
    // Output JSON file constants
    const ORDER_NUMBER = 'OrderNumber';
    const SUBSCRIPTION_ORDER = 'SubscriptOrder';
    const SUBSCRIPTION_TYPE = 'SubscriptType';
    const DATE_PLACED = 'DatePlaced';
    const SAP_DELIVERY_DATE = 'SAPDeliveryDate';
    const CUSTOMER_NAME = 'CustomerName';
    const ADDRESS_STREET = 'CustomerShippingAddressStreet';
    const ADDRESS_CITY = 'CustomerShippingAddressCity';
    const ADDRESS_STATE = 'CustomerShippingAddressState';
    const ADDRESS_ZIP = 'CustomerShippingAddressZip';
    const SMG_SKU = 'SMGSKU';
    const WEB_SKU = 'WebSKU';
    const QUANTITY = 'Quantity';
    const UNIT = 'Unit';
    const UNIT_PRICE = 'UnitPrice';
    const GROSS_SALES = 'GrossSales';
    const SHIPPING_AMOUNT = 'ShippingAmount';
    const EXEMPT_AMOUNT = 'ExemptAmount';
    const HDR_DISC_FIXED_AMOUNT = 'HdrDiscFixedAmount';
    const HDR_DISC_PERC = 'HdrDiscPerc';
    const HDR_DISC_COND_CODE = 'HdrDiscCondCode';
    const HDR_SURCH_FIXED_AMOUNT = 'HdrSurchFixedAmount';
    const HDR_SURCH_PERC = 'HdrSurchPerc';
    const HDR_SURCH_COND_CODE = 'HdrSurchCondCode';
    const DISCOUNT_AMOUNT = 'DiscountAmount';
    const SUBTOTAL = 'Subtotal';
    const TAX_RATE = 'TaxRate';
    const SALES_TAX = 'SalesTax';
    const INVOICE_AMOUNT = 'InvoiceAmount';
    const DELIVERY_LOCATION = 'DeliveryLocation';
    const EMAIL = 'CustomerEmail';
    const PHONE = 'CustomerPhone';
    const DELIVERY_WINDOW = 'DeliveryWindow';
    const SHIPPING_CONDITION = 'ShippingCondition';
    const WEBSITE_URL = 'WebsiteURL';
    const CREDIT_AMOUNT = 'CreditAmount';
    const CR_DR_RE_FLAG = 'CR/DR/RE/Flag';
    const SAP_BILLING_DOC_NUMBER = 'ReferenceDocNum';
    const CREDIT_COMMENT = 'CreditComment';
    const ORDER_REASON = 'OrderReason';
    const DISCOUNT_CONDITION_CODE = 'DiscCondCode';
    const SURCH_CONDITION_CODE = 'SurchCondCode';
    const DISCOUNT_FIXED_AMOUNT = 'DiscFixedAmt';
    const SURCH_FIXED_AMOUNT = 'SurchFixedAmt';
    const DISCOUNT_PERCENT_AMOUNT = 'DiscPercAmt';
    const SURCH_PERCENT_AMOUNT = 'SurchPercAmt';
    const DISCOUNT_REASON = 'ReasonCode';
    const SUBSCRIPTION_SHIP_START = 'SubscriptLineShipStart';
    const SUBSCRIPTION_SHIP_END = 'SubscriptLineShipEnd';

    /**
     * Takes the order and item details and puts it in an array
     *
     * @param OrderResponse $orderResponse
     * @return array
     */
    public function addRecordToOrdersArray($orderResponse)
    {
        // return
        return array_map('trim', array(
            self::ORDER_NUMBER => $orderResponse->getOrderNumber(),
            self::SUBSCRIPTION_ORDER => $orderResponse->getSubscriptionOrder(),
            self::SUBSCRIPTION_TYPE => $orderResponse->getSubscriptionType(),
            self::DATE_PLACED => $orderResponse->getDatePlaced(),
            self::SAP_DELIVERY_DATE => $orderResponse->getSapDeliveryDate(),
            self::CUSTOMER_NAME => $orderResponse->getCustomerName(),
            self::ADDRESS_STREET => $orderResponse->getAddressStreet(),
            self::ADDRESS_CITY => $orderResponse->getAddressCity(),
            self::ADDRESS_STATE => $orderResponse->getAddressState(),
            self::ADDRESS_ZIP => $orderResponse->getAddressZip(),
            self::SMG_SKU => $orderResponse->getSmgSku(),
            self::WEB_SKU => $orderResponse->getWebSku(),
            self::QUANTITY => $orderResponse->getQuantity(),
            self::UNIT => $orderResponse->getUnit(),
            self::UNIT_PRICE => $orderResponse->getUnitPrice(),
            self::GROSS_SALES => $orderResponse->getGrossSales(),
            self::SHIPPING_AMOUNT => $orderResponse->getShippingAmount(),
            self::EXEMPT_AMOUNT => $orderResponse->getExemptAmount(),
            self::HDR_DISC_FIXED_AMOUNT => $orderResponse->getHdrDiscFixedAmount(),
            self::HDR_DISC_PERC => $orderResponse->getHdrDiscPerc(),
            self::HDR_DISC_COND_CODE => $orderResponse->getHdrDiscCondCode(),
            self::HDR_SURCH_FIXED_AMOUNT => $orderResponse->getHdrSurchFixedAmount(),
            self::HDR_SURCH_PERC => $orderResponse->getHdrSurchPerc(),
            self::HDR_SURCH_COND_CODE => $orderResponse->getHdrSurchCondCode(),
            self::DISCOUNT_AMOUNT => $orderResponse->getDiscountAmount(),
            self::SUBTOTAL => $orderResponse->getSubtotal(),
            self::TAX_RATE => $orderResponse->getTaxRate(),
            self::SALES_TAX => $orderResponse->getSalesTax(),
            self::INVOICE_AMOUNT => $orderResponse->getInvoiceAmount(),
            self::DELIVERY_LOCATION => $orderResponse->getDeliveryLocation(),
            self::EMAIL => $orderResponse->getEmail(),
            self::PHONE => $orderResponse->getPhone(),
            self::DELIVERY_WINDOW => $orderResponse->getDeliveryWindow(),
            self::SHIPPING_CONDITION => $orderResponse->getShippingCondition(),
            self::WEBSITE_URL => $orderResponse->getWebsiteUrl(),
            self::CREDIT_AMOUNT => $orderResponse->getCreditAmount(),
            self::CR_DR_RE_FLAG => $orderResponse->getCrDrReFlag(),
            self::SAP_BILLING_DOC_NUMBER => $orderResponse->getSapBillingDocNumber(),
            self::CREDIT_COMMENT => $orderResponse->getCreditComment(),
            self::ORDER_REASON => $orderResponse->getOrderReason(),
            self::DISCOUNT_CONDITION_CODE => $orderResponse->getDiscountConditionCode(),
            self::SURCH_CONDITION_CODE => $orderResponse->getSurchConditionCode(),
            self::DISCOUNT_FIXED_AMOUNT => $orderResponse->getDiscountFixedAmount(),
            self::SURCH_FIXED_AMOUNT => $orderResponse->getSurchFixedAmount(),
            self::DISCOUNT_PERCENT_AMOUNT => $orderResponse->getDiscountPercentAmount(),
            self::SURCH_PERCENT_AMOUNT => $orderResponse->getSurchPercentAmount(),
            self::DISCOUNT_REASON => $orderResponse->getDiscountReason(),
            self::SUBSCRIPTION_SHIP_START => $orderResponse->getSubscriptionShipStart(),
            self::SUBSCRIPTION_SHIP_END => $orderResponse->getSubscriptionShipEnd()
        ));
    }
}
