<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Vantiv\Payment\Model\Recurring\Subscription;

use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\ReportingInterface;
use Magento\Framework\Api\Search\SearchCriteriaBuilder;
use Magento\Framework\App\RequestInterface;

class DataProvider extends \Magento\Framework\View\Element\UiComponent\DataProvider\DataProvider
{
    /**
     * @var \Vantiv\Payment\Helper\Recurring
     */
    private $recurringHelper;

    /**
     * DataProvider constructor.
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param ReportingInterface $reporting
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param RequestInterface $request
     * @param FilterBuilder $filterBuilder
     * @param \Vantiv\Payment\Helper\Recurring $recurringHelper
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        ReportingInterface $reporting,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        RequestInterface $request,
        FilterBuilder $filterBuilder,
        \Vantiv\Payment\Helper\Recurring $recurringHelper,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct(
            $name,
            $primaryFieldName,
            $requestFieldName,
            $reporting,
            $searchCriteriaBuilder,
            $request,
            $filterBuilder,
            $meta,
            $data
        );

        $this->recurringHelper = $recurringHelper;
    }

    /**
     * Apply all data modifiers
     *
     * @return array
     */
    public function getData()
    {
        $data = parent::getData();

        foreach ($data['items'] as & $item) {
            if ($item['status'] != \Vantiv\Payment\Model\Recurring\Source\SubscriptionStatus::ACTIVE) {
                $nextDate = null;
            } else {
                $nextDate = $this->recurringHelper->estimateNextPaymentDate(
                    $item['created_at'],
                    $item['start_date'],
                    $item['interval'],
                    $item['number_of_trial_intervals'],
                    $item['trial_interval']
                );
            }

            $item += ['next_date' => $nextDate];
        }

        return $data;
    }
}
