<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Vantiv\Payment\Helper\Paypal\Shortcut;

class ValidatorPlugin
{
    /**
     * Registry key for isMethodAvailable execution flag
     */
    const IS_METHOD_AVAILABLE_REG_FLAG = 'paypal_shortcut_is_method_available';

    /**
     * @var \Magento\Framework\Registry
     */
    private $registry;

    /**
     * @param \Magento\Framework\Registry $registry
     */
    public function __construct(\Magento\Framework\Registry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * @param object $subject
     * @param \Closure $proceed
     * @param string $paymentCode
     * @return bool
     */
    public function aroundIsMethodAvailable(
        $subject,
        \Closure $proceed,
        $paymentCode
    ) {
        $this->registry->register(self::IS_METHOD_AVAILABLE_REG_FLAG, true, true);
        $result = $proceed($paymentCode);
        $this->registry->unregister(self::IS_METHOD_AVAILABLE_REG_FLAG);

        return $result;
    }
}
