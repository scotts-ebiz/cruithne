<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Gateway\Cc\Config;

/**
 * Vantiv payment configuration class.
 */
class VantivCcFallbackConfig extends \Vantiv\Payment\Gateway\Common\Config\VantivPaymentConfig
{
    /**
     * Array of Cc configuration fallback fields.
     *
     * @var array
     */
    private $ccFallbackFields = [
        'suspect_issuer_country',
        'suspect_issuer_action',
        'advanced_fraud_is_active',
        'advanced_fraud_results_review_action',
        'advanced_fraud_results_fail_action'
    ];

    /**
     * Retrieve information from payment configuration.
     * Or fallback to common configuration.
     *
     * @param string $field
     * @param int|null $storeId
     * @return mixed
     */
    public function getValue($field, $storeId = null)
    {
        $path = '';

        if ($this->isCcFallback($field)) {
            $path = sprintf(self::PAYMENT_PATH_PATTERN, VantivCcConfig::METHOD_CODE, $field);
        } elseif ($this->isFallback($field)) {
            $path = sprintf(self::VANTIV_PATH_PATTERN, $field);
        } else {
            $path = sprintf(self::PAYMENT_PATH_PATTERN, $this->getMethodCode(), $field);
        }

        return $this->getScopeConfigData($path, $storeId);
    }

    /**
     * Check if the option need to fallback to CC config.
     *
     * @param string $field
     * @return bool
     */
    private function isCcFallback($field)
    {
        return in_array($field, $this->ccFallbackFields);
    }
}
