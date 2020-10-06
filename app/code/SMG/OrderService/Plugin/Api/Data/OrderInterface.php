<?php

namespace SMG\OrderService\Plugin\Api\Data;

class OrderInterface{
    
	protected $helperData;
    
    public function __construct(
    	\SMG\OrderService\Helper\Data $helperData
    	) {
         $this->helperData = $helperData;
    }
    
   /**
     * @param \Magento\Sales\Api\OrderManagementInterface $orderManagementInterface
     * @param \Magento\Sales\Model\Order\Interceptor $order
     * @return $order
    */
   public function afterPlace(\Magento\Sales\Api\OrderManagementInterface $orderManagementInterface , $order)
    {
		$storeId = $order->getStoreId();
		if($storeId!=1)
		{
		 $this->helperData->postOrderService($order);
	    }
	    return $order;
    }
	
}
