<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Vantiv\Payment\Block\Adminhtml\Sales\Items\Column;

class NamePlugin
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
     * @param \Magento\Sales\Block\Adminhtml\Items\Column\Name $subject
     * @param $result
     * @return array
     */
    public function afterGetOrderOptions(\Magento\Sales\Block\Adminhtml\Items\Column\Name $subject, $result)
    {
        if ($subject->getItem() instanceof \Magento\Sales\Model\Order\Item) {
            $result = array_merge($this->recurringHelper->prepareOrderItemOptions($subject->getItem()), $result);
        }

        return $result;
    }
}
