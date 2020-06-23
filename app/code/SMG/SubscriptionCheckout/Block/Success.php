<?php

namespace SMG\SubscriptionCheckout\Block;

use Magento\Framework\View\Element\Template;
use Magento\Framework\Session\SessionManagerInterface;
/**
 * Class Success
 * @package SMG\SubscriptionCheckout\Block\Success
 */
class Success extends Template
{
    public $_customerSession;
    public $_storeManager;

     /**
     * @var SessionManagerInterface
     */
    protected $_coreSession;

    public function __construct(
        Template\Context $context,
        array $data = [],
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Customer\Model\Session $customerSession,
        SessionManagerInterface $coreSession
    ) {
        parent::__construct($context, $data);

        $this->_storeManager = $storeManager;
        $this->_customerSession = $customerSession;
        $this->_coreSession = $coreSession;
    }

    /**
     * @return string
    */
    public function getOrderId(){
         $this->_coreSession->start();
        return $this->_coreSession->getData('order_id');
    }
}
