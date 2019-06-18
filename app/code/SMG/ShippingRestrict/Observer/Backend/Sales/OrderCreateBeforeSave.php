<?php

namespace SMG\Shippingrestrict\Observer\Backend\Sales;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Psr\Log\LoggerInterface;
class OrderCreateBeforeSave implements ObserverInterface
{
    /**
     * @var LoggerInterface
     */
    protected $_logger;

    /**
     * @var RequestInterface
     */
    protected $_objectManager;
    protected $_productloader; 
    protected $_messageManager; 
    protected $_quoteFactory;
    protected $_url;
    protected $_responseFactory;
    protected $_itemModel;
    /**
     *
     * @param LoggerInterface $logger
     * @param RequestInterface $request
     */
    public function __construct(LoggerInterface $logger,
        \Magento\Catalog\Model\ProductFactory $_productloader,
        \Magento\Quote\Model\QuoteFactory $quoteFactory,
        \Magento\Framework\Controller\ResultFactory $resultFactory,
        \Magento\Framework\UrlInterface $url,
        \Magento\Framework\App\ResponseFactory $responseFactory,
        \Magento\Quote\Model\Quote\Item $itemModel
        )
    {
        $this->_logger = $logger;
        $this->_productloader = $_productloader;
        $this->_quoteFactory = $quoteFactory;
        $this->_url = $url;
        $this->_responseFactory = $responseFactory;
    }

    public function execute(Observer $observer)
    {
        // get the parameters from the page
        $redirectionUrl = $this->_url->getUrl('sales/order_create/index');
        $validate = false;
        $order = $observer->getEvent()->getOrder();
        $quoteId = $order->getQuoteId();
        $quote = $this->_quoteFactory->create()->load($quoteId);    
        $items = $quote->getAllItems();
    if($quote->getShippingAddress()){
        $State= $quote->getShippingAddress()->getRegion();
    foreach($items as $item) {
        $itemId = $item-> getItemId();
        $productId=$item->getProductId();
        $product=$this->_productloader->create()->load($productId);
        $productname[] = $product->getName();
        $StateNotAllowd= $product->getStateNotAllowed();
        $data = explode(',', $StateNotAllowd);  
        $option_value = array(); 
    foreach($data as $value)
   {
        $attr = $product->getResource()->getAttribute('state_not_allowed');
        $option_value[] = $attr->getSource()->getOptionText($value);
   }
    if(in_array($State, $option_value)){
        $validate = true;
    }

}
   if($validate){
   echo $message="Unfortunately one or more of the selected products is restricted from shipping to ".$State.". ";
                throw new \Magento\Framework\Exception\NoSuchEntityException(__($message));
                return $this->_responseFactory->create()->setRedirect($redirectionUrl)->sendResponse();
                die;  
            }       
        }
    }
}
