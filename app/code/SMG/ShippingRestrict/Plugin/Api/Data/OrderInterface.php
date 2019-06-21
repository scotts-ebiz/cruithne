<?php
namespace SMG\ShippingRestrict\Plugin\Api\Data;
use Magento\Framework\App\RequestInterface;
use Magento\Sales\Model\Order;
use Magento\Framework\Exception\InputException;
class OrderInterface{
    
    protected $_request;
    protected $_quoteFactory;
    protected $_productloader; 
    protected $_responseFactory;
    protected $_url;
    
    public function __construct(RequestInterface $request,
    \Magento\Quote\Model\QuoteFactory $quoteFactory,
    \Magento\Catalog\Model\ProductFactory $productFactory,
    \Magento\Framework\App\ResponseFactory $responseFactory,
    \Magento\Framework\UrlInterface $url,
    Order $order
    )
    {        
        $this->_request = $request;
        $this->_quoteFactory = $quoteFactory;
        $this->_productloader = $productFactory;
        $this->_url = $url;
	    $this->_responseFactory = $responseFactory;
    }
    
    public function beforePlace(
        \Magento\Sales\Api\Data\OrderInterface $subject
     )
    {
		$redirectionUrl = $this->_url->getUrl('sales/order_create/index');
        $validate = false;
        $quoteId = $subject->getQuoteId();
        $quote = $this->_quoteFactory->create()->load($quoteId);	
		$items = $quote->getAllItems();
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
			    echo $message="Unfortunately one or more of the selected products is restricted from shipping to ".$State.".";
                throw new InputException(__($message));
                die();
             }   
	    return $subject;
	 
    }
}
