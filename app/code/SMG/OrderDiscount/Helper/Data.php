<?php
namespace SMG\OrderDiscount\Helper;
use \Magento\Framework\App\Helper\AbstractHelper;
use SMG\OrderDiscount\Model\OrderCustomDiscountFactory;
use \Magento\Sales\Model\OrderRepository;
class Data extends AbstractHelper
{
	protected $coupon;
    protected $saleRule; 
	protected $_customDiscount;
    protected $_timezone;
    protected $_catalogrule;
    protected $_order;

	public function __construct(
	\Magento\SalesRule\Model\Coupon $coupon,
	\Magento\SalesRule\Model\Rule $saleRule,
	\Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
    \Magento\CatalogRule\Model\ResourceModel\Rule $catalogrule,
    OrderRepository $orderRepository,
	OrderCustomDiscountFactory $customDiscount
	){ 
		 $this->coupon = $coupon;
		 $this->saleRule = $saleRule;
		 $this->_customDiscount = $customDiscount;
		 $this->_timezone = $timezone;
         $this->_catalogrule = $catalogrule;
         $this->_order = $orderRepository;
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

	public function CatalogCode($orderId, $item)
	{
      $data = [];
      $discountType = '';
	  $data['disc_fixed_amount'] = '';
	  $data['disc_percent_amount'] = '';
	  $data['disc_condition_code'] = '';
      $orderData = $this->_order->get($orderId);
      $customerId = $orderData->getCustomerId();
      $customerGroupId = $orderData->getCustomerGroupId();
	  $orderItems = $orderData->getAllItems();
	  $ruleData = '';
	 
		 $sort_order = [];
		 $productId = $item->getProductId();
		 $storeId = $item->getStoreId();
		 $date = $this->_timezone->formatDate();
		 $ruleData= $this->_catalogrule->getRulesFromProduct($date,$storeId,$customerGroupId,$productId);
		 foreach ($ruleData as $key => $row)
			{
			  $sort_order[$key] = $row['sort_order'];
			}
	     array_multisort($sort_order, SORT_ASC, $ruleData);
	     if(count($sort_order) == 1)
	     {
	     	$sort_order[0] = 0;
	     } 
		 if(!empty($ruleData))
		 	{
                if($ruleData[$sort_order[0]]['action_operator'] == 'by_fixed')
               {
			    $data['disc_fixed_amount'] = $ruleData[$sort_order[0]]['action_amount'];
			    $discountType = 'FixedAmt';
		      }
		       else if($ruleData[$sort_order[0]]['action_operator'] == 'by_percent')
		      {
		       $discountType = 'PercAmt';
			   $data['disc_percent_amount'] = $ruleData[$sort_order[0]]['action_amount'];
		      }
		
		$result = $this->_customDiscount->create();
		$collection = $result->getCollection()
						 ->addFieldToSelect('disc_cond_code')
						 ->addFieldToFilter('magento_rule_type',array('eq' => 'CatalogRule'))
						 ->addFieldToFilter('application_type',array('eq' => $discountType))
						 ->getFirstItem();
		 $data['disc_condition_code'] = $collection->getDiscCondCode();
		 	}
		return $data;
	}
}
