<?php
namespace SMG\Iframes\Block;

use \Magento\Framework\View\Element\Template;

class Cart extends Template
{
  public function _prepareLayout() {
    $this->pageConfig->getTitle()->set(__('Contact Us'));
    return parent::_prepareLayout();
  }
}
