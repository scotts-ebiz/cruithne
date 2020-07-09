<?php
/**
 * Created by PhpStorm.
 * User: nvanhoose
 * Date: 3/4/20
 * Time: 1:59 PM
 */

namespace SMG\Api\Api\Model;


/**
 * Class OrderResponse
 * @package SMG\Api\Api\Model
 */
class OrderResponse
{
    // Output JSON file constants
    /**
     * @var string
     */
    protected $_orderNumber;
    /**
     * @var string
     */
    protected $_subscriptionOrder;
    /**
     * @var string
     */
    protected $_subscriptionType;
    /**
     * @var string
     */
    protected $_datePlaced;
    /**
     * @var string
     */
    protected $_sapDeliveryDate;
    /**
     * @var string
     */
    protected $_customerName;
    /**
     * @var string
     */
    protected $_addressStreet;
    /**
     * @var string
     */
    protected $_addressCity;
    /**
     * @var string
     */
    protected $_addressState;
    /**
     * @var string
     */
    protected $_addressZip;
    /**
     * @var string
     */
    protected $_smgSku;
    /**
     * @var string
     */
    protected $_webSku;
    /**
     * @var string
     */
    protected $_quantity;
    /**
     * @var string
     */
    protected $_unit;
    /**
     * @var string
     */
    protected $_unitPrice;
    /**
     * @var string
     */
    protected $_grossSales;
    /**
     * @var string
     */
    protected $_shippingAmount;
    /**
     * @var string
     */
    protected $_exemptAmount;
    /**
     * @var string
     */
    protected $_hdrDiscFixedAmount;
    /**
     * @var string
     */
    protected $_hdrDiscPerc;
    /**
     * @var string
     */
    protected $_hdrDiscCondCode;
    /**
     * @var string
     */
    protected $_hdrSurchFixedAmount;
    /**
     * @var string
     */
    protected $_hdrSurchPerc;
    /**
     * @var string
     */
    protected $_hdrSurchCondCode;
    /**
     * @var string
     */
    protected $_discountAmount;
    /**
     * @var string
     */
    protected $_subtotal;
    /**
     * @var string
     */
    protected $_taxRate;
    /**
     * @var string
     */
    protected $_salesTax;
    /**
     * @var string
     */
    protected $_invoiceAmount;
    /**
     * @var string
     */
    protected $_deliveryLocation;
    /**
     * @var string
     */
    protected $_email;
    /**
     * @var string
     */
    protected $_phone;
    /**
     * @var string
     */
    protected $_deliveryWindow;
    /**
     * @var string
     */
    protected $_shippingCondition;
    /**
     * @var string
     */
    protected $_websiteUrl;
    /**
     * @var string
     */
    protected $_creditAmount;
    /**
     * @var string
     */
    protected $_crDrReFlag;
    /**
     * @var string
     */
    protected $_sapBillingDocNumber;
    /**
     * @var string
     */
    protected $_creditComment;
    /**
     * @var string
     */
    protected $_orderReason;
    /**
     * @var string
     */
    protected $_discountConditionCode;
    /**
     * @var string
     */
    protected $_surchConditionCode;
    /**
     * @var string
     */
    protected $_discountFixedAmount;
    /**
     * @var string
     */
    protected $_surchFixedAmount;
    /**
     * @var string
     */
    protected $_discountPercentAmount;
    /**
     * @var string
     */
    protected $_surchPercentAmount;
    /**
     * @var string
     */
    protected $_discountReason;
    /**
     * @var string
     */
    protected $_subscriptionShipStart;
    /**
     * @var string
     */
    protected $_subscriptionShipEnd;

    /**
     * @return string
     */
    public function getOrderNumber()
    {
        return $this->_orderNumber;
    }

    /**
     * @param $orderNumber
     * @return null 
     */
    public function setOrderNumber($orderNumber)
    {
        $this->_orderNumber = $orderNumber;
    }

    /**
     * @return string
     */
    public function getSubscriptionOrder()
    {
        return $this->_subscriptionOrder;
    }

    /**
     * @param $subscriptionOrder
     * @return null 
     */
    public function setSubscriptionOrder($subscriptionOrder)
    {
        $this->_subscriptionOrder = $subscriptionOrder;
    }

    /**
     * @return string
     */
    public function getSubscriptionType()
    {
        return $this->_subscriptionType;
    }

    /**
     * @param $subscriptionType
     * @return null 
     */
    public function setSubscriptionType($subscriptionType)
    {
        $this->_subscriptionType = $subscriptionType;
    }

    /**
     * @return string
     */
    public function getDatePlaced()
    {
        return $this->_datePlaced;
    }

    /**
     * @param $datePlaced
     * @return null 
     */
    public function setDatePlaced($datePlaced)
    {
        $this->_datePlaced = $datePlaced;
    }

    /**
     * @return string
     */
    public function getSapDeliveryDate()
    {
        return $this->_sapDeliveryDate;
    }

    /**
     * @param $sapDeliveryDate
     * @return null 
     */
    public function setSapDeliveryDate($sapDeliveryDate)
    {
        $this->_sapDeliveryDate = $sapDeliveryDate;
    }

    /**
     * @return string
     */
    public function getCustomerName()
    {
        return $this->_customerName;
    }

    /**
     * @param $customerName
     * @return null 
     */
    public function setCustomerName($customerName)
    {
        $this->_customerName = $customerName;
    }

    /**
     * @return string
     */
    public function getAddressStreet()
    {
        return $this->_addressStreet;
    }

    /**
     * @param $addressStreet
     * @return null 
     */
    public function setAddressStreet($addressStreet)
    {
        $this->_addressStreet = $addressStreet;
    }

    /**
     * @return string
     */
    public function getAddressCity()
    {
        return $this->_addressCity;
    }

    /**
     * @param $addressCity
     * @return null 
     */
    public function setAddressCity($addressCity)
    {
        $this->_addressCity = $addressCity;
    }

    /**
     * @return string
     */
    public function getAddressState()
    {
        return $this->_addressState;
    }

    /**
     * @param $addressState
     * @return null 
     */
    public function setAddressState($addressState)
    {
        $this->_addressState = $addressState;
    }

    /**
     * @return string
     */
    public function getAddressZip()
    {
        return $this->_addressZip;
    }

    /**
     * @param $addressZip
     * @return null 
     */
    public function setAddressZip($addressZip)
    {
        $this->_addressZip = $addressZip;
    }

    /**
     * @return string
     */
    public function getSmgSku()
    {
        return $this->_smgSku;
    }

    /**
     * @param $smgSku
     * @return null 
     */
    public function setSmgSku($smgSku)
    {
        $this->_smgSku = $smgSku;
    }

    /**
     * @return string
     */
    public function getWebSku()
    {
        return $this->_webSku;
    }

    /**
     * @param $webSku
     * @return null 
     */
    public function setWebSku($webSku)
    {
        $this->_webSku = $webSku;
    }

    /**
     * @return string
     */
    public function getQuantity()
    {
        return $this->_quantity;
    }

    /**
     * @param $quantity
     * @return null 
     */
    public function setQuantity($quantity)
    {
        $this->_quantity = $quantity;
    }

    /**
     * @return string
     */
    public function getUnit()
    {
        return $this->_unit;
    }

    /**
     * @param $unit
     * @return null 
     */
    public function setUnit($unit)
    {
        $this->_unit = $unit;
    }

    /**
     * @return string
     */
    public function getUnitPrice()
    {
        return $this->_unitPrice;
    }

    /**
     * @param $unitPrice
     * @return null 
     */
    public function setUnitPrice($unitPrice)
    {
        $this->_unitPrice = $unitPrice;
    }

    /**
     * @return string
     */
    public function getGrossSales()
    {
        return $this->_grossSales;
    }

    /**
     * @param $grossSales
     * @return null 
     */
    public function setGrossSales($grossSales)
    {
        $this->_grossSales = $grossSales;
    }

    /**
     * @return string
     */
    public function getShippingAmount()
    {
        return $this->_shippingAmount;
    }

    /**
     * @param $shippingAmount
     * @return null 
     */
    public function setShippingAmount($shippingAmount)
    {
        $this->_shippingAmount = $shippingAmount;
    }

    /**
     * @return string
     */
    public function getExemptAmount()
    {
        return $this->_exemptAmount;
    }

    /**
     * @param $exemptAmount
     * @return null 
     */
    public function setExemptAmount($exemptAmount)
    {
        $this->_exemptAmount = $exemptAmount;
    }

    /**
     * @return string
     */
    public function getHdrDiscFixedAmount()
    {
        return $this->_hdrDiscFixedAmount;
    }

    /**
     * @param $hdrDiscFixedAmount
     * @return null 
     */
    public function setHdrDiscFixedAmount($hdrDiscFixedAmount)
    {
        $this->_hdrDiscFixedAmount = $hdrDiscFixedAmount;
    }

    /**
     * @return string
     */
    public function getHdrDiscPerc()
    {
        return $this->_hdrDiscPerc;
    }

    /**
     * @param $hdrDiscPerc
     * @return null 
     */
    public function setHdrDiscPerc($hdrDiscPerc)
    {
        $this->_hdrDiscPerc = $hdrDiscPerc;
    }

    /**
     * @return string
     */
    public function getHdrDiscCondCode()
    {
        return $this->_hdrDiscCondCode;
    }

    /**
     * @param $hdrDiscCondCode
     * @return null 
     */
    public function setHdrDiscCondCode($hdrDiscCondCode)
    {
        $this->_hdrDiscCondCode = $hdrDiscCondCode;
    }

    /**
     * @return string
     */
    public function getHdrSurchFixedAmount()
    {
        return $this->_hdrSurchFixedAmount;
    }

    /**
     * @param $hdrSurchFixedAmount
     * @return null 
     */
    public function setHdrSurchFixedAmount($hdrSurchFixedAmount)
    {
        $this->_hdrSurchFixedAmount = $hdrSurchFixedAmount;
    }

    /**
     * @return string
     */
    public function getHdrSurchPerc()
    {
        return $this->_hdrSurchPerc;
    }

    /**
     * @param $hdrSurchPerc
     * @return null 
     */
    public function setHdrSurchPerc($hdrSurchPerc)
    {
        $this->_hdrSurchPerc = $hdrSurchPerc;
    }

    /**
     * @return string
     */
    public function getHdrSurchCondCode()
    {
        return $this->_hdrSurchCondCode;
    }

    /**
     * @param $hdrSurchCondCode
     * @return null 
     */
    public function setHdrSurchCondCode($hdrSurchCondCode)
    {
        $this->_hdrSurchCondCode = $hdrSurchCondCode;
    }

    /**
     * @return string
     */
    public function getDiscountAmount()
    {
        return $this->_discountAmount;
    }

    /**
     * @param $discountAmount
     * @return null 
     */
    public function setDiscountAmount($discountAmount)
    {
        $this->_discountAmount = $discountAmount;
    }

    /**
     * @return string
     */
    public function getSubtotal()
    {
        return $this->_subtotal;
    }

    /**
     * @param $subtotal
     * @return null 
     */
    public function setSubtotal($subtotal)
    {
        $this->_subtotal = $subtotal;
    }

    /**
     * @return string
     */
    public function getTaxRate()
    {
        return $this->_taxRate;
    }

    /**
     * @param $taxRate
     * @return null 
     */
    public function setTaxRate($taxRate)
    {
        $this->_taxRate = $taxRate;
    }

    /**
     * @return string
     */
    public function getSalesTax()
    {
        return $this->_salesTax;
    }

    /**
     * @param $salesTax
     * @return null 
     */
    public function setSalesTax($salesTax)
    {
        $this->_salesTax = $salesTax;
    }

    /**
     * @return string
     */
    public function getInvoiceAmount()
    {
        return $this->_invoiceAmount;
    }

    /**
     * @param $invoiceAmount
     * @return null 
     */
    public function setInvoiceAmount($invoiceAmount)
    {
        $this->_invoiceAmount = $invoiceAmount;
    }

    /**
     * @return string
     */
    public function getDeliveryLocation()
    {
        return $this->_deliveryLocation;
    }

    /**
     * @param $deliveryLocation
     * @return null 
     */
    public function setDeliveryLocation($deliveryLocation)
    {
        $this->_deliveryLocation = $deliveryLocation;
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->_email;
    }

    /**
     * @param $email
     * @return null 
     */
    public function setEmail($email)
    {
        $this->_email = $email;
    }

    /**
     * @return string
     */
    public function getPhone()
    {
        return $this->_phone;
    }

    /**
     * @param $phone
     * @return null 
     */
    public function setPhone($phone)
    {
        $this->_phone = $phone;
    }

    /**
     * @return string
     */
    public function getDeliveryWindow()
    {
        return $this->_deliveryWindow;
    }

    /**
     * @param $deliveryWindow
     * @return null 
     */
    public function setDeliveryWindow($deliveryWindow)
    {
        $this->_deliveryWindow = $deliveryWindow;
    }

    /**
     * @return string
     */
    public function getShippingCondition()
    {
        return $this->_shippingCondition;
    }

    /**
     * @param $shippingCondition
     * @return null 
     */
    public function setShippingCondition($shippingCondition)
    {
        $this->_shippingCondition = $shippingCondition;
    }

    /**
     * @return string
     */
    public function getWebsiteUrl()
    {
        return $this->_websiteUrl;
    }

    /**
     * @param $websiteUrl
     * @return null 
     */
    public function setWebsiteUrl($websiteUrl)
    {
        $this->_websiteUrl = $websiteUrl;
    }

    /**
     * @return string
     */
    public function getCreditAmount()
    {
        return $this->_creditAmount;
    }

    /**
     * @param $creditAmount
     * @return null 
     */
    public function setCreditAmount($creditAmount)
    {
        $this->_creditAmount = $creditAmount;
    }

    /**
     * @return string
     */
    public function getCrDrReFlag()
    {
        return $this->_crDrReFlag;
    }

    /**
     * @param $crDrReFlag
     * @return null 
     */
    public function setCrDrReFlag($crDrReFlag)
    {
        $this->_crDrReFlag = $crDrReFlag;
    }

    /**
     * @return string
     */
    public function getSapBillingDocNumber()
    {
        return $this->_sapBillingDocNumber;
    }

    /**
     * @param $sapBillingDocNumber
     * @return null 
     */
    public function setSapBillingDocNumber($sapBillingDocNumber)
    {
        $this->_sapBillingDocNumber = $sapBillingDocNumber;
    }

    /**
     * @return string
     */
    public function getCreditComment()
    {
        return $this->_creditComment;
    }

    /**
     * @param $creditComment
     * @return null 
     */
    public function setCreditComment($creditComment)
    {
        $this->_creditComment = $creditComment;
    }

    /**
     * @return string
     */
    public function getOrderReason()
    {
        return $this->_orderReason;
    }

    /**
     * @param $orderReason
     * @return null 
     */
    public function setOrderReason($orderReason)
    {
        $this->_orderReason = $orderReason;
    }

    /**
     * @return string
     */
    public function getDiscountConditionCode()
    {
        return $this->_discountConditionCode;
    }

    /**
     * @param $discountConditionCode
     * @return null 
     */
    public function setDiscountConditionCode($discountConditionCode)
    {
        $this->_discountConditionCode = $discountConditionCode;
    }

    /**
     * @return string
     */
    public function getSurchConditionCode()
    {
        return $this->_surchConditionCode;
    }

    /**
     * @param $surchConditionCode
     * @return null 
     */
    public function setSurchConditionCode($surchConditionCode)
    {
        $this->_surchConditionCode = $surchConditionCode;
    }

    /**
     * @return string
     */
    public function getDiscountFixedAmount()
    {
        return $this->_discountFixedAmount;
    }

    /**
     * @param $discountFixedAmount
     * @return null 
     */
    public function setDiscountFixedAmount($discountFixedAmount)
    {
        $this->_discountFixedAmount = $discountFixedAmount;
    }

    /**
     * @return string
     */
    public function getSurchFixedAmount()
    {
        return $this->_surchFixedAmount;
    }

    /**
     * @param $surchFixedAmount
     * @return null 
     */
    public function setSurchFixedAmount($surchFixedAmount)
    {
        $this->_surchFixedAmount = $surchFixedAmount;
    }

    /**
     * @return string
     */
    public function getDiscountPercentAmount()
    {
        return $this->_discountPercentAmount;
    }

    /**
     * @param $discountPercentAmount
     * @return null 
     */
    public function setDiscountPercentAmount($discountPercentAmount)
    {
        $this->_discountPercentAmount = $discountPercentAmount;
    }

    /**
     * @return string
     */
    public function getSurchPercentAmount()
    {
        return $this->_surchPercentAmount;
    }

    /**
     * @param $surchPercentAmount
     * @return null 
     */
    public function setSurchPercentAmount($surchPercentAmount)
    {
        $this->_surchPercentAmount = $surchPercentAmount;
    }

    /**
     * @return string
     */
    public function getDiscountReason()
    {
        return $this->_discountReason;
    }

    /**
     * @param $discountReason
     * @return null 
     */
    public function setDiscountReason($discountReason)
    {
        $this->_discountReason = $discountReason;
    }

    /**
     * @return string
     */
    public function getSubscriptionShipStart()
    {
        return $this->_subscriptionShipStart;
    }

    /**
     * @param $subscriptionShipStart
     * @return null 
     */
    public function setSubscriptionShipStart($subscriptionShipStart)
    {
        $this->_subscriptionShipStart = $subscriptionShipStart;
    }

    /**
     * @return string
     */
    public function getSubscriptionShipEnd()
    {
        return $this->_subscriptionShipEnd;
    }

    /**
     * @param $subscriptionShipEnd
     * @return null 
     */
    public function setSubscriptionShipEnd($subscriptionShipEnd)
    {
        $this->_subscriptionShipEnd = $subscriptionShipEnd;
    }
}