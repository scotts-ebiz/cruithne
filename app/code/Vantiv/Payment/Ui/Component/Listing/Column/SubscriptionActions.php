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

class SubscriptionActions extends Column
{
    /**
     * Url paths
     */
    const URL_PATH_VIEW = 'vantiv/recurring_subscription/view';
    const URL_PATH_CANCEL = 'vantiv/recurring_subscription/cancel';
    const URL_PATH_EDIT = 'vantiv/recurring_subscription/edit';

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
                    $item[$this->getData('name')] = [
                        'view' => [
                            'href' => $this->urlBuilder->getUrl(
                                static::URL_PATH_VIEW,
                                [
                                    'subscription_id' => $item['subscription_id']
                                ]
                            ),
                            'label' => __('View'),
                        ]
                    ];

                    if ($this->authorization->isAllowed('Vantiv_Payment::subscriptions_actions_edit')
                        && isset($item['status'])
                        && $item['status'] != \Vantiv\Payment\Model\Recurring\Source\SubscriptionStatus::CANCELLED
                    ) {
                        $item[$this->getData('name')]['edit'] = [
                            'href' => $this->urlBuilder->getUrl(
                                static::URL_PATH_EDIT,
                                [
                                    'subscription_id' => $item['subscription_id'],
                                ]
                            ),
                            'label' => __('Edit'),
                        ];

                        $item[$this->getData('name')]['cancel'] = [
                            'href' => $this->urlBuilder->getUrl(
                                static::URL_PATH_CANCEL,
                                [
                                    'subscription_id' => $item['subscription_id'],
                                    'from_grid' => '1'
                                ]
                            ),
                            'label' => __('Cancel'),
                            'confirm' => [
                                'title' => __('Cancel subscription "${ $.$data.vantiv_subscription_id }"'),
                                'message' => __('Are you sure you want to cancel subscription with id "${ $.$data.vantiv_subscription_id }"?')
                            ]
                        ];
                    }
                }
            }
        }

        return $dataSource;
    }
}
