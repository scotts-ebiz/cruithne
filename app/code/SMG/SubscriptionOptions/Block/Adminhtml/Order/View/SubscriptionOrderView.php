<?php
namespace SMG\SubscriptionOptions\Block\Adminhtml\Order\View;

class SubscriptionOrderView extends \Magento\Backend\Block\Template
{

    /**
     * @var \SMG\RecommendationQuiz\Helper\RecommendationQuizHelper
     */
    protected $_order;

    /**
     * SubscriptionOrderView constructor.
     * @param \Magento\Framework\View\Element\Template\Context $context
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
     * @param $orderId
     * @return \Magento\Sales\Model\Order
     */
    public function getOrderById($orderId){
        return $this->_order->load($orderId);
    }
}