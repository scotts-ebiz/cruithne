<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Ui\Component\Listing\Column;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Vantiv\Payment\Model\Recurring\Subscription;

/**
 * Class Price
 */
class SubscriptionDiscountAddonAmount extends SubscriptionAmount
{
    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $coreRegistry = null;

    /**
     * Constructor
     *
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Registry $registry,
        array $components = [],
        array $data = []
    ) {
        $this->coreRegistry = $registry;
        parent::__construct($context, $uiComponentFactory, $storeManager, $components, $data);
    }

    /**
     * Prepare Data Source
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        $subscription = $this->coreRegistry->registry(Subscription::REGISTRY_NAME);
        if ($subscription && isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                if (!isset($item['store_id'])) {
                    $item['store_id'] = $subscription->getStoreId();
                }
            }
        }

        return parent::prepareDataSource($dataSource);
    }
}
