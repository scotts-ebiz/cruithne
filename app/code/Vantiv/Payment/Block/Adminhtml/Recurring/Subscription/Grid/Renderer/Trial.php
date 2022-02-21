<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Vantiv\Payment\Block\Adminhtml\Recurring\Subscription\Grid\Renderer;

class Trial extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer
{
    /**
     * @var \Vantiv\Payment\Helper\Recurring
     */
    private $recurringHelper;

    /**
     * Trial column renderer constructor.
     * @param \Magento\Backend\Block\Context $context
     * @param \Vantiv\Payment\Helper\Recurring $recurringHelper
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Context $context,
        \Vantiv\Payment\Helper\Recurring $recurringHelper,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->recurringHelper = $recurringHelper;
    }

    /**
     * Renders grid column
     *
     * @param   \Magento\Framework\DataObject $row
     * @return  string
     */
    public function render(\Magento\Framework\DataObject $row)
    {
        return $this->recurringHelper->getSubscriptionTrialLabel($row);
    }
}
