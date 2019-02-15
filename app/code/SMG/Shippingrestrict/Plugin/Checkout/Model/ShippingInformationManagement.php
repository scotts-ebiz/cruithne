<?php
namespace SMG\Shippingrestrict\Plugin\Checkout\Model;

class ShippingInformationManagement
{
		protected $_checkoutSession;
		protected $_productloader; 
		protected $_messageManager; 

		public function __construct(
			\Magento\Checkout\Model\Session $checkoutSession,
			\Magento\Catalog\Model\ProductFactory $_productloader,
			\Magento\Framework\Message\ManagerInterface $messageManager					
		)
		{
			$this->_checkoutSession = $checkoutSession;
	        $this->_productloader = $_productloader;
	        $this->_messageManager = $messageManager;
		}

	public function afterSaveAddressInformation(
        \Magento\Checkout\Model\ShippingInformationManagement $shipping,
         $result
    )
    {
		$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
		$cart = $objectManager->get('\Magento\Checkout\Model\Cart'); 
		$items = $cart->getQuote()->getAllItems();
		
		$State= $this->_checkoutSession->getQuote()->getShippingAddress()->getRegion();
	
			foreach($items as $item) {
				$itemId = $item-> getItemId();
				$productId=$item->getProductId();
				$product=$this->_productloader->create()->load($productId);
				$StateNotAllowd= $product->getStateNotAllowed();
				$data = explode(',', $StateNotAllowd);	
			    $option_value = array();
			    foreach($data as $value)
			    {
                   $attr = $product->getResource()->getAttribute('state_not_allowed');
                   $option_value[] = $attr->getSource()->getOptionText($value);
			    }
                if(in_array($State, $option_value)){
				$cart->removeItem($itemId)->save(); 
                  
                $message = __('You deleted item from shopping cart');
                $response = ['success' => true];
                }

			}
		return  $result;
    }

}
