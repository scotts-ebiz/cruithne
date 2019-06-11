<?php
namespace SMG\ShippingRestrict\Plugin\Checkout\Model;

use Magento\Framework\Exception\InputException;

class ShippingInformationManagement
{
    protected $_checkoutSession;
    protected $_productloader;
    protected $_messageManager;
    protected $_cart;
    protected $_quoteRepository;

    public function __construct(
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Catalog\Model\ProductFactory $_productloader,
        \Magento\Framework\UrlInterface $urlInterface,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Checkout\Model\Cart $cart,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
    ) {
        $this->_checkoutSession = $checkoutSession;
        $this->_productloader = $_productloader;
        $this->_urlInterface = $urlInterface;
        $this->_messageManager = $messageManager;
        $this->_cart = $cart;
        $this->_quoteRepository = $quoteRepository;
    }

    public function afterSaveAddressInformation(
        \Magento\Checkout\Model\ShippingInformationManagement $shipping,
         $result
    ) {
        $items = $this->_cart->getQuote()->getAllItems();
        $validate = false;
        $State= $this->_checkoutSession->getQuote()->getShippingAddress()->getRegion();

        foreach ($items as $item) {
            $itemId = $item->getItemId();
            $productId=$item->getProductId();
            $product=$this->_productloader->create()->load($productId);
            $productname[] = $product->getName();
            $StateNotAllowd= $product->getStateNotAllowed();
            $data = empty($StateNotAllowd) ? [] : explode(',', $StateNotAllowd);
            $option_value = [];
            foreach ($data as $value) {
                $attr = $product->getResource()->getAttribute('state_not_allowed');
                $option_value[] = $attr->getSource()->getOptionText($value);
            }
            if (in_array($State, $option_value)) {
                $validate = true;
                $this->_cart->removeItem($itemId)->save();
            }
        }
        if ($validate) {
            $quoteId = $this->_checkoutSession->getQuote()->getId();
            $quote = $this->_quoteRepository->get($quoteId);
            $this->_quoteRepository->save($quote);
            $homepage = $this->_urlInterface->getBaseUrl();
            $checkout = $this->_urlInterface->getUrl('checkout/cart', ['_secure' => true]);
            $message="Unfortunately one or more of the selected products is restricted from shipping to " . $State . ". 
The item has been removed from the cart.";
            throw new InputException(__($message));
        }
        return  $result;
    }
}
