<?php
namespace SMG\OrderReason\Plugin\Api\Data;
use Magento\Framework\App\RequestInterface;
use Magento\Sales\Model\Order;
class OrderInterface{
    
    protected $_request;
    protected $_order;
    protected $_orderRepository;
    public function __construct(RequestInterface $request,
    \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
    Order $order
    )
    {        
        $this->_request = $request;
        $this->_order = $order;
        $this->_orderRepository = $orderRepository;
    }
    
    public function afterPlace(\Magento\Sales\Api\Data\OrderInterface $subject)
    {
	 $params = $this->_request->getParams();
	 //$order = $this->_orderRepository->get($subject->getId());
	 $orderItems = $subject->getItems();
	 if(!empty($params['item'])){
		 foreach ($orderItems as $quoteItem){
			$quoteItemId = $quoteItem->getQuoteItemId();
			$bundle = $quoteItem->getBuyRequest()->getBundleOption();
			if($quoteItem->getProductType() == 'bundle')
			$bundleId = $quoteItemId;       
			elseif($quoteItem->getProductType() == 'simple' && isset($bundle))
			$bundleId = $bundleId;
			else
			$bundleId = $quoteItemId;	
			if(!empty($params['item'][$bundleId]['reason_code']) && isset($bundle))
			$quoteItem->setReasonCode($params['item'][$bundleId]['reason_code']);
			elseif(!empty($params['item'][$quoteItemId]['reason_code']))
			$quoteItem->setReasonCode($params['item'][$quoteItemId]['reason_code']);
		 }
	  }

	  return $subject;
   }
}
