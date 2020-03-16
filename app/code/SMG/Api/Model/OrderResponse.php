<?php
/**
 * Created by PhpStorm.
 * User: nvanhoose
 * Date: 3/4/20
 * Time: 1:59 PM
 */

namespace SMG\Api\Model;


class OrderResponse
{
    // Output JSON file constants
    protected $_orderNumber;
    protected $_subscriptionOrder;
    protected $_subscriptionType;
    protected $_datePlaced;
    protected $_sapDeliveryDate;
    protected $_customerName;
    protected $_addressStreet;
    protected $_addressCity;
    protected $_addressState;
    protected $_addressZip;
    protected $_smgSku;
    protected $_webSku;
    protected $_quantity;
    protected $_unit;
    protected $_unitPrice;
    protected $_grossSales;
    protected $_shippingAmount;
    protected $_exemptAmount;
    protected $_hdrDiscFixedAmount;
    protected $_hdrDiscPerc;
    protected $_hdrDiscCondCode;
    protected $_hdrSurchFixedAmount;
    protected $_hdrSurchPerc;
    protected $_hdrSurchCondCode;
    protected $_discountAmount;
    protected $_subtotal;
    protected $_taxRate;
    protected $_salesTax;
    protected $_invoiceAmount;
    protected $_deliveryLocation;
    protected $_email;
    protected $_phone;
    protected $_deliveryWindow;
    protected $_shippingCondition;
    protected $_websiteUrl;
    protected $_creditAmount;
    protected $_crDrReFlag;
    protected $_sapBillingDocNumber;
    protected $_creditComment;
    protected $_orderReason;
    protected $_discountConditionCode;
    protected $_surchConditionCode;
    protected $_discountFixedAmount;
    protected $_surchFixedAmount;
    protected $_discountPercentAmount;
    protected $_surchPercentAmount;
    protected $_discountReason;
    protected $_subscriptionShipStart;
    protected $_subscriptionShipEnd;

    public function getOrderNumber()
    {
        return $this->_orderNumber;
    }

    public function setOrderNumber($orderNumber)
    {
        $this->_orderNumber = $orderNumber;
    }

    public function getSubscriptionOrder()
    {
        return $this->_subscriptionOrder;
    }

    public function setSubscriptionOrder($subscriptionOrder)
    {
        $this->_subscriptionOrder = $subscriptionOrder;
    }

    public function getSubscriptionType()
    {
        return $this->_subscriptionType;
    }

    public function setSubscriptionType($subscriptionType)
    {
        $this->_subscriptionType = $subscriptionType;
    }

    public function getDatePlaced()
    {
        return $this->_datePlaced;
    }

    public function setDatePlaced($datePlaced)
    {
        $this->_datePlaced = $datePlaced;
    }

    public function getSapDeliveryDate()
    {
        return $this->_sapDeliveryDate;
    }

    public function setSapDeliveryDate($sapDeliveryDate)
    {
        $this->_sapDeliveryDate = $sapDeliveryDate;
    }

    public function getCustomerName()
    {
        return $this->_customerName;
    }

    public function setCustomerName($customerName)
    {
        $this->_customerName = $customerName;
    }

    public function getAddressStreet()
    {
        return $this->_addressStreet;
    }

    public function setAddressStreet($addressStreet)
    {
        $this->_addressStreet = $addressStreet;
    }

    public function getAddressCity()
    {
        return $this->_addressCity;
    }

    public function setAddressCity($addressCity)
    {
        $this->_addressCity = $addressCity;
    }

    public function getAddressState()
    {
        return $this->_addressState;
    }

    public function setAddressState($addressState)
    {
        $this->_addressState = $addressState;
    }

    public function getAddressZip()
    {
        return $this->_addressZip;
    }

    public function setAddressZip($addressZip)
    {
        $this->_addressZip = $addressZip;
    }

    public function getSmgSku()
    {
        return $this->_smgSku;
    }

    public function setSmgSku($smgSku)
    {
        $this->_smgSku = $smgSku;
    }

    public function getWebSku()
    {
        return $this->_webSku;
    }

    public function setWebSku($webSku)
    {
        $this->_webSku = $webSku;
    }

    public function getQuantity()
    {
        return $this->_quantity;
    }

    public function setQuantity($quantity)
    {
        $this->_quantity = $quantity;
    }

    public function getUnit()
    {
        return $this->_unit;
    }

    public function setUnit($unit)
    {
        $this->_unit = $unit;
    }

    public function getUnitPrice()
    {
        return $this->_unitPrice;
    }

    public function setUnitPrice($unitPrice)
    {
        $this->_unitPrice = $unitPrice;
    }

    public function getGrossSales()
    {
        return $this->_grossSales;
    }

    public function setGrossSales($grossSales)
    {
        $this->_grossSales = $grossSales;
    }

    public function getShippingAmount()
    {
        return $this->_shippingAmount;
    }

    public function setShippingAmount($shippingAmount)
    {
        $this->_shippingAmount = $shippingAmount;
    }

    public function getExemptAmount()
    {
        return $this->_exemptAmount;
    }

    public function setExemptAmount($exemptAmount)
    {
        $this->_exemptAmount = $exemptAmount;
    }

    public function getHdrDiscFixedAmount()
    {
        return $this->_hdrDiscFixedAmount;
    }

    public function setHdrDiscFixedAmount($hdrDiscFixedAmount)
    {
        $this->_hdrDiscFixedAmount = $hdrDiscFixedAmount;
    }

    public function getHdrDiscPerc()
    {
        return $this->_hdrDiscPerc;
    }

    public function setHdrDiscPerc($hdrDiscPerc)
    {
        $this->_hdrDiscPerc = $hdrDiscPerc;
    }

    public function getHdrDiscCondCode()
    {
        return $this->_hdrDiscCondCode;
    }

    public function setHdrDiscCondCode($hdrDiscCondCode)
    {
        $this->_hdrDiscCondCode = $hdrDiscCondCode;
    }

    public function getHdrSurchFixedAmount()
    {
        return $this->_hdrSurchFixedAmount;
    }

    public function setHdrSurchFixedAmount($hdrSurchFixedAmount)
    {
        $this->_hdrSurchFixedAmount = $hdrSurchFixedAmount;
    }

    public function getHdrSurchPerc()
    {
        return $this->_hdrSurchPerc;
    }

    public function setHdrSurchPerc($hdrSurchPerc)
    {
        $this->_hdrSurchPerc = $hdrSurchPerc;
    }

    public function getHdrSurchCondCode()
    {
        return $this->_hdrSurchCondCode;
    }

    public function setHdrSurchCondCode($hdrSurchCondCode)
    {
        $this->_hdrSurchCondCode = $hdrSurchCondCode;
    }

    public function getDiscountAmount()
    {
        return $this->_discountAmount;
    }

    public function setDiscountAmount($discountAmount)
    {
        $this->_discountAmount = $discountAmount;
    }

    public function getSubtotal()
    {
        return $this->_subtotal;
    }

    public function setSubtotal($subtotal)
    {
        $this->_subtotal = $subtotal;
    }

    public function getTaxRate()
    {
        return $this->_taxRate;
    }

    public function setTaxRate($taxRate)
    {
        $this->_taxRate = $taxRate;
    }

    public function getSalesTax()
    {
        return $this->_salesTax;
    }

    public function setSalesTax($salesTax)
    {
        $this->_salesTax = $salesTax;
    }

    public function getInvoiceAmount()
    {
        return $this->_invoiceAmount;
    }

    public function setInvoiceAmount($invoiceAmount)
    {
        $this->_invoiceAmount = $invoiceAmount;
    }

    public function getDeliveryLocation()
    {
        return $this->_deliveryLocation;
    }

    public function setDeliveryLocation($deliveryLocation)
    {
        $this->_deliveryLocation = $deliveryLocation;
    }

    public function getEmail()
    {
        return $this->_email;
    }

    public function setEmail($email)
    {
        $this->_email = $email;
    }

    public function getPhone()
    {
        return $this->_phone;
    }

    public function setPhone($phone)
    {
        $this->_phone = $phone;
    }

    public function getDeliveryWindow()
    {
        return $this->_deliveryWindow;
    }

    public function setDeliveryWindow($deliveryWindow)
    {
        $this->_deliveryWindow = $deliveryWindow;
    }

    public function getShippingCondition()
    {
        return $this->_shippingCondition;
    }

    public function setShippingCondition($shippingCondition)
    {
        $this->_shippingCondition = $shippingCondition;
    }

    public function getWebsiteUrl()
    {
        return $this->_websiteUrl;
    }

    public function setWebsiteUrl($websiteUrl)
    {
        $this->_websiteUrl = $websiteUrl;
    }

    public function getCreditAmount()
    {
        return $this->_creditAmount;
    }

    public function setCreditAmount($creditAmount)
    {
        $this->_creditAmount = $creditAmount;
    }

    public function getCrDrReFlag()
    {
        return $this->_crDrReFlag;
    }

    public function setCrDrReFlag($crDrReFlag)
    {
        $this->_crDrReFlag = $crDrReFlag;
    }

    public function getSapBillingDocNumber()
    {
        return $this->_sapBillingDocNumber;
    }

    public function setSapBillingDocNumber($sapBillingDocNumber)
    {
        $this->_sapBillingDocNumber = $sapBillingDocNumber;
    }

    public function getCreditComment()
    {
        return $this->_creditComment;
    }

    public function setCreditComment($creditComment)
    {
        $this->_creditComment = $creditComment;
    }

    public function getOrderReason()
    {
        return $this->_orderReason;
    }

    public function setOrderReason($orderReason)
    {
        $this->_orderReason = $orderReason;
    }

    public function getDiscountConditionCode()
    {
        return $this->_discountConditionCode;
    }

    public function setDiscountConditionCode($discountConditionCode)
    {
        $this->_discountConditionCode = $discountConditionCode;
    }

    public function getSurchConditionCode()
    {
        return $this->_surchConditionCode;
    }

    public function setSurchConditionCode($surchConditionCode)
    {
        $this->_surchConditionCode = $surchConditionCode;
    }

    public function getDiscountFixedAmount()
    {
        return $this->_discountFixedAmount;
    }

    public function setDiscountFixedAmount($discountFixedAmount)
    {
        $this->_discountFixedAmount = $discountFixedAmount;
    }

    public function getSurchFixedAmount()
    {
        return $this->surchFixedAmount;
    }

    public function setSurchFixedAmount($surchFixedAmount)
    {
        $this->_surchFixedAmount = $surchFixedAmount;
    }

    public function getDiscountPercentAmount()
    {
        return $this->_discountPercentAmount;
    }

    public function setDiscountPercentAmount($discountPercentAmount)
    {
        $this->_discountPercentAmount = $discountPercentAmount;
    }

    public function getSurchPercentAmount()
    {
        return $this->_surchPercentAmount;
    }

    public function setSurchPercentAmount($surchPercentAmount)
    {
        $this->_surchPercentAmount = $surchPercentAmount;
    }

    public function getDiscountReason()
    {
        return $this->_discountReason;
    }

    public function setDiscountReason($discountReason)
    {
        $this->_discountReason = $discountReason;
    }

    public function getSubscriptionShipStart()
    {
        return $this->_subscriptionShipStart;
    }

    public function setSubscriptionShipStart($subscriptionShipStart)
    {
        $this->_subscriptionShipStart = $subscriptionShipStart;
    }

    public function getSubscriptionShipEnd()
    {
        return $this->_subscriptionShipEnd;
    }

    public function setSubscriptionShipEnd($subscriptionShipEnd)
    {
        $this->_subscriptionShipEnd = $subscriptionShipEnd;
    }
}