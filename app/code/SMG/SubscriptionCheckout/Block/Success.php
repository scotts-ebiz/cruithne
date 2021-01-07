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
    protected $_helper;

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
        OrderInterface $orderInterface,
        \SMG\BackendService\Helper\Data $helper
    ) {
        parent::__construct($context, $data);

        $this->_storeManager = $storeManager;
        $this->_customerSession = $customerSession;
        $this->_coreSession = $coreSession;
        $this->_orderInterface = $orderInterface;
        $this->_helper = $helper;
    }

    /**
     * @return string
    */
    public function getOrderId(){
        
         $this->_coreSession->start();
         $incrementId = $this->_coreSession->getData('order_id');
         $backendServiceStatus = $this->_helper->getStatus();
         if(!empty($incrementId) && $backendServiceStatus == 1){
            $order = $this->_orderInterface->loadByIncrementId($incrementId);
            $orderId = $order->getMasterSubscriptionId();
         }
         else
         {
            $orderId = $incrementId;
         }
         
        return $orderId;
    }
}
