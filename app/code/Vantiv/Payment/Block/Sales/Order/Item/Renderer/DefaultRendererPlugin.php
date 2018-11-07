<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Vantiv\Payment\Block\Sales\Order\Item\Renderer;

class DefaultRendererPlugin
{
    /**
     * @var \Vantiv\Payment\Helper\Recurring
     */
    private $recurringHelper;

    /**
     * @param \Vantiv\Payment\Helper\Recurring $recurringHelper
     */
    public function __construct(\Vantiv\Payment\Helper\Recurring $recurringHelper)
    {
        $this->recurringHelper = $recurringHelper;
    }

    /**
     * @param \Magento\Sales\Block\Order\Item\Renderer\DefaultRenderer $subject
     * @param $result
     * @return array
     */
    public function afterGetItemOptions(\Magento\Sales\Block\Order\Item\Renderer\DefaultRenderer $subject, $result)
    {
        return array_merge(
            $this->recurringHelper->prepareOrderItemOptions($subject->getOrderItem()),
            $result
        );
    }
}
