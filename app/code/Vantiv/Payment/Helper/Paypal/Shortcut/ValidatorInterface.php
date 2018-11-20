<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Vantiv\Payment\Helper\Paypal\Shortcut;

interface ValidatorInterface
{
    /**
     * Validates shortcut
     *
     * @param string $code
     * @param bool $isInCatalog
     * @return bool
     */
    public function validate($code, $isInCatalog);
}
