<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Vantiv\Payment\Block\Adminhtml\Recurring\Subscription\Grid\Renderer;

class NextPaymentDate extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\Date
{
    /**
     * @var \Vantiv\Payment\Helper\Recurring
     */
    private $recurringHelper;

    /**
     * @param \Magento\Backend\Block\Context $context
     * @param \Magento\Framework\Stdlib\DateTime\DateTimeFormatterInterface $dateTimeFormatter
     * @param \Vantiv\Payment\Helper\Recurring $recurringHelper
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Context $context,
        \Magento\Framework\Stdlib\DateTime\DateTimeFormatterInterface $dateTimeFormatter,
        \Vantiv\Payment\Helper\Recurring $recurringHelper,
        array $data = []
    ) {
        parent::__construct($context, $dateTimeFormatter, $data);
        $this->recurringHelper = $recurringHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function _getValue(\Magento\Framework\DataObject $row)
    {
        return $row['status'] != \Vantiv\Payment\Model\Recurring\Source\SubscriptionStatus::ACTIVE
            ? '' : $this->recurringHelper->estimateNextPaymentDate(
                $row['created_at'],
                $row['start_date'],
                $row['interval'],
                $row['number_of_trial_intervals'],
                $row['trial_interval']
            );
    }
}
