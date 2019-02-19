<?php
namespace SMG\ShippingRestrict\Plugin\Checkout\Model;
use Magento\Framework\Exception\InputException;

class ShippingInformationManagement
{
		protected $_checkoutSession;
		protected $_productloader; 
		protected $_messageManager; 
		protected $_cart;

		public function __construct(
			\Magento\Checkout\Model\Session $checkoutSession,
			\Magento\Catalog\Model\ProductFactory $_productloader,
			\Magento\Framework\Message\ManagerInterface $messageManager,
			\Magento\Checkout\Model\Cart $cart
			)
		{
			$this->_checkoutSession = $checkoutSession;
	        $this->_productloader = $_productloader;
	        $this->_messageManager = $messageManager;
	        $this->_cart = $cart;
		}

	public function afterSaveAddressInformation(
        \Magento\Checkout\Model\ShippingInformationManagement $shipping,
         $result
    )
    {
		$items = $this->_cart->getQuote()->getAllItems();
		$validate = false;
		$State= $this->_checkoutSession->getQuote()->getShippingAddress()->getRegion();
	
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
				 $this->_cart->removeItem($itemId)->save(); 
                }

			}
			if($validate){
				$message = implode(',',$productname).' is restricted in '.$State.' the product has been removed from the cart. Please refresh page for changes';
               throw new InputException(__($message));
             }
		     return  $result;
    }

}
