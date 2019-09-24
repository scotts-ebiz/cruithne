<?php
namespace SMG\Iframes\Block;

use \Magento\Framework\View\Element\Template;
use \Magento\Framework\View\Element\Template\Context;
use \Magento\Checkout\Model\Cart;
use \SMG\Iframes\Model\ContentSecurityPolicy;

class HeaderCart extends Template
{

  protected $_cart;
  protected $_contentSecurityPolicy;

  public function __construct(
    Context $context,
    Cart $cart,
    ContentSecurityPolicy $contentSecurityPolicy)
  {
    $this->_cart = $cart;
    $this->_contentSecurityPolicy = $contentSecurityPolicy;
    $this->_contentSecurityPolicy->setContentSecurityPolicy();
    return parent::__construct($context);
  }

  public function cartCount()
  {
    $totalItems = $this->_cart->getQuote()->getItemsSummaryQty();

    return $totalItems;
  }
}
