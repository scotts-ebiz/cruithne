<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Ui\Component\Listing\Column;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

/**
 * Class Price
 */
class SubscriptionTrial extends Column
{
    /**
     * @var \Vantiv\Payment\Model\Recurring\SubscriptionFactory
     */
    private $subscriptionFactory;

    /**
     * @var \Vantiv\Payment\Helper\Recurring
     */
    private $recurringHelper;

    /**
     * Constructor
     *
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param \Vantiv\Payment\Model\Recurring\SubscriptionFactory $subscriptionFactory
     * @param \Vantiv\Payment\Helper\Recurring $recurringHelper
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        \Vantiv\Payment\Model\Recurring\SubscriptionFactory $subscriptionFactory,
        \Vantiv\Payment\Helper\Recurring $recurringHelper,
        array $components = [],
        array $data = []
    ) {
        $this->subscriptionFactory = $subscriptionFactory;
        $this->recurringHelper = $recurringHelper;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * Prepare Data Source
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            $subscription = $this->subscriptionFactory->create();
            foreach ($dataSource['data']['items'] as & $item) {
                $subscription->setData($item);
                $item[$this->getData('name')] = $this->recurringHelper->getSubscriptionTrialLabel($subscription);
            }
        }

        return $dataSource;
    }
}
