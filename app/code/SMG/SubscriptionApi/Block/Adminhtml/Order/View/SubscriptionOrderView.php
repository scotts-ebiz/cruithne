<?php
namespace SMG\SubscriptionApi\Block\Adminhtml\Order\View;

/**
 * Class SubscriptionOrderView
 * @package SMG\SubscriptionApi\Block\Adminhtml\Order\View
 * @todo Wes this needs jailed
 */
class SubscriptionOrderView extends \Magento\Backend\Block\Template
{

    /**
     * @var \Magento\Sales\Model\Order
     */
    protected $_order;

    /**
     * SubscriptionOrderView constructor.
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Sales\Model\Order $order
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Sales\Model\Order $order,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->_order = $order;
    }

    /**
     * Get Order by ID
     * @param $orderId
     * @return \Magento\Sales\Model\Order
     */
    public function getOrderById($orderId){
        return $this->_order->load($orderId);
    }
}