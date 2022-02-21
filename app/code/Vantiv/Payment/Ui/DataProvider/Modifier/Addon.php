<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Vantiv\Payment\Ui\DataProvider\Modifier;

use Magento\Framework\Locale\CurrencyInterface;
use Magento\Framework\Registry;
use Magento\Store\Model\StoreManagerInterface;
use Vantiv\Payment\Model\Recurring\Subscription\Addon as AddonModel;
use Vantiv\Payment\Model\Recurring\Subscription\AddonFactory;

class Addon implements \Magento\Ui\DataProvider\Modifier\ModifierInterface
{
    /**
     * @var AddonFactory
     */
    private $addonFactory;

    /**
     * @var Registry
     */
    private $coreRegistry;

    /**
     * @var CurrencyInterface
     */
    private $currency;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @param AddonFactory $addonFactory
     * @param Registry $coreRegistry
     * @param StoreManagerInterface $storeManager
     * @param CurrencyInterface $currency
     */
    public function __construct(
        AddonFactory $addonFactory,
        Registry $coreRegistry,
        StoreManagerInterface $storeManager,
        CurrencyInterface $currency
    ) {
        $this->addonFactory = $addonFactory;
        $this->coreRegistry = $coreRegistry;
        $this->currency = $currency;
        $this->storeManager = $storeManager;
    }

    /**
     * @param array $data
     * @return array
     */
    public function modifyData(array $data)
    {
        /** @var AddonModel $addon */
        $addon = $this->coreRegistry->registry(AddonModel::REGISTRY_NAME);

        if($addon->getId()) {
            $data[$addon->getId()] = $addon->getData();
        }

        $data = $this->formatCurrency($data);

        return $data;
    }

    /**
     * Disable code field while editing existing add-on
     *
     * @param array $meta
     * @return array
     */
    public function modifyMeta(array $meta)
    {
        $addon = $this->coreRegistry->registry(AddonModel::REGISTRY_NAME);

        if ($addon->getId()) {
            $meta += [
                'general' => [
                    'children' => [
                        'code' => [
                            'arguments' => [
                                'data' => [
                                    'config' => [
                                        'disabled' => true,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ];
        }

        return $meta;
    }

    /**
     * Formats currency fields
     *
     * @param $data
     * @return mixed
     */
    protected function formatCurrency($data)
    {
        /** @var \Magento\Store\Model\Store $store */
        $store = $this->storeManager->getStore();

        $currencyCode = $store->getBaseCurrencyCode();
        $currency = $this->currency->getCurrency($currencyCode);

        foreach ($data as $id => $addonData) {
            if (isset($addonData['amount'])) {
                $value = $currency->toCurrency(
                    $addonData['amount'],
                    ['display' => \Magento\Framework\Currency::NO_SYMBOL]
                );

                $data[$id]['amount'] = $value;
            }
        }

        return $data;
    }
}
