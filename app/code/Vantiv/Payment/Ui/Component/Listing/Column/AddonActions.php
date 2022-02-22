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

class AddonActions extends Column
{
    const URL_PATH_EDIT = 'vantiv/recurring_addon/edit';
    const URL_PATH_DELETE = 'vantiv/recurring_addon/delete';
    /**
     * @var UrlInterface
     */
    private $urlBuilder;
    /**
     * @var AuthorizationInterface
     */
    private $authorization;

    /**
     * AddonActions constructor.
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
                if (isset($item['addon_id'])) {
                    if ($this->authorization->isAllowed('Vantiv_Payment::subscriptions_actions_edit')
                        && !$item['is_system']
                    ) {
                        $item[$this->getData('name')] = [
                            'edit' => [
                                'href' => $this->urlBuilder->getUrl(
                                    static::URL_PATH_EDIT,
                                    [
                                        'addon_id' => $item['addon_id']
                                    ]
                                ),
                                'label' => __('Edit'),
                            ],
                            'delete' => [
                                'href' => $this->urlBuilder->getUrl(
                                    static::URL_PATH_DELETE,
                                    [
                                        'addon_id' => $item['addon_id']
                                    ]
                                ),
                                'label' => __('Delete'),
                                'confirm' => [
                                    'title' => __('Delete Add-On'),
                                    'message' => __('Are you sure you want to delete this add-on?'),
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
