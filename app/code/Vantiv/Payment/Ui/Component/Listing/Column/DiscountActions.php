<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Ui\Component\Listing\Column;

use Magento\Framework\AuthorizationInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

class DiscountActions extends Column
{
    const URL_PATH_EDIT = 'vantiv/recurring_discount/edit';
    const URL_PATH_DELETE = 'vantiv/recurring_discount/delete';
    /**
     * @var UrlInterface
     */
    private $urlBuilder;
    /**
     * @var AuthorizationInterface
     */
    private $authorization;

    /**
     * DiscountActions constructor.
     *
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param UrlInterface $urlBuilder
     * @param AuthorizationInterface $authorization
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        UrlInterface $urlBuilder,
        AuthorizationInterface $authorization,
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
                if (isset($item['discount_id'])) {
                    if ($this->authorization->isAllowed('Vantiv_Payment::subscriptions_actions_edit')
                        && !$item['is_system']
                    ) {
                        $item[$this->getData('name')] = [
                            'edit' => [
                                'href' => $this->urlBuilder->getUrl(
                                    static::URL_PATH_EDIT,
                                    [
                                        'discount_id' => $item['discount_id']
                                    ]
                                ),
                                'label' => __('Edit'),
                            ],
                            'delete' => [
                                'href' => $this->urlBuilder->getUrl(
                                    static::URL_PATH_DELETE,
                                    [
                                        'discount_id' => $item['discount_id']
                                    ]
                                ),
                                'label' => __('Delete'),
                                'confirm' => [
                                    'title' => __('Delete Discount'),
                                    'message' => __('Are you sure you want to delete this discount?'),
                                ]
                            ],
                        ];
                    }
                }
            }
        }

        return $dataSource;
    }
}
