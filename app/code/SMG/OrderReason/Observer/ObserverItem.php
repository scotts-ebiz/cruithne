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
   $orderItems = $order->getAllVisibleItems();
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
   
    }
}
