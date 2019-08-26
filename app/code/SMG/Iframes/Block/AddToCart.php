<?php
namespace SMG\Iframes\Block;

use \Magento\Framework\View\Element\Template;
use \Magento\Framework\View\Element\Template\Context;
use \Magento\Store\Model\StoreManagerInterface;

class AddToCart extends Template
{
  protected $_storeManager;

  public function __construct(Context $context,
    StoreManagerInterface $storeManager,
    array $data = []
  ) {
    $this->_storeManager = $storeManager;
    parent::__construct($context, $data);
  }

  public function getBaseUrl() {
    return $this->_storeManager->getStore()->getBaseUrl();
  }
}
