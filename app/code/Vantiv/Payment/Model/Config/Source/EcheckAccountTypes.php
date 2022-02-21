<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Model\Config\Source;

/**
 * Available eCheck account types.
 */
class EcheckAccountTypes implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Checking account type.
     *
     * @var string
     */
    const CHECKING = 'Checking';

    /**
     * Savings account type.
     *
     * @var string
     */
    const SAVINGS = 'Savings';

    /**
     * Corporate acount type.
     *
     * @var string
     */
    const CORPORATE_CHECKING = 'Corporate';

    /**
     * Corporate savings account type.
     *
     * @var string
     */
    const CORPORATE_SAVINGS = 'Corp Savings';

    /**
     * Return possible account types' options.
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => self::CHECKING,
                'label' => __('Checking'),
            ], [
                'value' => self::SAVINGS,
                'label' => __('Savings'),
            ], [
                'value' => self::CORPORATE_CHECKING,
                'label' => __('Corporate Checking'),
            ], [
                'value' => self::CORPORATE_SAVINGS,
                'label' => __('Corporate Savings'),
            ],
        ];
    }

    /**
     * Return array of account types.
     *
     * @return array
     */
    public function toArray()
    {
        return [
            self::CHECKING,
            self::SAVINGS,
            self::CORPORATE_CHECKING,
            self::CORPORATE_SAVINGS,
        ];
    }
}
