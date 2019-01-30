<?php
namespace SMG\Dtm\Block;
class Cartdtm extends \Magento\Framework\View\Element\Template
{
	public function _prepareLayout()
    {
        return parent::_prepareLayout();
    }
    
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    public function cartitem()
    {
        $cart_object     = \Magento\Framework\App\ObjectManager::getInstance();
        $cart            = $cart_object->create('Magento\Checkout\Model\Cart')->getQuote();
        return $cart;
    }
    
    

}