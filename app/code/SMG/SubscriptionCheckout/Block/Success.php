<?php

namespace SMG\SubscriptionCheckout\Block;

use Magento\Framework\View\Element\Template;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Sales\Api\Data\OrderInterface;
/**
 * Class Success
 * @package SMG\SubscriptionCheckout\Block\Success
 */
class Success extends Template
{
    public $_customerSession;
    public $_storeManager;
    public $_orderInterface;

     /**
     * @var SessionManagerInterface
     */
    protected $_coreSession;

    public function __construct(
        Template\Context $context,
        array $data = [],
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Customer\Model\Session $customerSession,
        SessionManagerInterface $coreSession,
        OrderInterface $orderInterface
    ) {
        parent::__construct($context, $data);

        $this->_storeManager = $storeManager;
        $this->_customerSession = $customerSession;
        $this->_coreSession = $coreSession;
        $this->_orderInterface = $orderInterface;
    }

    /**
     * @return string
    */
    public function getOrderId(){
         $this->_coreSession->start();
         $incrementId = $this->_coreSession->getData('order_id');
         if(!empty($incrementId)){
            $order = $this->_orderInterface->loadByIncrementId($incrementId);
            $orderId = $order->getMasterSubscriptionId();
         }
         else
         {
             $orderId = $this->_coreSession->getData('order_id');
         }
        return $orderId;
    }
}
