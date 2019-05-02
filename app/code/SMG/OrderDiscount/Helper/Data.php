<?php
namespace SMG\OrderDiscount\Helper;
use \Magento\Framework\App\Helper\AbstractHelper;
use SMG\OrderDiscount\Model\OrderCustomDiscountFactory;

class Data extends AbstractHelper
{
	protected $coupon;
    protected $saleRule; 
	protected $_customDiscount;
	public function __construct(
	\Magento\SalesRule\Model\Coupon $coupon,
	\Magento\SalesRule\Model\Rule $saleRule,
	OrderCustomDiscountFactory $customDiscount
	){ 
		 $this->coupon = $coupon;
		 $this->saleRule = $saleRule;
		 $this->_customDiscount = $customDiscount;
	 }
	    
	public function DiscountCode($discountcode)
	{
		/* use SMG\OrderDiscount\Helper\Data;
		 * protected $_helper;
		 * Data $helper
		 * $this->_helper = $helper;
		 * $this->_helper->DiscountCode($couponCode);*/

		$data = []; 
		$discountType = '';
		$data['hdr_disc_fixed_amount'] = '';
		$data['hdr_disc_perc'] = '';
		$data['hdr_disc_cond_code'] = '';
		$ruleId =   $this->coupon->loadByCode($discountcode)->getRuleId();
		$rule = $this->saleRule->load($ruleId);
		if($ruleId){
		$action = $rule->getSimpleAction();
	    $discount =  $rule->getDiscountAmount(); 
		if($action == 'by_fixed' || $action == 'cart_fixed')
          {
			  $data['hdr_disc_fixed_amount'] = $discount;

			  $discountType = 'FixedAmt';
		  }
		  else if($action == 'by_percent')
		  {
			  $discountType = 'PercAmt';
			  $data['hdr_disc_perc'] = $discount;
		  }
		
		$result = $this->_customDiscount->create();
		$collection = $result->getCollection()
						 ->addFieldToSelect('disc_cond_code')
						 ->addFieldToFilter('magento_rule_type',array('eq' => 'CartRule'))
						 ->addFieldToFilter('application_type',array('eq' => $discountType))
						 ->getFirstItem();
		 $data['hdr_disc_cond_code'] = $collection->getDiscCondCode();	
			}		  
		return $data;
	}
}
