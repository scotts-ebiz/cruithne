<?php
namespace SMG\Iframes\Block;

use \Magento\Framework\View\Element\Template;
use \Magento\Framework\View\Element\Template\Context;
use \Magento\Checkout\Model\Cart;

class HeaderCart extends Template
{

  protected $_cart;

  public function __construct(
    Context $context,
    Cart $cart)
  {
    $this->_cart = $cart;
    return parent::__construct($context);
  }

  public function cartCount()
  {
    $totalItems = $this->_cart->getQuote()->getItemsSummaryQty();

    return $totalItems;
  }
}
