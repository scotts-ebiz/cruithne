<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Vantiv\Payment\Block\Adminhtml\Recurring\Subscription\Grid\Renderer;

class IntervalAmount extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer
{
    /**
     * @inheritdoc
     */
    public function render(\Magento\Framework\DataObject $row)
    {
        return $row->getFormattedIntervalAmount();
    }
}
