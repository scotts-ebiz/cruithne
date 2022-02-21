<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Vantiv\Payment\Block\Sales\Order\Email\Items\Order;

class DefaultOrderPlugin
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
     * @param \Magento\Sales\Block\Order\Email\Items\Order\DefaultOrder $subject
     * @param $result
     * @return array
     */
    public function afterGetItemOptions(\Magento\Sales\Block\Order\Email\Items\Order\DefaultOrder $subject, $result)
    {
        return array_merge(
            $this->recurringHelper->prepareOrderItemOptions($subject->getItem()),
            $result
        );
    }
}
