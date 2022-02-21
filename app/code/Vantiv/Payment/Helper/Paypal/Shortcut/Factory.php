<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Vantiv\Payment\Helper\Paypal\Shortcut;

class Factory extends \Magento\Paypal\Helper\Shortcut\Factory
{
    /**
     * Default validator
     */
    const DEFAULT_VALIDATOR = 'Vantiv\Payment\Helper\Paypal\Shortcut\Validator';

    /**
     * Checkout validator
     */
    const CHECKOUT_VALIDATOR = 'Vantiv\Payment\Helper\Paypal\Shortcut\CheckoutValidator';

    /**
     * Overridden in order to use custom constants
     *
     * @param mixed $parameter
     * @return \Magento\Paypal\Helper\Shortcut\ValidatorInterface
     */
    public function create($parameter = null)
    {
        $instanceName = self::DEFAULT_VALIDATOR;
        if (is_object($parameter) && $parameter instanceof \Magento\Checkout\Model\Session) {
            $instanceName = self::CHECKOUT_VALIDATOR;
        }
        return $this->_objectManager->create($instanceName);
    }
}
