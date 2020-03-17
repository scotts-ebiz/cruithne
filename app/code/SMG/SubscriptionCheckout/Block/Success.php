<?php

namespace SMG\SubscriptionCheckout\Block;

use Magento\Framework\View\Element\Template;

/**
 * Class Success
 * @package SMG\SubscriptionCheckout\Block\Success
 */
class Success extends Template
{
    public $_customerSession;
    public $_storeManager;

    public function __construct(
        Template\Context $context,
        array $data = [],
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Customer\Model\Session $customerSession
    ) {
        parent::__construct($context, $data);

        $this->_storeManager = $storeManager;
        $this->_customerSession = $customerSession;
    }
}
