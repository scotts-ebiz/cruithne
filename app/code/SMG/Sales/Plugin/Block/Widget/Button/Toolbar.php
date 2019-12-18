<?php
namespace SMG\Sales\Plugin\Block\Widget\Button;

use Magento\Backend\Block\Widget\Button\Toolbar as ToolbarContext;
use Magento\Framework\View\Element\AbstractBlock;
use Magento\Backend\Block\Widget\Button\ButtonList;

class Toolbar
{
    public function beforePushButtons(
        ToolbarContext $toolbar,
        \Magento\Framework\View\Element\AbstractBlock $context,
        \Magento\Backend\Block\Widget\Button\ButtonList $buttonList
    ) {
        if (!$context instanceof \Magento\Sales\Block\Adminhtml\Order\View) {
            return [$context, $buttonList];
        }

        $order = $context->getOrder();

        if (!empty($order['master_subscription_id']) || !empty($order['subscription_id'])):      
            $buttonList->remove('order_cancel');
        endif;

        return [$context, $buttonList];
    }
}