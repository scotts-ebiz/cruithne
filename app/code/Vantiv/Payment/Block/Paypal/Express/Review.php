<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Vantiv\Payment\Block\Paypal\Express;

/**
 * Paypal Express Onepage checkout block
 */
class Review extends \Magento\Paypal\Block\Express\Review
{
    /**
     * Paypal controller path
     *
     * @var string
     */
    protected $_controllerPath = 'vantiv/paypal_express';
}
