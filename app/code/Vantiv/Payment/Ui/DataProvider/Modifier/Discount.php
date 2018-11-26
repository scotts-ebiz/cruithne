<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Vantiv\Payment\Ui\DataProvider\Modifier;

use Magento\Framework\Locale\CurrencyInterface;
use Magento\Framework\Registry;
use Magento\Store\Model\StoreManagerInterface;
use Vantiv\Payment\Model\Recurring\Subscription\Discount as DiscountModel;
use Vantiv\Payment\Model\Recurring\Subscription\DiscountFactory;

class Discount implements \Magento\Ui\DataProvider\Modifier\ModifierInterface
{
    /**
     * @var DiscountFactory
     */
    private $discountFactory;

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
     * @param DiscountFactory $discountFactory
     * @param Registry $coreRegistry
     * @param StoreManagerInterface $storeManager
     * @param CurrencyInterface $currency
     */
    public function __construct(
        DiscountFactory $discountFactory,
        Registry $coreRegistry,
        StoreManagerInterface $storeManager,
        CurrencyInterface $currency
    ) {
        $this->discountFactory = $discountFactory;
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
        /** @var DiscountModel $discount */
        $discount = $this->coreRegistry->registry(DiscountModel::REGISTRY_NAME);

        if ($discount->getId()) {
            $data[$discount->getId()] = $discount->getData();
        }

        $data = $this->formatCurrency($data);

        return $data;
    }

    /**
     * Disable code field while editing existing discount
     *
     * @param array $meta
     * @return array
     */
    public function modifyMeta(array $meta)
    {
        $discount = $this->coreRegistry->registry(DiscountModel::REGISTRY_NAME);

        if ($discount->getId()) {
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

        foreach ($data as $id => $discountData) {
            if (isset($discountData['amount'])) {
                $value = $currency->toCurrency(
                    $discountData['amount'],
                    ['display' => \Magento\Framework\Currency::NO_SYMBOL]
                );

                $data[$id]['amount'] = $value;
            }
        }

        return $data;
    }
}
