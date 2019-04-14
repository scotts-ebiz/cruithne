<?php 
namespace SMG\OrderDiscount\Observer; 
use Magento\Framework\Event\ObserverInterface; 
 
class Observer implements ObserverInterface { 
 
    protected $connector;
    protected $coupon;
    protected $saleRule; 
    
    public function __construct(
    \Magento\SalesRule\Model\Coupon $coupon,
    \Magento\SalesRule\Model\Rule $saleRule
    ){ 
		 $this->coupon = $coupon;
         $this->saleRule = $saleRule;
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance(); 
     }
 
    public function execute(\Magento\Framework\Event\Observer $observer) { 
        $order = $observer->getEvent()->getOrder();
        $customerId = $order->getCustomerId();
        $couponCode = $order->getCouponCode();
        if($couponCode)
        {
			$ruleId =   $this->coupon->loadByCode($couponCode)->getRuleId();
            $rule = $this->saleRule->load($ruleId);
            $action = $rule->getSimpleAction();
            $discount =  $rule->getDiscountAmount(); 
            if($action == 'by_fixed' || $action == 'cart_fixed')
            {
				$order->setData('hdr_disc_fixed_amount', $discount);
			}
			else if($action == 'by_percent')
			{
				$order->setData('hdr_disc_perc', $discount); 
			}
			$order->setData('hdr_disc_cond_code', $couponCode);
			$order->save();
		}
    }
}
