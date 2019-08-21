<?php

namespace SMG\Minicart\Plugin\Checkout\CustomerData;

class CartTotals
{
    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $checkoutSession;
    protected $pricingHelper;

    /**
     * @param \Magento\Checkout\Model\Session $checkoutSession
     */
    public function __construct(
        \Magento\Checkout\Model\Session $checkoutSession,
         \Magento\Framework\Pricing\Helper\Data $pricingHelper) {
        $this->checkoutSession = $checkoutSession;
         $this->pricingHelper = $pricingHelper;
    }
     
   

    /**
     * Add cart grand total to result data
     *
     * @param \Magento\Checkout\CustomerData\Cart $subject
     * @param array $result
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetSectionData(\Magento\Checkout\CustomerData\Cart $subject, $result)
    {
        $totals = $this->checkoutSession->getQuote()->getTotals();
        $getGrandTotal = $totals['grand_total']->getValue();
        $finalGrandTotal = $this->pricingHelper->currency(number_format($getGrandTotal,2),true,false);
        if(isset($totals['grand_total'])) {
            $result['grand_total'] = $finalGrandTotal;
        }
        return $result;
    }
}
