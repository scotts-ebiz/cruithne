<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Vantiv\Payment\Ui\Component\Listing\Column;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

class RecoveryTransactionActions extends Column
{
    /**
     * Url paths
     */
    const URL_PATH_VIEW_SUBSCRIPTION = 'vantiv/recurring_subscription/view';
    const URL_PATH_CANCEL = 'vantiv/recurring_recovery/cancel';

    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    /**
     * @var \Magento\Framework\AuthorizationInterface
     */
    protected $authorization;

    /**
     * Constructor
     *
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param UrlInterface $urlBuilder
     * @param \Magento\Framework\AuthorizationInterface $authorization
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        UrlInterface $urlBuilder,
        \Magento\Framework\AuthorizationInterface $authorization,
        array $components = [],
        array $data = []
    ) {
        $this->urlBuilder = $urlBuilder;
        $this->authorization = $authorization;
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
            foreach ($dataSource['data']['items'] as & $item) {
                if (isset($item['subscription_id'])) {
                    if ($this->authorization->isAllowed('Vantiv_Payment::subscriptions_actions_view')) {
                        $item[$this->getData('name')] = [
                            'view' => [
                                'href' => $this->urlBuilder->getUrl(
                                    static::URL_PATH_VIEW_SUBSCRIPTION,
                                    [
                                        'subscription_id' => $item['subscription_id']
                                    ]
                                ),
                                'label' => __('View Subscription'),
                            ]
                        ];
                    }

                    if ($this->authorization->isAllowed('Vantiv_Payment::recovery_transactions_actions_cancel')
                        && isset($item['status'])
                        && $item['status'] == \Vantiv\Payment\Model\Recurring\Source\RecoveryTransactionStatus::DECLINED
                    ) {
                        $item[$this->getData('name')]['cancel'] = [
                            'href' => $this->urlBuilder->getUrl(
                                static::URL_PATH_CANCEL,
                                [
                                    'entity_id' => $item['entity_id']
                                ]
                            ),
                            'label' => __('Cancel'),
                            'confirm' => [
                                'title' => __('Cancel recovery transaction "${ $.$data.litle_txn_id }"'),
                                'message' => __('Are you sure you want to cancel recovery transaction with id "${ $.$data.litle_txn_id }"?')
                            ]
                        ];
                    }
                }
            }
        }

        return $dataSource;
    }
}
