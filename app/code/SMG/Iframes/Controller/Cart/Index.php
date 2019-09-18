<?php
namespace SMG\Iframes\Controller\Cart;

use \Magento\Framework\App\Action\Action;
use \Magento\Framework\App\Action\Context;
use \Magento\Framework\View\Result\PageFactory;
use \Magento\Checkout\Model\Cart;
use \SMG\Iframes\Model\ContentSecurityPolicy;

class Index extends Action
{
  protected $_resultPageFactory;
  protected $_cart;
  protected $_contentSecurityPolicy;

  public function __construct(Context $context,
                              PageFactory $resultPageFactory,
                              Cart $cart,
                              ContentSecurityPolicy $contentSecurityPolicy) {

    $this->_resultPageFactory = $resultPageFactory;
    $this->_cart = $cart;
    $this->_contentSecurityPolicy = $contentSecurityPolicy;
    parent::__construct($context);
  }

  public function execute() {

    $this->_contentSecurityPolicy->setContentSecurityPolicy();

    $totalItems = $this->_cart->getQuote()->getItemsSummaryQty();

    $resultPage = $this->_resultPageFactory->create();
    $block = $resultPage->getLayout()
      ->getBlock('SMG_cart_index')
      ->setData( 'cartCount' , $totalItems);

    echo $block->toHtml();
  }
}
