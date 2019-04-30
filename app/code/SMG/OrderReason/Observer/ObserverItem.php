<?php
namespace SMG\OrderReason\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\App\RequestInterface;
class ObserverItem implements ObserverInterface{
    /**
     * @var RequestInterface
     */
    protected $_request;

    public function __construct(RequestInterface $request)
    {        
        $this->_request = $request;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
	 $params = $this->_request->getParams();
	 $quote = $observer->getData('order');
	 $order = $observer->getData('order');
	 $orderItems = $order->getItems();
	 if(!empty($params['item'])){
		 foreach ($orderItems as $quoteItem){
			$quoteItemid = $quoteItem->getQuoteItemId();
			if(!empty($params['item'][$quoteItemid]['reason_code']))
			$quoteItem->setReasonCode($params['item'][$quoteItemid]['reason_code']);
		 }
	 }
    }
}
