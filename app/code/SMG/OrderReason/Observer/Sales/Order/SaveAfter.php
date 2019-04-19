<?php
namespace SMG\OrderReason\Observer\Sales\Order;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\App\RequestInterface;
class SaveAfter implements ObserverInterface
{
    /**
     * @var RequestInterface
     */
    protected $_request;

    public function __construct(RequestInterface $request)
    {        
        $this->_request = $request;
    }

    public function execute(Observer $observer)
    {
	 $params = $this->_request->getParams();
	 $quote = $observer->getData('order');
	 $order = $observer->getData('order');
	 $orderItems = $order->getItems();
		 foreach ($orderItems as $quoteItem){
			$quoteItemid = $quoteItem->getQuoteItemId();
			$quoteItem->setReasonCode($params['item'][$quoteItemid]['reason_code']);
		 }
    }
}
