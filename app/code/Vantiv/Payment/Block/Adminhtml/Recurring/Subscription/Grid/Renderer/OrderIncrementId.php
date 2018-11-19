<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Vantiv\Payment\Block\Adminhtml\Recurring\Subscription\Grid\Renderer;

class OrderIncrementId extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer
{
    /**
     * @inheritdoc
     */
    public function render(\Magento\Framework\DataObject $row)
    {
        if ($this->_authorization->isAllowed('Magento_Sales::actions_view') && $row->getOriginalOrderId()) {
            return sprintf(
                '<a href="%s" class="order-link"><span>%s</span></a>',
                $this->getUrl('sales/order/view', ['order_id' => $row->getOriginalOrderId()]),
                $this->escapeHtml($row->getOriginalOrderIncrementId())
            );
        }

        return $this->escapeHtml($row->getOriginalOrderIncrementId());
    }
}
